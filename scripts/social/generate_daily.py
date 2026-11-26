#!/usr/bin/env python3
"""
Generate daily IG, Facebook Page, TikTok & YouTube Shorts tutorial videos (9:16 30fps MP4)
and 5-slide tip carousels (4:5 PNG) for SkripsiLab AI using 100% free local tools:
- Live local Laravel capture logged in as "Winda" via Chrome + Puppeteer
- Microsoft Edge Neural TTS (id-ID-GadisNeural) + afconvert (natural Indonesian female voice)
- Native Swift AVFoundation 30fps compositor (mouse cursor glides + click ripples + live subtitles + BGM pad)
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
EDGE_TTS_BIN = "/Users/muhalwisyahrir/Library/Python/3.9/bin/edge-tts"


def load_json(path, default):
    if os.path.exists(path):
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)
    return default


def ensure_encoder_binary():
    if not os.path.exists(SWIFT_BIN) or os.path.getmtime(SWIFT_SRC) > os.path.getmtime(SWIFT_BIN):
        print("Compiling native Swift AVFoundation 30fps video compositor...")
        subprocess.run(
            ["swiftc", "-O", "-module-cache-path", "/tmp/swift-module-cache", SWIFT_SRC, "-o", SWIFT_BIN],
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
            return max(1.8, w.getnframes() / float(w.getframerate()))
    except Exception:
        return 3.5


def synthesize_voice(text, out_m4a):
    mp3_path = out_m4a.replace(".m4a", ".mp3")
    aiff_path = out_m4a.replace(".m4a", ".aiff")
    wav_path = out_m4a.replace(".m4a", ".wav")
    src_audio = None

    # Primary: Free Microsoft Edge Neural Indonesian Voice (id-ID-GadisNeural)
    tts_cmd = (
        [EDGE_TTS_BIN]
        if os.path.exists(EDGE_TTS_BIN)
        else [sys.executable, "-m", "edge_tts"]
    )
    res = subprocess.run(
        tts_cmd + ["--voice", "id-ID-GadisNeural", "--rate=+6%", "--text", text, "--write-media", mp3_path],
        capture_output=True,
        text=True,
    )
    if res.returncode == 0 and os.path.exists(mp3_path) and os.path.getsize(mp3_path) > 500:
        src_audio = mp3_path
    else:
        # Offline fallback: macOS Damayanti
        fb = subprocess.run(
            ["say", "-v", "Damayanti", "-r", "185", "-o", aiff_path, text],
            capture_output=True,
            text=True,
        )
        if fb.returncode == 0 and os.path.exists(aiff_path):
            src_audio = aiff_path

    if not src_audio:
        return False, 3.5

    subprocess.run(
        ["afconvert", "-f", "WAVE", "-d", "LEI16@44100", "-c", "1", src_audio, wav_path],
        check=True,
        capture_output=True,
    )
    dur = wav_duration(wav_path)
    subprocess.run(
        ["afconvert", "-f", "m4af", "-d", "aac", wav_path, out_m4a],
        check=True,
        capture_output=True,
    )
    for tmp in (mp3_path, aiff_path, wav_path):
        if os.path.exists(tmp):
            os.remove(tmp)
    return os.path.exists(out_m4a), dur


def generate_soft_pad_wav(out_wav, duration_sec):
    """Synthesize a clean, soft warm ambient chord pad (C-G-Am-F feel) 100% copyright-free."""
    sr = 44100
    total_samples = int(sr * (duration_sec + 1.0))
    chords = [
        [261.63, 329.63, 392.00, 493.88],  # Cmaj7
        [196.00, 293.66, 392.00, 493.88],  # G6
        [220.00, 261.63, 329.63, 392.00],  # Am7
        [174.61, 261.63, 349.23, 440.00],  # Fmaj7
    ]
    bar_sec = 2.6
    buf = bytearray()
    for i in range(total_samples):
        t = i / float(sr)
        chord = chords[int(t / bar_sec) % len(chords)]
        bar_pos = (t % bar_sec) / bar_sec
        env = math.sin(math.pi * bar_pos) ** 0.45
        fade_io = min(1.0, t / 0.8) * min(1.0, max(0.0, (duration_sec + 0.5 - t) / 1.0))
        val = 0.0
        for f in chord:
            val += math.sin(2.0 * math.pi * f * t)
        sample = int(max(-32767, min(32767, (val / len(chord)) * env * fade_io * 4800)))
        buf.extend(struct.pack("<h", sample))

    with wave.open(out_wav, "wb") as w:
        w.setnchannels(1)
        w.setsampwidth(2)
        w.setframerate(sr)
        w.writeframes(bytes(buf))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--day", type=int, default=None, help="Override topic day (1-6)")
    parser.add_argument("--date", type=str, default=None, help="Output date folder YYYY-MM-DD")
    args = parser.parse_args()

    ensure_encoder_binary()
    topics = load_json(TOPICS_FILE, [])
    if not topics:
        print("Error: topics.json is empty", file=sys.stderr)
        sys.exit(1)

    topic = pick_topic(topics, args.day)
    today = args.date or datetime.date.today().isoformat()
    out_dir = os.path.join(OUT_BASE, f"{today}_{topic['slug']}")
    os.makedirs(out_dir, exist_ok=True)

    topic_json_tmp = os.path.join(out_dir, "_topic.json")
    with open(topic_json_tmp, "w", encoding="utf-8") as f:
        json.dump(topic, f, ensure_ascii=False, indent=2)

    # 1. Capture live workspace & generate Carousel slides
    subprocess.run(["node", RENDER_SCRIPT, topic_json_tmp, out_dir], check=True)

    captures_meta = load_json(os.path.join(out_dir, "captures_meta.json"), {})
    scenes = topic.get("video_scenes", [])
    steps_bar = topic.get("steps_bar", ["1. Judul", "2. Proyek", "3. Bab I-V", "4. Jurnal", "5. PPT", "6. Sidang"])

    manifest_scenes = []
    temp_files = [topic_json_tmp, os.path.join(out_dir, "captures_meta.json")]
    total_dur = 0.0

    for idx, sc in enumerate(scenes):
        key = sc.get("capture_key", f"scene_{idx + 1}")
        cap = captures_meta.get(key, {})
        img_before = cap.get("before", "")
        img_after = cap.get("after")
        if img_before:
            temp_files.append(img_before)
        if img_after:
            temp_files.append(img_after)

        sub_beats = sc.get("sub_beats", [])
        if not sub_beats:
            sub_beats = [{"say": sc.get("voice", sc.get("subtitle", "")), "caption": sc.get("subtitle", "")}]

        sub_segments = []
        cursor_sec = 0.15
        for b_idx, beat in enumerate(sub_beats):
            m4a_path = os.path.join(out_dir, f"voice_{idx + 1}_{b_idx + 1}.m4a")
            ok, dur = synthesize_voice(beat["say"], m4a_path)
            seg_start = round(cursor_sec, 2)
            seg_end = round(cursor_sec + dur + 0.18, 2)
            sub_segments.append({
                "start": seg_start,
                "end": seg_end,
                "text": beat.get("caption", beat["say"]),
                "audio": m4a_path if ok else None,
            })
            if ok:
                temp_files.append(m4a_path)
            cursor_sec = seg_end + 0.12

        scene_dur = round(cursor_sec + 0.35, 2)
        total_dur += scene_dur

        manifest_scenes.append({
            "badge": sc.get("badge", topic.get("badge_top", "TUTORIAL SKRIPSILAB AI")),
            "title": sc.get("title", topic["hook"]),
            "subtitle": sc.get("subtitle", ""),
            "url": sc.get("url_display", "skripsilabai.com"),
            "steps": steps_bar,
            "active_step": sc.get("step_index", 0),
            "highlights": sc.get("highlights", []),
            "image_before": img_before,
            "image_after": img_after,
            "cursor_start": cap.get("cursor_start", [0.24, 0.78]),
            "cursor_target": cap.get("cursor_target", [0.74, 0.56]),
            "transition_mode": sc.get("transition_mode", "click"),
            "duration": scene_dur,
            "sub_segments": sub_segments,
        })

    bgm_wav = os.path.join(out_dir, "ambient_pad.wav")
    generate_soft_pad_wav(bgm_wav, total_dur)
    temp_files.append(bgm_wav)

    final_video = os.path.join(out_dir, "tutorial_video.mp4")
    manifest_obj = {
        "brand_name": "SkripsiLab AI",
        "domain": "skripsilabai.com",
        "logo": os.path.join(ROOT, "public/images/logo.png"),
        "bgm": bgm_wav,
        "output": final_video,
        "scenes": manifest_scenes,
    }
    manifest_path = os.path.join(out_dir, "manifest.json")
    with open(manifest_path, "w", encoding="utf-8") as f:
        json.dump(manifest_obj, f, ensure_ascii=False, indent=2)
    temp_files.append(manifest_path)

    subprocess.run([SWIFT_BIN, manifest_path], check=True)

    # Captions for IG, Facebook Page, TikTok & YouTube Shorts
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
        if tp and os.path.exists(tp):
            os.remove(tp)

    print(json.dumps({
        "status": "ok",
        "day": topic["day"],
        "topic": topic["slug"],
        "output_dir": out_dir,
        "video": final_video,
        "duration_sec": round(total_dur, 2),
        "slides": [os.path.join(out_dir, f"slide_{i + 1}.png") for i in range(len(topic.get("carousel_slides", [])))],
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
