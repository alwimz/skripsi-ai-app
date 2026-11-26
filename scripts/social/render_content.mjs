import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const puppeteer = require('/opt/homebrew/lib/node_modules/puppeteer');

const [, , topicJsonPath, outputDir] = process.argv;
if (!topicJsonPath || !outputDir) {
  console.error('Usage: node render_content.mjs <topic.json> <outputDir>');
  process.exit(1);
}

const topic = JSON.parse(fs.readFileSync(topicJsonPath, 'utf8'));
fs.mkdirSync(outputDir, { recursive: true });

const logoPath = path.resolve('/opt/homebrew/var/www/skripsi Ai/public/images/logo.png');
const logoBase64 = fs.existsSync(logoPath)
  ? `data:image/png;base64,${fs.readFileSync(logoPath).toString('base64')}`
  : '';

const esc = (s = '') =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

function carouselHtml(slide, index, total) {
  const isCover = index === 0;
  const isEnd = index === total - 1;
  const bg = isCover
    ? 'linear-gradient(155deg, #0f172a 0%, #1e3a8a 58%, #2563eb 100%)'
    : isEnd
      ? 'linear-gradient(155deg, #1d4ed8 0%, #0f172a 100%)'
      : '#f8fafc';
  const textMain = isCover || isEnd ? '#ffffff' : '#0f172a';
  const textSub = isCover || isEnd ? '#dbeafe' : '#334155';
  const cardBg = isCover || isEnd ? 'rgba(255,255,255,0.10)' : '#ffffff';
  const cardBorder = isCover || isEnd ? 'rgba(255,255,255,0.22)' : '#dbeafe';
  const badgeBg = isCover || isEnd ? 'rgba(255,255,255,0.16)' : '#eff6ff';
  const badgeColor = isCover || isEnd ? '#ffffff' : '#1d4ed8';
  const highlightBg = isCover || isEnd ? '#facc15' : '#2563eb';
  const highlightColor = isCover || isEnd ? '#0f172a' : '#ffffff';

  const dots = Array.from({ length: total }, (_, i) => {
    const active = i === index;
    const c = isCover || isEnd ? (active ? '#ffffff' : 'rgba(255,255,255,0.3)') : active ? '#2563eb' : '#cbd5e1';
    return `<span style="width:${active ? 28 : 10}px;height:10px;border-radius:999px;background:${c};display:inline-block;"></span>`;
  }).join('');

  return `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    width: 1080px; height: 1350px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: ${bg};
    color: ${textMain};
    padding: 64px 68px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }
  .orb1 {
    position: absolute; top: -140px; right: -120px; width: 460px; height: 460px; border-radius: 50%;
    background: ${isCover || isEnd ? 'rgba(96,165,250,0.18)' : 'rgba(37,99,235,0.06)'};
  }
  .orb2 {
    position: absolute; bottom: -160px; left: -120px; width: 420px; height: 420px; border-radius: 50%;
    background: ${isCover || isEnd ? 'rgba(16,185,129,0.14)' : 'rgba(59,130,246,0.05)'};
  }
  .top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2; }
  .brand { display: flex; align-items: center; gap: 16px; }
  .brand img { width: 62px; height: 62px; object-fit: contain; background: #fff; border-radius: 16px; padding: 6px; box-shadow: 0 8px 20px rgba(15,23,42,0.14); }
  .brand-txt { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; }
  .brand-sub { font-size: 17px; opacity: 0.85; font-weight: 600; }
  .slide-num { font-size: 20px; font-weight: 800; padding: 10px 20px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor}; border: 1px solid ${cardBorder}; }
  .main { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 28px; }
  .badge { width: fit-content; font-size: 20px; font-weight: 800; letter-spacing: 0.05em; padding: 10px 22px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor}; border: 1.5px solid ${cardBorder}; }
  h1 { font-size: ${isCover ? 66 : 54}px; font-weight: 900; line-height: 1.15; letter-spacing: -0.03em; }
  .highlight-box {
    width: fit-content; max-width: 100%;
    background: ${highlightBg}; color: ${highlightColor};
    padding: 16px 26px; border-radius: 18px;
    font-size: 32px; font-weight: 800; line-height: 1.3;
    box-shadow: 0 12px 28px -10px rgba(15,23,42,0.28);
  }
  .body-card {
    background: ${cardBg}; border: 2px solid ${cardBorder};
    border-radius: 26px; padding: 36px 38px;
    font-size: 32px; line-height: 1.52; font-weight: 500; color: ${textSub};
    box-shadow: 0 18px 40px -24px rgba(15,23,42,0.2);
  }
  .foot { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 2px solid ${isCover || isEnd ? 'rgba(255,255,255,0.18)' : '#e2e8f0'}; }
  .foot-txt { font-size: 23px; font-weight: 700; color: ${textSub}; }
  .dots { display: flex; align-items: center; gap: 9px; }
</style></head>
<body>
  <div class="orb1"></div><div class="orb2"></div>
  <div class="top">
    <div class="brand">
      ${logoBase64 ? `<img src="${logoBase64}" alt="">` : ''}
      <div>
        <div class="brand-txt">SkripsiLab AI</div>
        <div class="brand-sub">@skripsilabai · skripsilabai.com</div>
      </div>
    </div>
    <div class="slide-num">${index + 1} / ${total}</div>
  </div>
  <div class="main">
    <div class="badge">${esc(slide.badge)}</div>
    <h1>${esc(slide.title)}</h1>
    <div class="highlight-box">${esc(slide.highlight)}</div>
    <div class="body-card">${esc(slide.body)}</div>
  </div>
  <div class="foot">
    <div class="foot-txt">${esc(slide.footer)}</div>
    <div class="dots">${dots}</div>
  </div>
</body></html>`;
}

