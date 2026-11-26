import Foundation
import AVFoundation
import CoreGraphics
import CoreImage
import ImageIO

struct SceneInput: Decodable {
    let image: String
    let audio: String?
    let duration: Double
}

guard CommandLine.arguments.count >= 3 else {
    fputs("Usage: make_video <manifest.json> <output.mp4> [bgm.wav]\n", stderr)
    exit(1)
}

let manifestPath = CommandLine.arguments[1]
let outputPath = CommandLine.arguments[2]
let bgmPath = CommandLine.arguments.count >= 4 ? CommandLine.arguments[3] : nil

let data = try Data(contentsOf: URL(fileURLWithPath: manifestPath))
let scenes = try JSONDecoder().decode([SceneInput].self, from: data)
guard !scenes.isEmpty else {
    fputs("Error: empty scenes manifest\n", stderr)
    exit(1)
}

func loadCGImage(_ path: String) -> CGImage? {
    let url = URL(fileURLWithPath: path) as CFURL
    guard let src = CGImageSourceCreateWithURL(url, nil) else { return nil }
    return CGImageSourceCreateImageAtIndex(src, 0, nil)
}

let width = 1080
let height = 1920
let fps: Int32 = 30

let tempVideoURL = URL(fileURLWithPath: outputPath + ".video_only.mp4")
try? FileManager.default.removeItem(at: tempVideoURL)
try? FileManager.default.removeItem(at: URL(fileURLWithPath: outputPath))

let writer = try AVAssetWriter(outputURL: tempVideoURL, fileType: .mp4)
let videoSettings: [String: Any] = [
    AVVideoCodecKey: AVVideoCodecType.h264,
    AVVideoWidthKey: width,
    AVVideoHeightKey: height,
    AVVideoCompressionPropertiesKey: [
        AVVideoAverageBitRateKey: 5_000_000,
        AVVideoProfileLevelKey: AVVideoProfileLevelH264HighAutoLevel,
        AVVideoMaxKeyFrameIntervalKey: 30
    ]
]
let writerInput = AVAssetWriterInput(mediaType: .video, outputSettings: videoSettings)
writerInput.expectsMediaDataInRealTime = false

let attrs: [String: Any] = [
    kCVPixelBufferPixelFormatTypeKey as String: Int(kCVPixelFormatType_32BGRA),
    kCVPixelBufferWidthKey as String: width,
    kCVPixelBufferHeightKey as String: height,
    kCVPixelBufferCGImageCompatibilityKey as String: true,
    kCVPixelBufferCGBitmapContextCompatibilityKey as String: true
]
let adaptor = AVAssetWriterInputPixelBufferAdaptor(assetWriterInput: writerInput, sourcePixelBufferAttributes: attrs)
guard writer.canAdd(writerInput) else {
    fputs("Error: cannot add video writer input\n", stderr)
    exit(1)
}
writer.add(writerInput)
writer.startWriting()
writer.startSession(atSourceTime: .zero)

var sceneImages: [CGImage] = []
for sc in scenes {
    guard let img = loadCGImage(sc.image) else {
        fputs("Error loading image: \(sc.image)\n", stderr)
        exit(1)
    }
    sceneImages.append(img)
}

var frameIndex: Int64 = 0
let colorSpace = CGColorSpaceCreateDeviceRGB()

func renderFrame(img: CGImage, nextImg: CGImage?, zoom: Double, fadeAlpha: Double, pool: CVPixelBufferPool) -> CVPixelBuffer? {
    var maybeBuf: CVPixelBuffer?
    let status = CVPixelBufferPoolCreatePixelBuffer(kCFAllocatorDefault, pool, &maybeBuf)
    guard status == kCVReturnSuccess, let buf = maybeBuf else { return nil }

    CVPixelBufferLockBaseAddress(buf, [])
    defer { CVPixelBufferUnlockBaseAddress(buf, []) }

    guard let ctx = CGContext(
        data: CVPixelBufferGetBaseAddress(buf),
        width: width,
        height: height,
        bitsPerComponent: 8,
        bytesPerRow: CVPixelBufferGetBytesPerRow(buf),
        space: colorSpace,
        bitmapInfo: CGBitmapInfo.byteOrder32Little.rawValue | CGImageAlphaInfo.premultipliedFirst.rawValue
    ) else { return nil }

    ctx.interpolationQuality = .high
    let w = Double(width)
    let h = Double(height)

    // Draw current image with gentle zoom
    let zw = w * zoom
    let zh = h * zoom
    let zx = (w - zw) / 2.0
    let zy = (h - zh) / 2.0
    ctx.setAlpha(1.0)
    ctx.draw(img, in: CGRect(x: zx, y: zy, width: zw, height: zh))

    // Cross-dissolve near end of scene if transitioning to nextImg
    if fadeAlpha > 0.0, let nxt = nextImg {
        ctx.setAlpha(CGFloat(fadeAlpha))
        ctx.draw(nxt, in: CGRect(x: 0, y: 0, width: w, height: h))
    }
    return buf
}

let semaphore = DispatchSemaphore(value: 0)
let queue = DispatchQueue(label: "video.encode")

var currentSceneIdx = 0
var sceneFrameIdx = 0
var sceneStartTimes: [CMTime] = []
var cumulativeTime = 0.0
for sc in scenes {
    sceneStartTimes.append(CMTime(seconds: cumulativeTime, preferredTimescale: 600))
    cumulativeTime += sc.duration
}

let fadeFrames = 10 // ~0.33s smooth crossfade

