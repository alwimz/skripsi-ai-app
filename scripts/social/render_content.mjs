import fs from 'node:fs';
import path from 'node:path';
import { execFileSync, spawn } from 'node:child_process';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const puppeteer = require('/opt/homebrew/lib/node_modules/puppeteer');

const [, , topicJsonPath, outputDir] = process.argv;
if (!topicJsonPath || !outputDir) {
  console.error('Usage: node render_content.mjs <topic.json> <outputDir>');
  process.exit(1);
}

const ROOT = '/opt/homebrew/var/www/skripsi Ai';
const PORT = 18923;
const BASE_URL = `http://127.0.0.1:${PORT}`;

const topic = JSON.parse(fs.readFileSync(topicJsonPath, 'utf8'));
fs.mkdirSync(outputDir, { recursive: true });

const logoPath = path.join(ROOT, 'public/images/logo.png');
const logoBase64 = fs.existsSync(logoPath)
  ? `data:image/png;base64,${fs.readFileSync(logoPath).toString('base64')}`
  : '';

const esc = (s = '') =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForServer(url, timeoutMs = 15000) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    try {
      const res = await fetch(url);
      if (res.ok || res.status < 500) return true;
    } catch {}
    await wait(250);
  }
  throw new Error(`Laravel dev server failed to respond at ${url}`);
}

async function hideFloatingWidgets(page) {
  await page.evaluate(() => {
    const selectors = [
      '#help-widget',
      '.help-widget-btn',
      '.help-fab',
      '[aria-label*="Bantuan"]',
      '.floating-help',
      '#wa-float',
    ];
    for (const sel of selectors) {
      document.querySelectorAll(sel).forEach((el) => {
        el.style.display = 'none';
      });
    }
    // Also hide fixed bottom-right helper pill if any
    document.querySelectorAll('div, button, a').forEach((el) => {
      const txt = (el.textContent || '').trim();
      if (txt === 'Butuh Bantuan?' || txt.includes('Butuh Bantuan?')) {
        const container = el.closest('div[style*="position: fixed"], div[class*="fixed"]') || el;
        container.style.display = 'none';
      }
    });
  });
}

async function measureNormalizedCenter(page, selector) {
  const rect = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    if (!r.width || !r.height) return null;
    return {
      x: (r.left + r.width * 0.52) / window.innerWidth,
      y: (r.top + r.height * 0.55) / window.innerHeight,
    };
  }, selector);
  if (!rect) return [0.72, 0.58];
  return [
    Math.min(0.95, Math.max(0.05, Number(rect.x.toFixed(3)))),
    Math.min(0.95, Math.max(0.05, Number(rect.y.toFixed(3)))),
  ];
}

async function loginAsWinda(page, email, password) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  await wait(300);
  const hasEmail = await page.$('input[name="email"]');
  if (hasEmail) {
    await page.type('input[name="email"]', email, { delay: 5 });
    await page.type('input[name="password"]', password, { delay: 5 });
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }),
      page.click('button[type="submit"]'),
    ]);
    await wait(500);
  }
}

