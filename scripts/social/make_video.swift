import Foundation
import AVFoundation
import CoreGraphics
import CoreText
import ImageIO

struct SubSegment: Decodable {
    let start: Double
    let end: Double
    let text: String
    let audio: String?
}

struct SceneSpec: Decodable {
    let badge: String
    let title: String
    let subtitle: String
    let url: String
    let steps: [String]
    let active_step: Int
    let highlights: [String]
    let image_before: String
    let image_after: String?
    let cursor_start: [Double]?
    let cursor_target: [Double]?
    let transition_mode: String?
    let duration: Double
    let sub_segments: [SubSegment]
}

struct RenderManifest: Decodable {
    let brand_name: String
    let domain: String
    let logo: String?
    let bgm: String?
    let output: String
    let scenes: [SceneSpec]
}

guard CommandLine.arguments.count >= 2 else {
    fputs("Usage: make_video <manifest.json>\n", stderr)
    exit(1)
}

let manifestURL = URL(fileURLWithPath: CommandLine.arguments[1])
let manifestData = try Data(contentsOf: manifestURL)
let manifest = try JSONDecoder().decode(RenderManifest.self, from: manifestData)

guard !manifest.scenes.isEmpty else {
    fputs("Error: no scenes in manifest\n", stderr)
    exit(1)
}

func loadCGImage(_ path: String?) -> CGImage? {
    guard let p = path, !p.isEmpty else { return nil }
    let url = URL(fileURLWithPath: p) as CFURL
    guard let src = CGImageSourceCreateWithURL(url, nil) else { return nil }
    return CGImageSourceCreateImageAtIndex(src, 0, nil)
}

let W: Double = 1080.0
let H: Double = 1920.0
let fps: Int32 = 30
let colorSpace = CGColorSpaceCreateDeviceRGB()

func rgba(_ r: Double, _ g: Double, _ b: Double, _ a: Double = 1.0) -> CGColor {
    return CGColor(colorSpace: colorSpace, components: [CGFloat(r/255.0), CGFloat(g/255.0), CGFloat(b/255.0), CGFloat(a)])!
}

func topRect(x: Double, topY: Double, w: Double, h: Double) -> CGRect {
    return CGRect(x: x, y: H - topY - h, width: w, height: h)
}

func topPoint(x: Double, topY: Double) -> CGPoint {
    return CGPoint(x: x, y: H - topY)
}

func fillRoundedRect(_ ctx: CGContext, rect: CGRect, radius: Double, fill: CGColor, stroke: CGColor? = nil, lineWidth: Double = 1.5) {
    let path = CGPath(roundedRect: rect, cornerWidth: CGFloat(radius), cornerHeight: CGFloat(radius), transform: nil)
    ctx.saveGState()
    ctx.addPath(path)
    ctx.setFillColor(fill)
    ctx.fillPath()
    if let s = stroke {
        ctx.addPath(path)
        ctx.setStrokeColor(s)
        ctx.setLineWidth(CGFloat(lineWidth))
        ctx.strokePath()
    }
    ctx.restoreGState()
}

