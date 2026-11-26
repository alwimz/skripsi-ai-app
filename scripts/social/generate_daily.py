#!/usr/bin/env python3
"""
Generate daily Instagram & TikTok tutorial videos (9:16 MP4) and carousel tip images (4:5 PNG)
for SkripsiLab AI using 100% free local tools:
- Chrome + Puppeteer (UI & Carousel PNG rendering)
- macOS Damayanti TTS + afconvert (Indonesian Voiceover)
- Native Swift AVFoundation encoder (Hardware H.264 1080x1920 MP4 + soft copyright-free chord pad)
No paid API keys required.
"""
import argparse
import datetime
import json
import math
import os
import struct
import subprocess
import sys
import wave

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, "../.."))
TOPICS_FILE = os.path.join(SCRIPT_DIR, "topics.json")
RENDER_SCRIPT = os.path.join(SCRIPT_DIR, "render_content.mjs")
SWIFT_SRC = os.path.join(SCRIPT_DIR, "make_video.swift")
SWIFT_BIN = os.path.join(SCRIPT_DIR, "make_video")
OUT_BASE = os.path.join(ROOT, "storage/app/marketing")
POSTED_LOG = os.path.join(OUT_BASE, "posted_log.json")


def load_json(path, default):
    if os.path.exists(path):
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)
    return default


def ensure_encoder_binary():
    if not os.path.exists(SWIFT_BIN) or os.path.getmtime(SWIFT_SRC) > os.path.getmtime(SWIFT_BIN):
        print("Compiling native Swift AVFoundation video encoder...")
        subprocess.run(
            ["swiftc", "-O", SWIFT_SRC, "-o", SWIFT_BIN],
            check=True,
        )


def pick_topic(topics, day_arg=None):
    if day_arg is not None:
        for t in topics:
            if t["day"] == day_arg:
                return t
        return topics[(day_arg - 1) % len(topics)]

    log = load_json(POSTED_LOG, {"generated_slugs": []})
    done = set(log.get("generated_slugs", []))
    for t in topics:
        if t["slug"] not in done:
            return t
    return topics[len(done) % len(topics)]


def wav_duration(wav_path):
    try:
        with wave.open(wav_path, "rb") as w:
            return max(3.0, w.getnframes() / float(w.getframerate()))
    except Exception:
        return 5.0


def synthesize_voice(text, out_m4a):
    aiff_path = out_m4a.replace(".m4a", ".aiff")
    wav_path = out_m4a.replace(".m4a", ".wav")
    res = subprocess.run(
        ["say", "-v", "Damayanti", "-r", "185", "-o", aiff_path, text],
        capture_output=True,
        text=True,
    )
    if res.returncode != 0 or not os.path.exists(aiff_path):
        return False, 5.0
    # Convert to PCM WAV first to inspect exact duration cleanly
    subprocess.run(
        ["afconvert", "-f", "WAVE", "-d", "LEI16@44100", "-c", "1", aiff_path, wav_path],
        check=True,
        capture_output=True,
    )
    dur = wav_duration(wav_path)
    subprocess.run(
        ["afconvert", "-f", "m4af", "-d", "aac", wav_path, out_m4a],
        check=True,
        capture_output=True,
    )
    for tmp in (aiff_path, wav_path):
        if os.path.exists(tmp):
            os.remove(tmp)
    return os.path.exists(out_m4a), dur