function videoSceneHtml(scene, idx, total, screenshotBase64) {
  const isCta = scene.visual_type === 'cta';
  const bullets = Array.isArray(scene.bullets) ? scene.bullets : [];
  const bg = isCta
    ? 'linear-gradient(165deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%)'
    : 'linear-gradient(180deg, #eff6ff 0%, #f8fafc 45%, #ffffff 100%)';

  const progress = Math.round(((idx + 1) / total) * 100);
  const bulletHtml = bullets
    .map(
      (b, i) => `
      <div style="display:flex;align-items:flex-start;gap:18px;padding:24px 26px;background:#ffffff;border:2px solid #dbeafe;border-radius:20px;box-shadow:0 12px 26px -18px rgba(15,23,42,0.2);">
        <span style="min-width:44px;height:44px;border-radius:12px;background:#2563eb;color:#fff;font-size:22px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">${i + 1}</span>
        <span style="font-size:30px;font-weight:700;color:#0f172a;line-height:1.38;">${esc(b)}</span>
      </div>`
    )
    .join('');

  const centerVisual = screenshotBase64
    ? `<div style="background:#0f172a;padding:18px;border-radius:38px;box-shadow:0 28px 60px -18px rgba(15,23,42,0.45);border:4px solid #1e293b;width:820px;margin:0 auto;">
         <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:12px;">
           <span style="width:12px;height:12px;border-radius:50%;background:#ef4444;"></span>
           <span style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></span>
           <span style="width:12px;height:12px;border-radius:50%;background:#10b981;"></span>
           <span style="margin-left:12px;font-size:20px;color:#94a3b8;font-weight:700;">skripsilabai.com</span>
         </div>
         <img src="${screenshotBase64}" style="width:100%;height:780px;object-fit:cover;object-position:top center;border-radius:22px;display:block;border:1px solid #334155;">
       </div>`
    : `<div style="display:flex;flex-direction:column;gap:20px;width:100%;">${bulletHtml}</div>`;

  return `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    width: 1080px; height: 1920px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: ${bg};
    color: ${isCta ? '#ffffff' : '#0f172a'};
    padding: 86px 64px 120px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }
  .top-bar { display: flex; flex-direction: column; gap: 18px; }
  .brand-row { display: flex; align-items: center; justify-content: space-between; }
  .brand { display: flex; align-items: center; gap: 14px; }
  .brand img { width: 64px; height: 64px; border-radius: 16px; background: #fff; padding: 6px; object-fit: contain; box-shadow: 0 8px 20px rgba(15,23,42,0.14); }
  .brand-name { font-size: 28px; font-weight: 900; }
  .step-pill {
    font-size: 22px; font-weight: 900; letter-spacing: 0.06em;
    padding: 10px 24px; border-radius: 999px;
    background: ${isCta ? '#facc15' : '#2563eb'};
    color: ${isCta ? '#0f172a' : '#ffffff'};
  }
  .prog-track { width: 100%; height: 10px; border-radius: 999px; background: ${isCta ? 'rgba(255,255,255,0.2)' : '#dbeafe'}; overflow: hidden; }
  .prog-fill { width: ${progress}%; height: 100%; background: ${isCta ? '#4ade80' : 'linear-gradient(90deg, #2563eb, #10b981)'}; }
  .hero-block { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 18px; }
  h1 { font-size: 56px; font-weight: 900; line-height: 1.15; letter-spacing: -0.025em; }
  .sub { font-size: 30px; font-weight: 600; color: ${isCta ? '#dbeafe' : '#475569'}; line-height: 1.4; max-width: 900px; }
  .sub-caption {
    background: rgba(15, 23, 42, 0.92);
    color: #ffffff;
    border: 2px solid rgba(255,255,255,0.22);
    border-radius: 24px;
    padding: 26px 32px;
    text-align: center;
    box-shadow: 0 18px 40px rgba(15,23,42,0.28);
  }
  .cap-text { font-size: 32px; font-weight: 800; line-height: 1.38; color: #facc15; }
  .cap-url { font-size: 22px; font-weight: 700; color: #cbd5e1; margin-top: 8px; }
</style></head>
<body>
  <div class="top-bar">
    <div class="brand-row">
      <div class="brand">
        ${logoBase64 ? `<img src="${logoBase64}" alt="">` : ''}
        <div class="brand-name">SkripsiLab AI</div>
      </div>
      <div class="step-pill">${esc(scene.step)}</div>
    </div>
    <div class="prog-track"><div class="prog-fill"></div></div>
  </div>

  <div class="hero-block">
    <h1>${esc(scene.title)}</h1>
    <div class="sub">${esc(scene.subtitle)}</div>
  </div>

  <div>${centerVisual}</div>

  <div class="sub-caption">
    <div class="cap-text">${esc(scene.subtitle)}</div>
    <div class="cap-url">skripsilabai.com · @skripsilabai</div>
  </div>
</body></html>`;
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const page = await browser.newPage();

    // 1. Render 5 Carousel Tip Images (1080x1350)
    await page.setViewport({ width: 1080, height: 1350, deviceScaleFactor: 1 });
    const slides = topic.carousel_slides || [];
    for (let i = 0; i < slides.length; i++) {
      await page.setContent(carouselHtml(slides[i], i, slides.length), { waitUntil: 'load' });
      const outPath = path.join(outputDir, `slide_${i + 1}.png`);
      await page.screenshot({ path: outPath, type: 'png' });
      console.log(`Rendered ${outPath}`);
    }

    // 2. Render Video Tutorial Scenes (1080x1920)
    const scenes = topic.video_scenes || [];
    for (let i = 0; i < scenes.length; i++) {
      const sc = scenes[i];
      let screenshotBase64 = '';
      if (sc.visual_type === 'live_site' && sc.url) {
        try {
          await page.setViewport({ width: 540, height: 760, deviceScaleFactor: 2 });
          await page.goto(sc.url, { waitUntil: 'domcontentloaded', timeout: 20000 });
          await new Promise((r) => setTimeout(r, 1200));
          if (sc.scroll_y) {
            await page.evaluate((y) => window.scrollTo(0, y), sc.scroll_y);
            await new Promise((r) => setTimeout(r, 400));
          }
          const buf = await page.screenshot({ type: 'png' });
          screenshotBase64 = `data:image/png;base64,${buf.toString('base64')}`;
        } catch (err) {
          console.warn(`Live capture fallback for ${sc.url}: ${err.message}`);
        }
      }
      await page.setViewport({ width: 1080, height: 1920, deviceScaleFactor: 1 });
      await page.setContent(videoSceneHtml(sc, i, scenes.length, screenshotBase64), { waitUntil: 'load' });
      const scenePath = path.join(outputDir, `scene_${i + 1}.png`);
      await page.screenshot({ path: scenePath, type: 'png' });
      console.log(`Rendered ${scenePath}`);
    }
  } finally {
    await browser.close();
  }
})();