func drawText(_ ctx: CGContext, text: String, rectTop: CGRect, fontSize: Double, weight: CTFontSymbolicTraits = [], color: CGColor, align: CTTextAlignment = .center, lineSpacing: Double = 4.0) {
    let baseFont = CTFontCreateWithName("HelveticaNeue" as CFString, CGFloat(fontSize), nil)
    let font: CTFont
    if !weight.isEmpty, let boldFont = CTFontCreateCopyWithSymbolicTraits(baseFont, CGFloat(fontSize), nil, weight, weight) {
        font = boldFont
    } else {
        font = baseFont
    }

    var textAlign = align
    var ls = CGFloat(lineSpacing)
    let para: CTParagraphStyle = withUnsafePointer(to: &textAlign) { pAlign in
        withUnsafePointer(to: &ls) { pLs in
            let paraSettings = [
                CTParagraphStyleSetting(spec: .alignment, valueSize: MemoryLayout<CTTextAlignment>.size, value: pAlign),
                CTParagraphStyleSetting(spec: .lineSpacingAdjustment, valueSize: MemoryLayout<CGFloat>.size, value: pLs)
            ]
            return CTParagraphStyleCreate(paraSettings, paraSettings.count)
        }
    }

    let attrs: [NSAttributedString.Key: Any] = [
        kCTFontAttributeName as NSAttributedString.Key: font,
        kCTForegroundColorAttributeName as NSAttributedString.Key: color,
        kCTParagraphStyleAttributeName as NSAttributedString.Key: para
    ]
    let attrStr = NSAttributedString(string: text, attributes: attrs)
    let framesetter = CTFramesetterCreateWithAttributedString(attrStr)

    // Measure text height to vertically center if helpful, or use exact rectTop
    let cgRect = topRect(x: Double(rectTop.origin.x), topY: Double(rectTop.origin.y), w: Double(rectTop.size.width), h: Double(rectTop.size.height))
    let path = CGMutablePath()
    path.addRect(cgRect)
    let frame = CTFramesetterCreateFrame(framesetter, CFRange(location: 0, length: attrStr.length), path, nil)

    ctx.saveGState()
    ctx.textMatrix = .identity
    CTFrameDraw(frame, ctx)
    ctx.restoreGState()
}

func measureTextWidth(_ text: String, fontSize: Double, bold: Bool) -> Double {
    let baseFont = CTFontCreateWithName("HelveticaNeue" as CFString, CGFloat(fontSize), nil)
    let font: CTFont
    if bold, let bf = CTFontCreateCopyWithSymbolicTraits(baseFont, CGFloat(fontSize), nil, .traitBold, .traitBold) {
        font = bf
    } else {
        font = baseFont
    }
    let attr = NSAttributedString(string: text, attributes: [kCTFontAttributeName as NSAttributedString.Key: font])
    let line = CTLineCreateWithAttributedString(attr)
    return Double(CTLineGetTypographicBounds(line, nil, nil, nil))
}

func drawCursor(_ ctx: CGContext, tipX: Double, tipTopY: Double, scale: Double = 1.0) {
    let s = scale
    // Tip is at (tipX, tipTopY) in top-down space -> convert each vertex cleanly
    let ptsTop: [(Double, Double)] = [
        (0, 0),
        (0, 42 * s),
        (11 * s, 32 * s),
        (20 * s, 49 * s),
        (27 * s, 45 * s),
        (18 * s, 28 * s),
        (31 * s, 27 * s)
    ]
    let path = CGMutablePath()
    for (idx, p) in ptsTop.enumerated() {
        let pt = topPoint(x: tipX + p.0, topY: tipTopY + p.1)
        if idx == 0 { path.move(to: pt) } else { path.addLine(to: pt) }
    }
    path.closeSubpath()

    ctx.saveGState()
    ctx.setShadow(offset: CGSize(width: 2, height: -4), blur: 10, color: rgba(15, 23, 42, 0.55))
    ctx.addPath(path)
    ctx.setFillColor(rgba(255, 255, 255, 1.0))
    ctx.fillPath()
    ctx.restoreGState()

    ctx.saveGState()
    ctx.addPath(path)
    ctx.setStrokeColor(rgba(37, 99, 235, 1.0))
    ctx.setLineWidth(4.0)
    ctx.setLineJoin(.round)
    ctx.strokePath()
    ctx.restoreGState()
}

func easeInOutCubic(_ t: Double) -> Double {
    let x = min(1.0, max(0.0, t))
    return x < 0.5 ? 4 * x * x * x : 1 - pow(-2 * x + 2, 3) / 2
}

struct LoadedScene {
    let spec: SceneSpec
    let imgBefore: CGImage
    let imgAfter: CGImage?
}

let logoImg = loadCGImage(manifest.logo)
var loadedScenes: [LoadedScene] = []
for sc in manifest.scenes {
    guard let before = loadCGImage(sc.image_before) else {
        fputs("Error: could not load image_before: \(sc.image_before)\n", stderr)
        exit(1)
    }
    let after = loadCGImage(sc.image_after)
    loadedScenes.append(LoadedScene(spec: sc, imgBefore: before, imgAfter: after))
}