async function installMockInterceptors(page) {
  await page.setRequestInterception(true);
  page.on('request', (req) => {
    const url = req.url();
    const method = req.method();
    if (method === 'POST' && url.includes('/demo/cari-judul')) {
      return req.respond({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          rumusan:
            'Bagaimana tingkat akurasi algoritma Multinomial Naive Bayes dengan pembobotan TF-IDF n-gram dalam mengklasifikasikan sentimen komentar berbahasa Indonesia pada platform TikTok?',
          titles: [
            'Klasifikasi Sentimen Komentar Pengguna TikTok Menggunakan Algoritma Multinomial Naive Bayes dan Pembobotan TF-IDF',
            'Analisis Perbandingan Performa TF-IDF Unigram-Bigram dan Support Vector Machine pada Opini Publik TikTok Live Commerce',
            'Rancang Bangun Dashboard Pemantauan Sentimen Otomatis Komentar TikTok Berbasis Naive Bayes dan Kamus Normalisasi Slang',
          ],
        }),
      });
    }
    if (method === 'POST' && url.includes('/tools/find-journals')) {
      return req.respond({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: [
            {
              scope: 'Indonesia',
              title: 'Klasifikasi Sentimen Komentar Media Sosial Berbahasa Indonesia Menggunakan TF-IDF dan Naive Bayes',
              author: 'Rahmawati, D., Firdaus, M., & Prakoso, T.',
              year: '2024',
              journal: 'Jurnal Nasional Teknik Elektro dan Teknologi Informasi (JNTETI · SINTA 2)',
              doi_url: 'https://doi.org/10.22146/jnteti.v13i2.8412',
              pdf_url: 'https://doi.org/10.22146/jnteti.v13i2.8412',
              citation: 'Rahmawati, D., Firdaus, M., & Prakoso, T. (2024). Klasifikasi Sentimen Komentar Media Sosial Berbahasa Indonesia Menggunakan TF-IDF dan Naive Bayes. JNTETI, 13(2), 112–120.',
            },
            {
              scope: 'Internasional',
              title: 'Comparative Sentiment Classification on Short-Form Video Comments Using Enhanced TF-IDF and Multinomial Naive Bayes',
              author: 'Putra, B. H., & Wibowo, A.',
              year: '2024',
              journal: 'IEEE Access · Scopus Q1',
              doi_url: 'https://doi.org/10.1109/ACCESS.2024.3389104',
              pdf_url: 'https://doi.org/10.1109/ACCESS.2024.3389104',
              citation: 'Putra, B. H., & Wibowo, A. (2024). Comparative Sentiment Classification on Short-Form Video Comments Using Enhanced TF-IDF and Multinomial Naive Bayes. IEEE Access, 12, 41205–41216.',
            },
            {
              scope: 'Indonesia',
              title: 'Normalisasi Kata Tidak Baku dan Ekstraksi Fitur N-Gram pada Klasifikasi Sentimen Komentar TikTok',
              author: 'Sari, N. P., & Pratama, Y.',
              year: '2023',
              journal: 'Jurnal RESTI (Rekayasa Sistem dan Teknologi Informasi · SINTA 2)',
              doi_url: 'https://doi.org/10.29207/resti.v7i4.5102',
              pdf_url: 'https://doi.org/10.29207/resti.v7i4.5102',
              citation: 'Sari, N. P., & Pratama, Y. (2023). Normalisasi Kata Tidak Baku dan Ekstraksi Fitur N-Gram pada Klasifikasi Sentimen Komentar TikTok. Jurnal RESTI, 7(4), 891–900.',
            },
          ],
        }),
      });
    }
    if (method === 'POST' && url.includes('/tools/generate-titles')) {
      return req.respond({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          data: [
            'Klasifikasi Sentimen Komentar Pengguna TikTok Menggunakan Algoritma Multinomial Naive Bayes dan Pembobotan TF-IDF',
            'Implementasi Kamus Normalisasi Slang Bahasa Indonesia pada Model Analisis Sentimen Komentar TikTok Shop',
            'Evaluasi Perbandingan Kinerja Naive Bayes dan Support Vector Machine dalam Pemetaan Opini Publik Media Sosial',
          ],
        }),
      });
    }
    return req.continue();
  });
}

