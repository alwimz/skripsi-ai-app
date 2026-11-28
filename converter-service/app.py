import os
import shutil
import subprocess
import tempfile
from pathlib import Path
from typing import Optional

from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from fastapi.responses import Response
from pdf2docx import Converter

app = FastAPI(title="SkripsiLab 1:1 Document Converter", version="1.0.0")
SECRET = os.getenv("CONVERTER_SECRET", "").strip()
MAX_BYTES = 25 * 1024 * 1024  # 25 MB


def verify_secret(header_secret: Optional[str]) -> None:
    if SECRET and (header_secret or "").strip() != SECRET:
        raise HTTPException(status_code=403, detail="Unauthorized converter secret")


@app.get("/")
@app.get("/health")
def health():
    soffice = shutil.which("soffice") or shutil.which("libreoffice")
    return {
        "status": "ok",
        "engine": "libreoffice+pdf2docx",
        "libreoffice_ready": bool(soffice),
    }


@app.post("/convert/docx-to-pdf")
async def convert_docx_to_pdf(
    file: UploadFile = File(...),
    x_converter_secret: Optional[str] = Header(default=None),
):
    verify_secret(x_converter_secret)
    data = await file.read()
    if not data or len(data) > MAX_BYTES:
        raise HTTPException(status_code=422, detail="File kosong atau melebihi 25 MB.")

    soffice = shutil.which("soffice") or shutil.which("libreoffice")
    if not soffice:
        raise HTTPException(status_code=503, detail="LibreOffice tidak tersedia.")

    with tempfile.TemporaryDirectory(prefix="docx2pdf_") as tmp:
        in_path = Path(tmp) / "input.docx"
        profile_dir = Path(tmp) / "lo_profile"
        in_path.write_bytes(data)

        cmd = [
            soffice,
            "--headless",
            "--nologo",
            "--nodefault",
            "--norestore",
            f"-env:UserInstallation=file://{profile_dir}",
            "--convert-to",
            "pdf:writer_pdf_Export",
            "--outdir",
            tmp,
            str(in_path),
        ]
        try:
            proc = subprocess.run(cmd, capture_output=True, text=True, timeout=55)
        except subprocess.TimeoutExpired:
            raise HTTPException(status_code=504, detail="Konversi DOCX ke PDF timeout.")

        out_path = Path(tmp) / "input.pdf"
        if proc.returncode != 0 or not out_path.exists():
            raise HTTPException(
                status_code=500,
                detail=f"LibreOffice gagal mengonversi dokumen: {proc.stderr[:240]}",
            )
        pdf_bytes = out_path.read_bytes()

    return Response(content=pdf_bytes, media_type="application/pdf")


@app.post("/convert/pdf-to-docx")
async def convert_pdf_to_docx(
    file: UploadFile = File(...),
    x_converter_secret: Optional[str] = Header(default=None),
):
    verify_secret(x_converter_secret)
    data = await file.read()
    if not data or len(data) > MAX_BYTES:
        raise HTTPException(status_code=422, detail="File kosong atau melebihi 25 MB.")

    with tempfile.TemporaryDirectory(prefix="pdf2docx_") as tmp:
        in_path = Path(tmp) / "input.pdf"
        out_path = Path(tmp) / "output.docx"
        in_path.write_bytes(data)

        try:
            cv = Converter(str(in_path))
            cv.convert(str(out_path))
            cv.close()
        except Exception as exc:
            raise HTTPException(status_code=500, detail=f"Gagal konversi PDF ke DOCX: {str(exc)[:240]}")

        if not out_path.exists():
            raise HTTPException(status_code=500, detail="File hasil .docx tidak terbentuk.")
        docx_bytes = out_path.read_bytes()

    return Response(
        content=docx_bytes,
        media_type="application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    )