let outputPath = manifest.output
let tempVideoURL = URL(fileURLWithPath: outputPath + ".video_only.mp4")
try? FileManager.default.removeItem(at: tempVideoURL)
try? FileManager.default.removeItem(at: URL(fileURLWithPath: outputPath))

let writer = try AVAssetWriter(outputURL: tempVideoURL, fileType: .mp4)
let videoSettings: [String: Any] = [
    AVVideoCodecKey: AVVideoCodecType.h264,
    AVVideoWidthKey: Int(W),
    AVVideoHeightKey: Int(H),
    AVVideoCompressionPropertiesKey: [
        AVVideoAverageBitRateKey: 6_500_000,
        AVVideoProfileLevelKey: AVVideoProfileLevelH264HighAutoLevel,
        AVVideoMaxKeyFrameIntervalKey: 30
    ]
]
let writerInput = AVAssetWriterInput(mediaType: .video, outputSettings: videoSettings)
writerInput.expectsMediaDataInRealTime = false

let attrs: [String: Any] = [
    kCVPixelBufferPixelFormatTypeKey as String: Int(kCVPixelFormatType_32BGRA),
    kCVPixelBufferWidthKey as String: Int(W),
    kCVPixelBufferHeightKey as String: Int(H),
    kCVPixelBufferCGImageCompatibilityKey as String: true,
    kCVPixelBufferCGBitmapContextCompatibilityKey as String: true
]
let adaptor = AVAssetWriterInputPixelBufferAdaptor(assetWriterInput: writerInput, sourcePixelBufferAttributes: attrs)
guard writer.canAdd(writerInput) else {
    fputs("Error: cannot add writerInput\n", stderr)
    exit(1)
}
writer.add(writerInput)
writer.startWriting()
writer.startSession(atSourceTime: .zero)

func drawAspectFill(_ ctx: CGContext, img: CGImage, destRect: CGRect, zoom: Double = 1.0, verticalPanRatio: Double = 0.0, alpha: Double = 1.0) {
    ctx.saveGState()
    ctx.clip(to: destRect)
    ctx.setAlpha(CGFloat(alpha))
    ctx.interpolationQuality = .high

    let imgW = Double(img.width)
    let imgH = Double(img.height)
    let dW = Double(destRect.size.width)
    let dH = Double(destRect.size.height)

    // Fit width cleanly so full browser UI spans edge to edge horizontally
    let baseScale = max(dW / imgW, dH / imgH)
    let scale = baseScale * zoom
    let drawW = imgW * scale
    let drawH = imgH * scale

    let drawX = Double(destRect.origin.x) + (dW - drawW) / 2.0
    // Center vertically around top anchor or pan downwards for tall screenshots
    let overflowH = max(0.0, drawH - dH)
    let topOffset = overflowH * min(1.0, max(0.0, verticalPanRatio))
    // Bottom-left CGContext coordinates: top of destRect is maxY, so drawRect top aligns with maxY + topOffset
    let drawY = Double(destRect.maxY) - drawH + topOffset
    ctx.draw(img, in: CGRect(x: drawX, y: drawY, width: drawW, height: drawH))
    ctx.restoreGState()
}