async function captureScene(page, scene, index, projectId, baseDir) {
  const key = scene.capture_key || `scene_${index + 1}`;
  const mode = scene.capture_mode;
  const beforePath = path.join(baseDir, `${key}_before.png`);
  const afterPath = path.join(baseDir, `${key}_after.png`);

  await page.setViewport({ width: 1180, height: 924, deviceScaleFactor: 2 });

  let cursorStart = [0.24, 0.78];
  let cursorTarget = [0.76, 0.56];
  let hasAfter = true;

  if (mode === 'landing_coba_judul' || mode === 'landing_coba_judul_result') {
    await page.goto(`${BASE_URL}/#coba-judul`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(500);
    await hideFloatingWidgets(page);
    await page.evaluate(() => {
      document.getElementById('coba-judul')?.scrollIntoView({ behavior: 'instant', block: 'center' });
    });
    await wait(300);

    await page.$eval('#demo-jurusan', (el) => { el.value = 'Teknik Informatika'; }).catch(() => {});
    await page.$eval('#demo-prodi', (el) => { el.value = 'Teknik Informatika'; }).catch(() => {});
    await page.$eval('#demo-topik', (el) => { el.value = 'Klasifikasi sentimen komentar TikTok dengan Naive Bayes & TF-IDF'; });

    if (mode === 'landing_coba_judul') {
      cursorTarget = await measureNormalizedCenter(page, '#demo-submit-btn');
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#demo-submit-btn');
      await wait(550);
      await page.evaluate(() => {
        document.getElementById('demo-results-box')?.scrollIntoView({ behavior: 'instant', block: 'nearest' });
      });
      await wait(250);
      await page.screenshot({ path: afterPath, type: 'png' });
    } else {
      await page.click('#demo-submit-btn');
      await wait(550);
      cursorTarget = await measureNormalizedCenter(page, '#demo-results-box .demo-result-actions a');
      await page.screenshot({ path: beforePath, type: 'png' });
      // Transition to Dashboard Buat Proyek as Winda
      await page.goto(`${BASE_URL}/app/create?tab=projects&create=1&prefill_judul=${encodeURIComponent('Klasifikasi Sentimen Komentar Pengguna TikTok Menggunakan Algoritma Multinomial Naive Bayes dan Pembobotan TF-IDF')}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
      await wait(600);
      await hideFloatingWidgets(page);
      await page.screenshot({ path: afterPath, type: 'png' });
    }
  } else if (mode === 'workspace_dashboard_titles') {
    await page.goto(`${BASE_URL}/app/create?tab=dashboard`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(600);
    await hideFloatingWidgets(page);
    cursorTarget = await measureNormalizedCenter(page, '#nav-titles');
    await page.screenshot({ path: beforePath, type: 'png' });

    await page.click('#nav-titles');
    await wait(450);
    await page.$eval('#title-topik', (el) => { el.value = 'Analisis sentimen komentar TikTok Naive Bayes'; }).catch(() => {});
    await page.$eval('#title-prodi', (el) => { el.value = 'Teknik Informatika'; }).catch(() => {});
    await page.$eval('#title-metode', (el) => {
      if (el.options && el.options.length > 1) el.selectedIndex = 1;
    }).catch(() => {});
    await page.click('#pane-titles button[type="button"].btn-primary').catch(() => {});
    await page.waitForSelector('#titles-result-box', { visible: true, timeout: 5000 }).catch(() => {});
    await wait(300);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'workspace_titles') {
    await page.goto(`${BASE_URL}/app/create?tab=titles`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(550);
    await hideFloatingWidgets(page);
    await page.$eval('#title-topik', (el) => { el.value = 'Analisis sentimen komentar TikTok Naive Bayes'; }).catch(() => {});
    await page.$eval('#title-prodi', (el) => { el.value = 'Teknik Informatika'; }).catch(() => {});
    await page.$eval('#title-metode', (el) => {
      if (el.options && el.options.length > 1) el.selectedIndex = 1;
    }).catch(() => {});
    cursorTarget = await measureNormalizedCenter(page, '#pane-titles .btn-primary');
    await page.screenshot({ path: beforePath, type: 'png' });
    await page.click('#pane-titles .btn-primary').catch(() => {});
    await page.waitForSelector('#titles-result-box', { visible: true, timeout: 5000 }).catch(() => {});
    await wait(300);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'workspace_journals' || mode === 'workspace_journals_results') {
    await page.goto(`${BASE_URL}/app/create?tab=journals`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(550);
    await hideFloatingWidgets(page);
    await page.$eval('#journal-keyword', (el) => { el.value = 'analisis sentimen tiktok naive bayes tf-idf'; }).catch(() => {});
    cursorTarget = await measureNormalizedCenter(page, '#btn-find-journals');
    if (mode === 'workspace_journals') {
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#btn-find-journals');
      await page.waitForSelector('#journals-result-box', { visible: true, timeout: 5000 }).catch(() => {});
      await wait(300);
      await page.screenshot({ path: afterPath, type: 'png' });
    } else {
      await page.click('#btn-find-journals');
      await page.waitForSelector('#journals-result-box', { visible: true, timeout: 5000 }).catch(() => {});
      await wait(300);
      cursorTarget = await measureNormalizedCenter(page, '#journals-list button, #journals-list a');
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.evaluate(() => {
        document.getElementById('journals-result-box')?.scrollIntoView({ behavior: 'instant', block: 'start' });
      });
      await wait(250);
      await page.screenshot({ path: afterPath, type: 'png' });
    }
  } else if (mode === 'workspace_qa') {
    await page.goto(`${BASE_URL}/app/create?tab=qa`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(550);
    await hideFloatingWidgets(page);
    await page.$eval('#qa-question', (el) => { el.value = 'Apakah pembobotan TF-IDF unigram-bigram sudah tepat untuk komentar TikTok yang banyak bahasa gaul?'; }).catch(() => {});
    cursorTarget = await measureNormalizedCenter(page, '#pane-qa button[onclick*="send"]');
    await page.screenshot({ path: beforePath, type: 'png' });
    // Inject clean realistic Dosen AI chat preview bubble so screen shows answer
    await page.evaluate(() => {
      const box = document.getElementById('qa-chat-messages');
      if (box) {
        box.innerHTML += `
          <div style="margin:12px 0;display:flex;justify-content:flex-end;">
            <div style="background:#2563eb;color:#fff;padding:12px 16px;border-radius:14px;max-width:78%;font-size:13.5px;line-height:1.5;">Apakah TF-IDF unigram-bigram sudah tepat untuk komentar TikTok berbahasa gaul, Prof?</div>
          </div>
          <div style="margin:12px 0;display:flex;justify-content:flex-start;">
            <div style="background:#f8fafc;border:1px solid #dbeafe;color:#0f172a;padding:14px 18px;border-radius:14px;max-width:85%;font-size:13.5px;line-height:1.55;">
              <strong style="color:#1d4ed8;">Dosen AI Pembimbing:</strong><br>
              Sudah sangat tepat Winda! Pastikan di <strong>Bab III</strong> kamu menambahkan tahap <em>Normalisasi Kamus Slang</em> sebelum TF-IDF agar kata tidak baku terpetakan konsisten, lalu bandingkan dengan skenario baseline di <strong>Bab IV</strong> untuk membuktikan peningkatan akurasinya.
            </div>
          </div>`;
      }
    });
    await wait(250);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'workspace_ppt') {
    await page.goto(`${BASE_URL}/app/create?tab=ppt`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(600);
    await hideFloatingWidgets(page);
    cursorTarget = await measureNormalizedCenter(page, '#ppt-view-step-1 .project-row, #pane-ppt button');
    await page.screenshot({ path: beforePath, type: 'png' });
    // Click saved PPT history if present or step 2
    await page.evaluate(() => {
      const savedCard = document.querySelector('#ppt-saved-list .project-row button, [onclick*="loadSavedPpt"]');
      if (savedCard) savedCard.click();
    });
    await wait(600);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'workspace_defense') {
    await page.goto(`${BASE_URL}/app/create?tab=defense`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(600);
    await hideFloatingWidgets(page);
    cursorTarget = await measureNormalizedCenter(page, '#pane-defense button.btn-primary');
    await page.screenshot({ path: beforePath, type: 'png' });
    // Scroll down or highlight question bank card cleanly
    await page.evaluate(() => window.scrollBy(0, 140));
    await wait(300);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'workspace_olahdata_paraphrase') {
    await page.goto(`${BASE_URL}/app/create?tab=olahdata`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(600);
    await hideFloatingWidgets(page);
    cursorTarget = await measureNormalizedCenter(page, '#nav-paraphrase');
    await page.screenshot({ path: beforePath, type: 'png' });
    await page.click('#nav-paraphrase');
    await wait(450);
    await page.$eval('#para-input', (el) => { el.value = 'Penelitian ini memakai data komentar TikTok sebanyak 5000 komentar terus dibersihkan sebelum dihitung akurasinya.'; }).catch(() => {});
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode === 'tools_hub') {
    await page.goto(`${BASE_URL}/tools`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(600);
    await hideFloatingWidgets(page);
    cursorTarget = await measureNormalizedCenter(page, '.tool-card, button');
    await page.screenshot({ path: beforePath, type: 'png' });
    await page.evaluate(() => window.scrollBy(0, 320));
    await wait(300);
    await page.screenshot({ path: afterPath, type: 'png' });
  } else if (mode.startsWith('project_')) {
    await page.goto(`${BASE_URL}/project/${projectId}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(900);
    await hideFloatingWidgets(page);

    if (mode === 'project_sampul_bab1') {
      await page.click('#nav-item-front').catch(() => {});
      await wait(450);
      await page.evaluate(() => {
        const scroller = document.querySelector('#main-scroll, .preview-area, #sheets-container')?.parentElement;
        if (scroller) scroller.scrollTop = 140;
      });
      await wait(250);
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#nav-item-bab1').catch(() => {});
      await wait(450);
      await page.screenshot({ path: afterPath, type: 'png' });
    } else if (mode === 'project_daftar_isi') {
      await page.click('#nav-item-front').catch(() => {});
      await wait(450);
      await page.evaluate(() => {
        const container = document.getElementById('sheets-container') || document.querySelector('.workspace-center, .a4-viewport');
        if (container) container.scrollTop = 980;
      });
      await wait(300);
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#nav-item-bab1').catch(() => {});
      await wait(450);
      await page.screenshot({ path: afterPath, type: 'png' });
    } else if (mode === 'project_bab4_export') {
      await page.click('#nav-item-bab4').catch(() => {});
      await wait(450);
      cursorTarget = await measureNormalizedCenter(page, 'a[href*="/download/docx"], button[onclick*="downloadDocx"], #btn-download-docx');
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#nav-item-daftar_pustaka').catch(() => {});
      await wait(450);
      await page.screenshot({ path: afterPath, type: 'png' });
    } else if (mode === 'project_daftar_pustaka') {
      await page.click('#nav-item-bab1').catch(() => {});
      await wait(400);
      await page.screenshot({ path: beforePath, type: 'png' });
      await page.click('#nav-item-daftar_pustaka').catch(() => {});
      await wait(450);
      await page.screenshot({ path: afterPath, type: 'png' });
    }
  } else {
    hasAfter = false;
    await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await wait(500);
    await hideFloatingWidgets(page);
    await page.screenshot({ path: beforePath, type: 'png' });
  }

  const out = {
    before: beforePath,
    after: hasAfter && fs.existsSync(afterPath) ? afterPath : null,
    cursor_start: cursorStart,
    cursor_target: cursorTarget,
  };
  return out;
}

function carouselHtml(slide, index, total, captureDataUri) {
  const isCover = index === 0;
  const isEnd = index === total - 1;
  const bg = isCover || isEnd
    ? 'linear-gradient(155deg, #0b1324 0%, #172554 55%, #1d4ed8 100%)'
    : '#f8fafc';
  const textMain = isCover || isEnd ? '#ffffff' : '#0f172a';
  const textSub = isCover || isEnd ? '#dbeafe' : '#334155';
  const badgeBg = isCover || isEnd ? 'rgba(255,255,255,0.16)' : '#eff6ff';
  const badgeColor = isCover || isEnd ? '#ffffff' : '#1d4ed8';
  const cardBorder = isCover || isEnd ? 'rgba(255,255,255,0.22)' : '#dbeafe';
  const highlightBg = isCover || isEnd ? '#facc15' : '#2563eb';
  const highlightColor = isCover || isEnd ? '#0f172a' : '#ffffff';

  const dots = Array.from({ length: total }, (_, i) => {
    const active = i === index;
    const c = isCover || isEnd ? (active ? '#ffffff' : 'rgba(255,255,255,0.3)') : active ? '#2563eb' : '#cbd5e1';
    return `<span style="width:${active ? 26 : 10}px;height:10px;border-radius:999px;background:${c};display:inline-block;"></span>`;
  }).join('');

  const browserMock = captureDataUri
    ? `<div style="background:#ffffff;border-radius:20px;overflow:hidden;border:2px solid ${isCover || isEnd ? 'rgba(255,255,255,0.28)' : '#cbd5e1'};box-shadow:0 22px 44px -16px rgba(15,23,42,0.38);">
         <div style="background:#f1f5f9;padding:10px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #e2e8f0;">
           <span style="width:11px;height:11px;border-radius:50%;background:#ef4444;"></span>
           <span style="width:11px;height:11px;border-radius:50%;background:#f59e0b;"></span>
           <span style="width:11px;height:11px;border-radius:50%;background:#22c55e;"></span>
           <span style="margin-left:10px;font-size:15px;font-weight:700;color:#64748b;">skripsilabai.com</span>
         </div>
         <img src="${captureDataUri}" style="width:100%;height:420px;object-fit:cover;object-position:top center;display:block;">
       </div>`
    : '';

  return `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    width: 1080px; height: 1350px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: ${bg};
    color: ${textMain};
    padding: 54px 62px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }
  .top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2; }
  .brand { display: flex; align-items: center; gap: 14px; }
  .brand img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 14px; padding: 5px; box-shadow: 0 8px 20px rgba(15,23,42,0.14); }
  .brand-txt { font-size: 24px; font-weight: 800; }
  .brand-sub { font-size: 16px; opacity: 0.85; font-weight: 600; }
  .slide-num { font-size: 19px; font-weight: 800; padding: 8px 18px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor}; border: 1px solid ${cardBorder}; }
  .main { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 20px; }
  .badge { width: fit-content; font-size: 18px; font-weight: 800; letter-spacing: 0.05em; padding: 8px 20px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor}; border: 1.5px solid ${cardBorder}; }
  h1 { font-size: ${isCover ? 52 : 44}px; font-weight: 900; line-height: 1.15; letter-spacing: -0.025em; }
  .highlight-box {
    width: fit-content; max-width: 100%;
    background: ${highlightBg}; color: ${highlightColor};
    padding: 13px 22px; border-radius: 16px;
    font-size: 26px; font-weight: 800; line-height: 1.3;
  }
  .body-txt {
    font-size: 26px; line-height: 1.48; font-weight: 500; color: ${textSub};
  }
  .foot { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; padding-top: 20px; border-top: 2px solid ${isCover || isEnd ? 'rgba(255,255,255,0.18)' : '#e2e8f0'}; }
  .foot-txt { font-size: 22px; font-weight: 700; color: ${textSub}; }
  .dots { display: flex; align-items: center; gap: 9px; }
</style></head>
<body>
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
    ${browserMock}
    <div class="body-txt">${esc(slide.body)}</div>
  </div>
  <div class="foot">
    <div class="foot-txt">${esc(slide.footer)}</div>
    <div class="dots">${dots}</div>
  </div>
</body></html>`;
}

(async () => {
  const setupRaw = execFileSync('php', [path.join(ROOT, 'scripts/social/setup_demo_db.php')], {
    encoding: 'utf8',
  });
  const demoDb = JSON.parse(setupRaw);

  const serverProc = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
    cwd: ROOT,
    env: {
      ...process.env,
      DB_CONNECTION: 'sqlite',
      DB_DATABASE: demoDb.db_path,
      APP_ENV: 'local',
    },
    stdio: 'ignore',
  });

  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    await waitForServer(`${BASE_URL}/`);

    const page = await browser.newPage();
    await installMockInterceptors(page);
    await loginAsWinda(page, demoDb.email, demoDb.password);

    const capturesMeta = {};
    const captureUriMap = {};

    const scenes = topic.video_scenes || [];
    for (let i = 0; i < scenes.length; i++) {
      const sc = scenes[i];
      const key = sc.capture_key || `scene_${i + 1}`;
      const info = await captureScene(page, sc, i, demoDb.project_id, outputDir);
      capturesMeta[key] = info;
      if (info.before && fs.existsSync(info.before)) {
        captureUriMap[`${key}_before`] = `data:image/png;base64,${fs.readFileSync(info.before).toString('base64')}`;
      }
      if (info.after && fs.existsSync(info.after)) {
        captureUriMap[`${key}_after`] = `data:image/png;base64,${fs.readFileSync(info.after).toString('base64')}`;
      }
      console.log(`Captured real live UI for ${key} (${sc.capture_mode})`);
    }

    // Fallback extra captures if carousel references another key
    const slides = topic.carousel_slides || [];
    const carouselPage = await browser.newPage();
    await carouselPage.setViewport({ width: 1080, height: 1350, deviceScaleFactor: 1 });
    for (let i = 0; i < slides.length; i++) {
      const s = slides[i];
      const imgUri =
        (s.capture_key && (captureUriMap[s.capture_key] || captureUriMap[`${s.capture_key}_after`] || captureUriMap[`${s.capture_key}_before`])) ||
        Object.values(captureUriMap)[0] ||
        '';
      await carouselPage.setContent(carouselHtml(s, i, slides.length, imgUri), { waitUntil: 'domcontentloaded' });
      const outPath = path.join(outputDir, `slide_${i + 1}.png`);
      await carouselPage.screenshot({ path: outPath, type: 'png' });
      console.log(`Rendered Carousel ${outPath}`);
    }

    fs.writeFileSync(path.join(outputDir, 'captures_meta.json'), JSON.stringify(capturesMeta, null, 2));
  } finally {
    await browser.close().catch(() => {});
    serverProc.kill('SIGTERM');
  }
})();
