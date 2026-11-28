---
title: SkripsiLab Document Converter
emoji: 📄
colorFrom: blue
colorTo: indigo
sdk: docker
app_port: 7860
---

# SkripsiLab 1:1 Document Converter (Gratis Unlimited)

Microservice ringan berbasis **LibreOffice Writer + `pdf2docx`** untuk konversi **Word (`.docx`) ⇄ PDF** 1:1 persis dokumen asli (margin, sampul, tabel, gambar, header/footer, nomor halaman).

## Cara Deploy Gratis di Hugging Face Spaces (Tanpa Kartu Kredit)
1. Buka https://huggingface.co/new-space → pilih **Docker (Blank)** → beri nama misal `skripsilab-converter` → pilih **Public** (atau Private) → klik **Create Space**.
2. Unggah 4 file dari folder `converter-service/` ini (`Dockerfile`, `requirements.txt`, `app.py`, `README.md`) ke tab **Files** di Space tersebut.
3. Setelah status Space berubah menjadi **Running**, tambahkan ke `.env` Laravel di hosting cPanel:
   ```env
   CONVERTER_SERVICE_URL=https://<username>-skripsilab-converter.hf.space
   CONVERTER_SERVICE_SECRET=
   ```
   *(Opsional: jika Anda menyetel Secret `CONVERTER_SECRET` di Settings Space, masukkan nilai yang sama ke `CONVERTER_SERVICE_SECRET` di `.env` Laravel.)*