func renderSceneFrame(scene: LoadedScene, localTime: Double, sceneAlpha: Double, pool: CVPixelBufferPool) -> CVPixelBuffer? {
    var maybeBuf: CVPixelBuffer?
    let status = CVPixelBufferPoolCreatePixelBuffer(kCFAllocatorDefault, pool, &maybeBuf)
    guard status == kCVReturnSuccess, let buf = maybeBuf else { return nil }

    CVPixelBufferLockBaseAddress(buf, [])
    defer { CVPixelBufferUnlockBaseAddress(buf, []) }

    guard let ctx = CGContext(
        data: CVPixelBufferGetBaseAddress(buf),
        width: Int(W),
        height: Int(H),
        bitsPerComponent: 8,
        bytesPerRow: CVPixelBufferGetBytesPerRow(buf),
        space: colorSpace,
        bitmapInfo: CGBitmapInfo.byteOrder32Little.rawValue | CGImageAlphaInfo.premultipliedFirst.rawValue
    ) else { return nil }

    let sc = scene.spec
    let dur = max(0.5, sc.duration)
    let p = min(1.0, max(0.0, localTime / dur))

    // 1. Background Navy Gradient (#0B1324 -> #162442 -> #0F172A)
    let bgColors = [rgba(10, 18, 36), rgba(22, 37, 68), rgba(15, 23, 42)] as CFArray
    if let grad = CGGradient(colorsSpace: colorSpace, colors: bgColors, locations: [0.0, 0.45, 1.0]) {
        ctx.drawLinearGradient(grad, start: CGPoint(x: W / 2, y: H), end: CGPoint(x: W / 2, y: 0), options: [])
    }

    // Subtle glowing accent orbs
    ctx.saveGState()
    ctx.setFillColor(rgba(37, 99, 235, 0.14))
    ctx.fillEllipse(in: topRect(x: -140, topY: -120, w: 540, h: 540))
    ctx.setFillColor(rgba(16, 185, 129, 0.09))
    ctx.fillEllipse(in: topRect(x: W - 360, topY: H - 460, w: 500, h: 500))
    ctx.restoreGState()

    // 2. Top Brand Row (topY: 70)
    let brandX = 64.0
    let brandTopY = 72.0
    if let logo = logoImg {
        fillRoundedRect(ctx, rect: topRect(x: brandX, topY: brandTopY, w: 54, h: 54), radius: 14, fill: rgba(255, 255, 255, 1.0))
        ctx.draw(logo, in: topRect(x: brandX + 6, topY: brandTopY + 6, w: 42, h: 42))
    }
    drawText(ctx, text: manifest.brand_name, rectTop: CGRect(x: brandX + 68, y: brandTopY + 10, width: 320, height: 40), fontSize: 28, weight: .traitBold, color: rgba(255, 255, 255), align: .left)
    drawText(ctx, text: manifest.domain, rectTop: CGRect(x: W - 360, y: brandTopY + 14, width: 296, height: 36), fontSize: 23, weight: .traitBold, color: rgba(147, 197, 253), align: .right)

    // 3. Category Badge Pill (topY: 150)
    let badgeTextW = measureTextWidth(sc.badge, fontSize: 21, bold: true) + 52.0
    let badgeW = min(W - 120, max(280.0, badgeTextW))
    fillRoundedRect(ctx, rect: topRect(x: (W - badgeW) / 2.0, topY: 148, w: badgeW, h: 46), radius: 23, fill: rgba(30, 58, 138, 0.72), stroke: rgba(96, 165, 250, 0.55), lineWidth: 1.5)
    drawText(ctx, text: sc.badge, rectTop: CGRect(x: (W - badgeW) / 2.0, y: 159, width: badgeW, height: 34), fontSize: 21, weight: .traitBold, color: rgba(191, 219, 254), align: .center)

    // 4. Headline & Subtitle (topY: 214 .. 365)
    drawText(ctx, text: sc.title, rectTop: CGRect(x: 60, y: 214, width: W - 120, height: 112), fontSize: 46, weight: .traitBold, color: rgba(255, 255, 255), align: .center, lineSpacing: 4)
    drawText(ctx, text: sc.subtitle, rectTop: CGRect(x: 72, y: 332, width: W - 144, height: 64), fontSize: 25, weight: [], color: rgba(203, 213, 225), align: .center, lineSpacing: 3)

    // 5. Step Pill Strip (topY: 412)
    let steps = sc.steps.isEmpty ? ["1. Judul", "2. Bab I-V", "3. Jurnal", "4. PPT", "5. Sidang", "6. Tools"] : sc.steps
    let pillGap = 10.0
    let totalStripW = W - 96.0
    let pillW = (totalStripW - pillGap * Double(steps.count - 1)) / Double(steps.count)
    for (idx, st) in steps.enumerated() {
        let px = 48.0 + Double(idx) * (pillW + pillGap)
        let isActive = idx == sc.active_step
        let isDone = idx < sc.active_step
        let fill = isActive ? rgba(37, 99, 235, 1.0) : (isDone ? rgba(16, 185, 129, 0.25) : rgba(255, 255, 255, 0.08))
        let stroke = isActive ? rgba(147, 197, 253, 0.9) : (isDone ? rgba(52, 211, 153, 0.55) : rgba(255, 255, 255, 0.14))
        let txtColor = isActive ? rgba(255, 255, 255) : (isDone ? rgba(167, 243, 208) : rgba(148, 163, 184))
        fillRoundedRect(ctx, rect: topRect(x: px, topY: 410, w: pillW, h: 44), radius: 14, fill: fill, stroke: stroke, lineWidth: 1.5)
        drawText(ctx, text: st, rectTop: CGRect(x: px + 4, y: 421, width: pillW - 8, height: 28), fontSize: 18, weight: .traitBold, color: txtColor, align: .center)
    }

    // 6. Browser Frame Card (topY: 478, width: 984, height: 830)
    let cardX = 48.0
    let cardTopY = 478.0
    let cardW = W - 96.0
    let cardH = 825.0
    let chromeH = 54.0

    ctx.saveGState()
    ctx.setShadow(offset: CGSize(width: 0, height: -18), blur: 40, color: rgba(0, 0, 0, 0.5))
    fillRoundedRect(ctx, rect: topRect(x: cardX, topY: cardTopY, w: cardW, h: cardH), radius: 28, fill: rgba(255, 255, 255, 1.0))
    ctx.restoreGState()

    // Chrome top bar (#F1F5F9)
    let frameRect = topRect(x: cardX, topY: cardTopY, w: cardW, h: cardH)
    let frameClip = CGPath(roundedRect: frameRect, cornerWidth: 28, cornerHeight: 28, transform: nil)
    ctx.saveGState()
    ctx.addPath(frameClip)
    ctx.clip()

    ctx.setFillColor(rgba(241, 245, 249))
    ctx.fill(topRect(x: cardX, topY: cardTopY, w: cardW, h: chromeH))
    ctx.setFillColor(rgba(226, 232, 240))
    ctx.fill(topRect(x: cardX, topY: cardTopY + chromeH - 1.5, w: cardW, h: 1.5))

    // Traffic lights
    let dotY = cardTopY + 19.0
    ctx.setFillColor(rgba(239, 68, 68)); ctx.fillEllipse(in: topRect(x: cardX + 24, topY: dotY, w: 15, h: 15))
    ctx.setFillColor(rgba(245, 158, 11)); ctx.fillEllipse(in: topRect(x: cardX + 49, topY: dotY, w: 15, h: 15))
    ctx.setFillColor(rgba(34, 197, 94)); ctx.fillEllipse(in: topRect(x: cardX + 74, topY: dotY, w: 15, h: 15))

    // URL pill in chrome
    fillRoundedRect(ctx, rect: topRect(x: cardX + 180, topY: cardTopY + 11, w: cardW - 360, h: 32), radius: 10, fill: rgba(255, 255, 255), stroke: rgba(203, 213, 225), lineWidth: 1.0)
    drawText(ctx, text: sc.url, rectTop: CGRect(x: cardX + 188, y: cardTopY + 18, width: cardW - 376, height: 24), fontSize: 18, weight: .traitBold, color: rgba(71, 85, 105), align: .center)

    // Viewport inside browser card
    let viewTopY = cardTopY + chromeH
    let viewH = cardH - chromeH
    let viewRect = topRect(x: cardX, topY: viewTopY, w: cardW, h: viewH)

    let mode = sc.transition_mode ?? "click"
    let zoom = 1.0 + 0.028 * p

    if mode == "scroll" {
        if let after = scene.imgAfter {
            let fade = p < 0.42 ? 0.0 : easeInOutCubic((p - 0.42) / 0.22)
            drawAspectFill(ctx, img: scene.imgBefore, destRect: viewRect, zoom: 1.0, verticalPanRatio: min(1.0, p * 1.4), alpha: 1.0 - fade)
            if fade > 0.0 {
                drawAspectFill(ctx, img: after, destRect: viewRect, zoom: 1.0 + 0.02 * (p - 0.42), verticalPanRatio: max(0.0, (p - 0.5) * 1.2), alpha: fade)
            }
        } else {
            drawAspectFill(ctx, img: scene.imgBefore, destRect: viewRect, zoom: 1.0, verticalPanRatio: easeInOutCubic(p), alpha: 1.0)
        }
    } else {
        // Click or crossfade interaction
        let clickMoment = 0.36
        let hasAfter = scene.imgAfter != nil
        let afterAlpha = hasAfter ? easeInOutCubic((p - clickMoment) / 0.14) : 0.0

        drawAspectFill(ctx, img: scene.imgBefore, destRect: viewRect, zoom: 1.0 + 0.012 * min(1.0, p / clickMoment), verticalPanRatio: 0.0, alpha: 1.0 - afterAlpha)
        if let after = scene.imgAfter, afterAlpha > 0.0 {
            drawAspectFill(ctx, img: after, destRect: viewRect, zoom: zoom, verticalPanRatio: 0.0, alpha: afterAlpha)
        }

        // Animated Cursor + Click Ripple
        if let targetArr = sc.cursor_target, targetArr.count >= 2 {
            let startArr = (sc.cursor_start?.count ?? 0) >= 2 ? sc.cursor_start! : [0.22, 0.78]
            let sx = cardX + startArr[0] * cardW
            let sy = viewTopY + startArr[1] * viewH
            let tx = cardX + targetArr[0] * cardW
            let ty = viewTopY + targetArr[1] * viewH

            let moveT = easeInOutCubic(p / 0.26)
            let cx = sx + (tx - sx) * moveT
            let cy = sy + (ty - sy) * moveT

            // Click ripple ring between 0.28 and 0.48
            if p >= 0.28 && p <= 0.52 {
                let rp = (p - 0.28) / 0.24
                let radius = 14.0 + rp * 54.0
                let alpha = max(0.0, 1.0 - rp)
                ctx.saveGState()
                ctx.setStrokeColor(rgba(37, 99, 235, alpha * 0.85))
                ctx.setLineWidth(5.0)
                ctx.strokeEllipse(in: topRect(x: tx - radius, topY: ty - radius, w: radius * 2, h: radius * 2))
                ctx.restoreGState()
            }

            if p < 0.72 {
                let pressScale = (p >= 0.27 && p <= 0.35) ? 0.86 : 1.0
                let cursorFade = p > 0.62 ? max(0.0, 1.0 - (p - 0.62) / 0.10) : 1.0
                ctx.saveGState()
                ctx.setAlpha(CGFloat(cursorFade))
                drawCursor(ctx, tipX: cx, tipTopY: cy, scale: pressScale)
                ctx.restoreGState()
            }
        }
    }

    ctx.restoreGState() // End browser frame clip

    // Browser border outline
    fillRoundedRect(ctx, rect: frameRect, radius: 28, fill: rgba(0, 0, 0, 0), stroke: rgba(148, 163, 184, 0.35), lineWidth: 2.0)

    // 7. Two Feature Highlight Pills Below Browser Card (topY: 1330)
    let pillTopY = 1330.0
    for (idx, hl) in sc.highlights.prefix(2).enumerated() {
        let y = pillTopY + Double(idx) * 62.0
        let w = min(W - 140.0, max(540.0, measureTextWidth(hl, fontSize: 23, bold: true) + 72.0))
        let px = (W - w) / 2.0
        fillRoundedRect(ctx, rect: topRect(x: px, topY: y, w: w, h: 48), radius: 14, fill: idx == 0 ? rgba(37, 99, 235, 0.92) : rgba(30, 41, 59, 0.95), stroke: idx == 0 ? rgba(96, 165, 250, 0.75) : rgba(71, 85, 105, 0.8), lineWidth: 1.5)
        drawText(ctx, text: hl, rectTop: CGRect(x: px + 18, y: y + 11, width: w - 36, height: 32), fontSize: 22, weight: .traitBold, color: rgba(255, 255, 255), align: .center)
    }

    // 8. Synced Dynamic Bottom Subtitle Pill (topY: 1485..1655 — safe above TikTok/Reels lower UI!)
    var activeSubText = sc.subtitle
    for seg in sc.sub_segments {
        if localTime >= seg.start && localTime <= seg.end {
            activeSubText = seg.text
            break
        }
        if localTime > seg.end {
            activeSubText = seg.text
        }
    }

    let subBoxW = min(W - 96.0, 940.0)
    let subBoxX = (W - subBoxW) / 2.0
    let subBoxTopY = 1488.0
    let subBoxH = 128.0
    ctx.saveGState()
    ctx.setShadow(offset: CGSize(width: 0, height: -8), blur: 24, color: rgba(0, 0, 0, 0.45))
    fillRoundedRect(ctx, rect: topRect(x: subBoxX, topY: subBoxTopY, w: subBoxW, h: subBoxH), radius: 24, fill: rgba(15, 23, 42, 0.94), stroke: rgba(59, 130, 246, 0.48), lineWidth: 2.0)
    ctx.restoreGState()

    drawText(ctx, text: activeSubText, rectTop: CGRect(x: subBoxX + 34, y: subBoxTopY + 24, width: subBoxW - 68, height: subBoxH - 36), fontSize: 31, weight: .traitBold, color: rgba(255, 255, 255), align: .center, lineSpacing: 6)

    // Scene crossfade alpha if near start
    if sceneAlpha < 1.0 {
        ctx.saveGState()
        ctx.setFillColor(rgba(10, 18, 36, 1.0 - sceneAlpha))
        ctx.fill(CGRect(x: 0, y: 0, width: W, height: H))
        ctx.restoreGState()
    }

    return buf
}