writerInput.requestMediaDataWhenReady(on: queue) {
    guard let pool = adaptor.pixelBufferPool else {
        writerInput.markAsFinished()
        semaphore.signal()
        return
    }

    while writerInput.isReadyForMoreMediaData {
        if currentSceneIdx >= scenes.count {
            writerInput.markAsFinished()
            semaphore.signal()
            break
        }

        let sc = scenes[currentSceneIdx]
        let totalSceneFrames = max(1, Int(round(sc.duration * Double(fps))))
        let progress = Double(sceneFrameIdx) / Double(max(1, totalSceneFrames - 1))
        let zoom = 1.0 + (0.045 * progress)

        var fadeAlpha = 0.0
        var nextImg: CGImage? = nil
        if currentSceneIdx + 1 < scenes.count && sceneFrameIdx >= totalSceneFrames - fadeFrames {
            let fadeProgress = Double(sceneFrameIdx - (totalSceneFrames - fadeFrames)) / Double(fadeFrames)
            fadeAlpha = min(1.0, max(0.0, fadeProgress))
            nextImg = sceneImages[currentSceneIdx + 1]
        }

        if let buf = renderFrame(img: sceneImages[currentSceneIdx], nextImg: nextImg, zoom: zoom, fadeAlpha: fadeAlpha, pool: pool) {
            let pts = CMTime(value: frameIndex, timescale: fps)
            adaptor.append(buf, withPresentationTime: pts)
        }

        frameIndex += 1
        sceneFrameIdx += 1
        if sceneFrameIdx >= totalSceneFrames {
            currentSceneIdx += 1
            sceneFrameIdx = 0
        }
    }
}

semaphore.wait()
let finishSem = DispatchSemaphore(value: 0)
writer.finishWriting {
    finishSem.signal()
}
finishSem.wait()

if writer.status == .failed {
    fputs("Writer error: \(writer.error?.localizedDescription ?? "unknown")\n", stderr)
    exit(1)
}

// Merge video + scene voiceovers + ambient background pad using AVMutableComposition
let comp = AVMutableComposition()
let videoAsset = AVURLAsset(url: tempVideoURL)
let videoTrackGroup = videoAsset.tracks(withMediaType: .video)
guard let srcVideoTrack = videoTrackGroup.first,
      let compVideoTrack = comp.addMutableTrack(withMediaType: .video, preferredTrackID: kCMPersistentTrackID_Invalid) else {
    fputs("Failed to compose video track\n", stderr)
    exit(1)
}
let totalDuration = CMTime(seconds: cumulativeTime, preferredTimescale: 600)
try compVideoTrack.insertTimeRange(CMTimeRange(start: .zero, duration: min(videoAsset.duration, totalDuration)), of: srcVideoTrack, at: .zero)

var mixParams: [AVMutableAudioMixInputParameters] = []

// Add voiceover audio per scene
if let voiceTrack = comp.addMutableTrack(withMediaType: .audio, preferredTrackID: kCMPersistentTrackID_Invalid) {
    for (idx, sc) in scenes.enumerated() {
        guard let aPath = sc.audio, FileManager.default.fileExists(atPath: aPath) else { continue }
        let aAsset = AVURLAsset(url: URL(fileURLWithPath: aPath))
        if let srcA = aAsset.tracks(withMediaType: .audio).first {
            let maxDur = min(aAsset.duration, CMTime(seconds: sc.duration, preferredTimescale: 600))
            try? voiceTrack.insertTimeRange(CMTimeRange(start: .zero, duration: maxDur), of: srcA, at: sceneStartTimes[idx])
        }
    }
    let vp = AVMutableAudioMixInputParameters(track: voiceTrack)
    vp.setVolume(1.0, at: .zero)
    mixParams.append(vp)
}

// Add soft copyright-free ambient music pad
if let bgm = bgmPath, FileManager.default.fileExists(atPath: bgm) {
    let bAsset = AVURLAsset(url: URL(fileURLWithPath: bgm))
    if let srcB = bAsset.tracks(withMediaType: .audio).first,
       let bgmTrack = comp.addMutableTrack(withMediaType: .audio, preferredTrackID: kCMPersistentTrackID_Invalid) {
        let dur = min(bAsset.duration, totalDuration)
        try? bgmTrack.insertTimeRange(CMTimeRange(start: .zero, duration: dur), of: srcB, at: .zero)
        let bp = AVMutableAudioMixInputParameters(track: bgmTrack)
        bp.setVolume(0.14, at: .zero)
        mixParams.append(bp)
    }
}

let audioMix = AVMutableAudioMix()
audioMix.inputParameters = mixParams

let outURL = URL(fileURLWithPath: outputPath)
guard let exporter = AVAssetExportSession(asset: comp, presetName: AVAssetExportPresetHighestQuality) else {
    fputs("Failed to create AVAssetExportSession\n", stderr)
    exit(1)
}
exporter.outputURL = outURL
exporter.outputFileType = .mp4
exporter.shouldOptimizeForNetworkUse = true
exporter.audioMix = audioMix

let expSem = DispatchSemaphore(value: 0)
exporter.exportAsynchronously {
    expSem.signal()
}
expSem.wait()

try? FileManager.default.removeItem(at: tempVideoURL)

if exporter.status != .completed {
    fputs("Export error: \(exporter.error?.localizedDescription ?? "unknown")\n", stderr)
    exit(1)
}

print("EXPORTED \(outputPath) (\(String(format: "%.2f", cumulativeTime))s)")