def generate_soft_pad_wav(out_wav, duration_sec):
    """Synthesize a clean, soft warm ambient chord pad (C-G-Am-F feel) 100% copyright-free."""
    sr = 44100
    total_samples = int(sr * (duration_sec + 1.0))
    chords = [
        (261.63, 329.63, 392.00),  # C major
        (196.00, 293.66, 392.00),  # G5/G
        (220.00, 261.63, 329.63),  # Am
        (174.61, 261.63, 349.23),  # F
    ]
    chord_len = sr * 4
    frames = bytearray()
    for i in range(total_samples):
        t = i / sr
        c_idx = (i // chord_len) % len(chords)
        freqs = chords[c_idx]
        pos_in_chord = (i % chord_len) / float(chord_len)
        env = math.sin(math.pi * pos_in_chord) ** 0.5
        val = sum(math.sin(2.0 * math.pi * f * t) for f in freqs) / 3.0
        sample = int(max(-32767, min(32767, val * env * 14000)))
        frames.extend(struct.pack("<h", sample))

    with wave.open(out_wav, "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)
        wf.setframerate(sr)
        wf.writeframes(bytes(frames))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--day", type=int, default=None, help="Topic day index (1-based)")
    parser.add_argument("--date", type=str, default=None, help="Output date folder YYYY-MM-DD")
    args = parser.parse_args()

    ensure_encoder_binary()
    topics = load_json(TOPICS_FILE, [])
    if not topics:
        print("ERROR: topics.json is empty", file=sys.stderr)
        sys.exit(1)

    topic = pick_topic(topics, args.day)
    today = args.date or datetime.datetime.now().strftime("%Y-%m-%d")
    out_dir = os.path.join(OUT_BASE, f"{today}_{topic['slug']}")
    os.makedirs(out_dir, exist_ok=True)

    topic_json_tmp = os.path.join(out_dir, "topic.json")
    with open(topic_json_tmp, "w", encoding="utf-8") as f:
        json.dump(topic, f, ensure_ascii=False, indent=2)

    print(f"Rendering visuals for Day {topic['day']}: {topic['slug']} -> {out_dir}")
    subprocess.run(["node", RENDER_SCRIPT, topic_json_tmp, out_dir], check=True)

    scenes = topic.get("video_scenes", [])
    manifest = []
    temp_files = []
    total_dur = 0.0

    for idx, sc in enumerate(scenes, start=1):
        img_path = os.path.join(out_dir, f"scene_{idx}.png")
        m4a_path = os.path.join(out_dir, f"voice_{idx}.m4a")
        ok, dur = synthesize_voice(sc.get("voice", sc.get("subtitle", "")), m4a_path)
        scene_dur = round(dur + 0.45, 2)
        total_dur += scene_dur
        manifest.append({
            "image": img_path,
            "audio": m4a_path if ok else None,
            "duration": scene_dur,
        })
        temp_files.extend([img_path, m4a_path])

    manifest_path = os.path.join(out_dir, "manifest.json")
    with open(manifest_path, "w", encoding="utf-8") as f:
        json.dump(manifest, f, ensure_ascii=False, indent=2)

    bgm_wav = os.path.join(out_dir, "ambient_pad.wav")
    generate_soft_pad_wav(bgm_wav, total_dur)
    temp_files.extend([manifest_path, bgm_wav, topic_json_tmp])

    final_video = os.path.join(out_dir, "tutorial_video.mp4")
    subprocess.run([SWIFT_BIN, manifest_path, final_video, bgm_wav], check=True)

    # Save ready-to-copy captions for IG, TikTok, Facebook & YouTube Shorts
    with open(os.path.join(out_dir, "caption_ig.txt"), "w", encoding="utf-8") as f:
        f.write(f"{topic['caption_ig']}\n\n{topic['hashtags']}\n")
    with open(os.path.join(out_dir, "caption_tiktok.txt"), "w", encoding="utf-8") as f:
        f.write(f"{topic['caption_tiktok']} {topic['hashtags']}\n")
    with open(os.path.join(out_dir, "caption_fb.txt"), "w", encoding="utf-8") as f:
        f.write(f"{topic['caption_ig']}\n\n👉 Langsung coba di: {topic.get('feature_url', 'https://skripsilabai.com')}\n\n{topic['hashtags']}\n")
    with open(os.path.join(out_dir, "caption_youtube.txt"), "w", encoding="utf-8") as f:
        yt_title = f"{topic['hook']} #Shorts"
        if len(yt_title) > 95:
            yt_title = yt_title[:92].rstrip() + "..."
        f.write(
            f"JUDUL: {yt_title}\n\n"
            f"DESKRIPSI:\n{topic['subhook']}\n\n"
            f"Coba langsung: {topic.get('feature_url', 'https://skripsilabai.com')}\n\n"
            f"{topic['hashtags']} #Shorts\n"
        )

    log = load_json(POSTED_LOG, {"generated_slugs": [], "history": []})
    if topic["slug"] not in log["generated_slugs"]:
        log["generated_slugs"].append(topic["slug"])
    log["history"].append({
        "date": today,
        "day": topic["day"],
        "slug": topic["slug"],
        "output_dir": out_dir,
        "video": final_video,
    })
    with open(POSTED_LOG, "w", encoding="utf-8") as f:
        json.dump(log, f, ensure_ascii=False, indent=2)

    for tp in temp_files:
        if os.path.exists(tp):
            os.remove(tp)

    print(json.dumps({
        "status": "ok",
        "day": topic["day"],
        "topic": topic["slug"],
        "output_dir": out_dir,
        "video": final_video,
        "duration_sec": round(total_dur, 2),
        "slides": [os.path.join(out_dir, f"slide_{i+1}.png") for i in range(len(topic.get("carousel_slides", [])))],
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