// Compute cumulative frame layout
var totalFrames: Int64 = 0
let sceneFrameCounts = loadedScenes.map { max(1, Int(round($0.spec.duration * Double(fps)))) }

let semaphore = DispatchSemaphore(value: 0)
let queue = DispatchQueue(label: "video.encode")

var currentSceneIdx = 0
var sceneFrameIdx = 0
var frameIndex: Int64 = 0

writerInput.requestMediaDataWhenReady(on: queue) {
    guard let pool = adaptor.pixelBufferPool else {
        writerInput.markAsFinished()
        semaphore.signal()
        return
    }

    while writerInput.isReadyForMoreMediaData {
        if currentSceneIdx >= loadedScenes.count {
            writerInput.markAsFinished()
            semaphore.signal()
            break
        }

        let sceneTotal = sceneFrameCounts[currentSceneIdx]
        let localTime = Double(sceneFrameIdx) / Double(fps)
        let alpha = sceneFrameIdx < 6 ? Double(sceneFrameIdx + 1) / 6.0 : 1.0

        if let buf = renderSceneFrame(scene: loadedScenes[currentSceneIdx], localTime: localTime, sceneAlpha: alpha, pool: pool) {
            let pts = CMTime(value: frameIndex, timescale: fps)
            if !adaptor.append(buf, withPresentationTime: pts) {
                fputs("Failed appending frame \(frameIndex): \(String(describing: writer.error))\n", stderr)
                writerInput.markAsFinished()
                semaphore.signal()
                break
            }
        }

        frameIndex += 1
        sceneFrameIdx += 1
        if sceneFrameIdx >= sceneTotal {
            sceneFrameIdx = 0
            currentSceneIdx += 1
        }
    }
}

semaphore.wait()

let finishSem = DispatchSemaphore(value: 0)
writer.finishWriting {
    finishSem.signal()
}
finishSem.wait()

if writer.status != .completed {
    fputs("AVAssetWriter failed: \(String(describing: writer.error))\n", stderr)
    exit(1)
}

// Mix all sub_segment audio tracks + soft ambient BGM pad using AVMutableComposition
let composition = AVMutableComposition()
let videoAsset = AVURLAsset(url: tempVideoURL)

let compSem = DispatchSemaphore(value: 0)
Task {
    do {
        if let srcVideoTrack = try await videoAsset.loadTracks(withMediaType: .video).first,
           let compVideoTrack = composition.addMutableTrack(withMediaType: .video, preferredTrackID: kCMPersistentTrackID_Invalid) {
            let dur = try await videoAsset.load(.duration)
            try compVideoTrack.insertTimeRange(CMTimeRange(start: .zero, duration: dur), of: srcVideoTrack, at: .zero)
        }

        var audioMixParams: [AVMutableAudioMixInputParameters] = []

        // Single voiceover track placing each sub-segment cleanly in time
        if let compVoiceTrack = composition.addMutableTrack(withMediaType: .audio, preferredTrackID: kCMPersistentTrackID_Invalid) {
            var cursorSec = 0.0
            for ls in loadedScenes {
                for seg in ls.spec.sub_segments {
                    if let ap = seg.audio, FileManager.default.fileExists(atPath: ap) {
                        let aAsset = AVURLAsset(url: URL(fileURLWithPath: ap))
                        if let aTrack = try await aAsset.loadTracks(withMediaType: .audio).first {
                            let aDur = try await aAsset.load(.duration)
                            let atTime = CMTime(seconds: cursorSec + seg.start, preferredTimescale: 600)
                            let segLen = min(aDur.seconds, max(0.2, seg.end - seg.start))
                            try compVoiceTrack.insertTimeRange(CMTimeRange(start: .zero, duration: CMTime(seconds: segLen, preferredTimescale: 600)), of: aTrack, at: atTime)
                        }
                    }
                }
                cursorSec += ls.spec.duration
            }
            let vp = AVMutableAudioMixInputParameters(track: compVoiceTrack)
            vp.setVolume(1.0, at: .zero)
            audioMixParams.append(vp)
        }

        // Subtle warm chord pad BGM
        if let bgm = manifest.bgm, FileManager.default.fileExists(atPath: bgm) {
            let bAsset = AVURLAsset(url: URL(fileURLWithPath: bgm))
            if let bTrack = try await bAsset.loadTracks(withMediaType: .audio).first,
               let compBgmTrack = composition.addMutableTrack(withMediaType: .audio, preferredTrackID: kCMPersistentTrackID_Invalid) {
                let totalSec = loadedScenes.reduce(0.0) { $0 + $1.spec.duration }
                let bDur = try await bAsset.load(.duration)
                let useDur = min(totalSec, bDur.seconds)
                if useDur > 0.2 {
                    try compBgmTrack.insertTimeRange(CMTimeRange(start: .zero, duration: CMTime(seconds: useDur, preferredTimescale: 600)), of: bTrack, at: .zero)
                    let bp = AVMutableAudioMixInputParameters(track: compBgmTrack)
                    bp.setVolume(0.20, at: .zero)
                    audioMixParams.append(bp)
                }
            }
        }

        let audioMix = AVMutableAudioMix()
        audioMix.inputParameters = audioMixParams

        guard let exporter = AVAssetExportSession(asset: composition, presetName: AVAssetExportPresetHighestQuality) else {
            fputs("Could not create AVAssetExportSession\n", stderr)
            exit(1)
        }
        exporter.outputURL = URL(fileURLWithPath: outputPath)
        exporter.outputFileType = .mp4
        exporter.audioMix = audioMix
        exporter.shouldOptimizeForNetworkUse = true

        await exporter.export()
        if exporter.status != .completed {
            fputs("Export failed: \(String(describing: exporter.error))\n", stderr)
            exit(1)
        }
        try? FileManager.default.removeItem(at: tempVideoURL)
        compSem.signal()
    } catch {
        fputs("Composition error: \(error)\n", stderr)
        exit(1)
    }
}
compSem.wait()
print("Rendered interactive video cleanly: \(outputPath)")
