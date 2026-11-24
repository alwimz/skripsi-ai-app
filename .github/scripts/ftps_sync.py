#!/usr/bin/env python3
"""
Incremental FTPS/FTP deployer tuned for cPanel Pure-FTPd servers.

Forces TLSv1.2 with TLS session reuse on passive data channels to avoid
OpenSSL 3 / TLS 1.3 data-socket decode errors (SSL alert number 50), skips
0-byte files, checkpoints state incrementally, and optionally clears remote
compiled Laravel Blade views after deployment.
"""

from __future__ import annotations

import argparse
import fnmatch
import ftplib
import hashlib
import io
import ipaddress
import json
import os
import posixpath
import ssl
import sys
import time
from dataclasses import dataclass, field
from typing import Callable, Dict, Iterable, List, Optional, Set, Tuple, TypeVar

T = TypeVar("T")

TRANSIENT_ERRORS = (OSError, ssl.SSLError, EOFError, ftplib.error_temp)
PROTECTED_EXACT = {".env", "error_log", ".user.ini", ".htaccess_backup"}
PROTECTED_PREFIXES = (
    "vendor/",
    "storage/logs/",
    "storage/framework/sessions/",
    "storage/framework/cache/",
    ".well-known/",
    "cgi-bin/",
)


class PureFTPTLS(ftplib.FTP_TLS):
    """FTP_TLS subclass enforcing TLS session reuse on data channels."""

    def _sni_hostname(self) -> Optional[str]:
        if not self.host:
            return None
        try:
            ipaddress.ip_address(self.host)
            return None
        except ValueError:
            return self.host

    def auth(self) -> str:
        """Secure the control socket using explicit AUTH TLS."""
        if isinstance(self.sock, ssl.SSLSocket):
            raise ValueError("Already using TLS")
        resp = self.voidcmd("AUTH TLS")
        self.sock = self.context.wrap_socket(
            self.sock,
            server_hostname=self._sni_hostname(),
        )
        self.file = self.sock.makefile(mode="r", encoding=self.encoding)
        return resp

    def ntransfercmd(self, cmd: str, rest: Optional[int] = None):
        """Wrap raw data socket ONCE while inheriting control-channel TLS session."""
        # Call ftplib.FTP directly so FTP_TLS.ntransfercmd does not wrap twice.
        conn, size = ftplib.FTP.ntransfercmd(self, cmd, rest)
        if self._prot_p:
            session = self.sock.session if isinstance(self.sock, ssl.SSLSocket) else None
            conn = self.context.wrap_socket(
                conn,
                server_hostname=self._sni_hostname(),
                session=session,
            )
        return conn, size


def build_tls_context(verify_cert: bool = False) -> ssl.SSLContext:
    """Build a strict TLSv1.2 SSLContext compatible with Pure-FTPd session reuse."""
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    context.maximum_version = ssl.TLSVersion.TLSv1_2
    if not verify_cert:
        context.check_hostname = False
        context.verify_mode = ssl.CERT_NONE
    return context


def _matches_pattern(rel_path: str, pattern: str) -> bool:
    """Match a POSIX relative path against an exclude/include glob pattern."""
    pat = pattern.strip()
    while pat.startswith("./"):
        pat = pat[2:]
    pat = pat.lstrip("/")
    if not pat:
        return False

    segments = rel_path.split("/")
    basename = segments[-1]

    # Match folder-prefix rules like "vendor/**" or ".github/**"
    if pat.endswith("/**"):
        prefix = pat[:-3]
        if prefix.startswith("**/"):
            needle = prefix[3:]
            return needle in segments[:-1] or any(
                fnmatch.fnmatch(seg, needle) for seg in segments[:-1]
            )
        return rel_path.startswith(prefix + "/")

    # Match recursive wildcard segments like "**/.git*" or "**/node_modules/**"
    if pat.startswith("**/"):
        sub = pat[3:]
        return (
            fnmatch.fnmatch(rel_path, sub)
            or fnmatch.fnmatch(basename, sub)
            or any(fnmatch.fnmatch(seg, sub) for seg in segments)
            or any(
                fnmatch.fnmatch("/".join(segments[i:]), sub)
                for i in range(len(segments))
            )
        )

    # Match direct glob like "*.zip", "storage/logs/*.log", "storage/framework/sessions/*"
    if "/" not in pat:
        return fnmatch.fnmatch(basename, pat) or fnmatch.fnmatch(rel_path, pat)

    return fnmatch.fnmatch(rel_path, pat)


def should_exclude(rel_path: str, patterns: Iterable[str]) -> bool:
    """Evaluate exclude rules and '!' negations in order."""
    excluded = False
    for raw in patterns:
        pat = raw.strip()
        if not pat or pat.startswith("#"):
            continue
        if pat.startswith("!"):
            if _matches_pattern(rel_path, pat[1:]):
                excluded = False
        elif _matches_pattern(rel_path, pat):
            excluded = True
    return excluded


def is_protected_remote(rel_path: str, state_name: str) -> bool:
    """Prevent accidental deletion of runtime-only or hosting-owned files."""
    if rel_path == state_name or rel_path.startswith(".ftp-"):
        return True
    if rel_path in PROTECTED_EXACT or rel_path.startswith(".env."):
        return True
    return rel_path.startswith(PROTECTED_PREFIXES)


def sha256_file(path: str) -> str:
    h = hashlib.sha256()
    with open(path, "rb") as f:
        while True:
            chunk = f.read(65536)
            if not chunk:
                break
            h.update(chunk)
    return h.hexdigest()


def collect_local_files(
    local_dir: str,
    exclude_patterns: List[str],
    state_name: str,
) -> Tuple[Dict[str, str], List[str]]:
    """Return ({rel_path: sha256}, skipped_empty_files) sorted deterministically."""
    base = os.path.abspath(local_dir)
    hashes: Dict[str, str] = {}
    skipped_empty: List[str] = []

    for root, dirs, files in os.walk(base):
        dirs.sort()
        files.sort()
        rel_root = os.path.relpath(root, base)
        posix_root = "" if rel_root == "." else rel_root.replace(os.sep, "/")

        # Prune fully-excluded top/nested directories early for speed.
        kept_dirs = []
        for d in dirs:
            candidate = f"{posix_root}/{d}/__probe__" if posix_root else f"{d}/__probe__"
            dir_probe = f"{posix_root}/{d}" if posix_root else d
            # Only prune if directory itself is excluded and no negation can rescue inside it
            has_negation = any(
                p.strip().startswith("!" + dir_probe + "/") for p in exclude_patterns
            )
            if not has_negation and should_exclude(candidate, exclude_patterns):
                continue
            kept_dirs.append(d)
        dirs[:] = kept_dirs

        for fname in files:
            rel_path = f"{posix_root}/{fname}" if posix_root else fname
            if rel_path == state_name or rel_path.startswith(".ftp-deploy"):
                continue
            if should_exclude(rel_path, exclude_patterns):
                continue

            full_path = os.path.join(root, fname)
            if os.path.getsize(full_path) == 0:
                skipped_empty.append(rel_path)
                continue

            hashes[rel_path] = sha256_file(full_path)

    return hashes, skipped_empty


@dataclass
class SyncConfig:
    host: str
    port: int
    user: str
    password: str
    protocol: str
    local_dir: str
    server_dir: str
    state_name: str
    excludes: List[str] = field(default_factory=list)
    clear_views: bool = False
    verify_cert: bool = False
    timeout: int = 30
    checkpoint_every: int = 20
    commit_sha: str = ""


class FTPSyncClient:
    """Manages resilient FTPS/FTP sync with session reuse and manifest checkpoints."""

    def __init__(self, cfg: SyncConfig, ftp_factory: Optional[Callable[[], ftplib.FTP]] = None):
        self.cfg = cfg
        self._ftp_factory = ftp_factory
        self.ftp: Optional[ftplib.FTP] = None
        self.ensured_dirs: Set[str] = set()
        self.base_cwd: str = "/"

    def _create_client(self) -> ftplib.FTP:
        if self._ftp_factory is not None:
            return self._ftp_factory()
        if self.cfg.protocol.lower() == "ftps":
            ctx = build_tls_context(verify_cert=self.cfg.verify_cert)
            return PureFTPTLS(context=ctx, timeout=self.cfg.timeout)
        return ftplib.FTP(timeout=self.cfg.timeout)

    def close(self) -> None:
        if self.ftp is not None:
            try:
                self.ftp.quit()
            except Exception:
                try:
                    self.ftp.close()
                except Exception:
                    pass
            finally:
                self.ftp = None

    def connect(self) -> ftplib.FTP:
        self.close()
        backoffs = (0, 3, 6, 10, 15)
        last_exc: Optional[Exception] = None
        for attempt, delay in enumerate(backoffs, start=1):
            if delay:
                print(f"[ftps-sync] Menunggu {delay}s sebelum mencoba ulang koneksi ({attempt}/{len(backoffs)})...")
                time.sleep(delay)
            client = self._create_client()
            try:
                client.connect(self.cfg.host, self.cfg.port, timeout=self.cfg.timeout)
                client.login(self.cfg.user, self.cfg.password)
                if isinstance(client, ftplib.FTP_TLS):
                    client.prot_p()
                client.set_pasv(True)
                target_dir = self.cfg.server_dir.strip() or "/"
                if target_dir != "/":
                    client.cwd(target_dir)
                self.base_cwd = client.pwd()
                self.ftp = client
                self.ensured_dirs.clear()
                return client
            except Exception as exc:
                last_exc = exc
                print(f"[ftps-sync] Gagal koneksi percobaan {attempt}: {exc}", file=sys.stderr)
                try:
                    client.close()
                except Exception:
                    pass
        assert last_exc is not None
        raise last_exc

    def ensure_connected(self) -> ftplib.FTP:
        if self.ftp is None:
            return self.connect()
        return self.ftp

    def run_resilient(self, fn: Callable[[ftplib.FTP], T], max_attempts: int = 4) -> T:
        last_exc: Optional[Exception] = None
        for attempt in range(1, max_attempts + 1):
            ftp = self.ensure_connected()
            try:
                return fn(ftp)
            except TRANSIENT_ERRORS as exc:
                last_exc = exc
                print(
                    f"[ftps-sync] Koneksi terputus sementara (percobaan {attempt}/{max_attempts}): {exc}",
                    file=sys.stderr,
                )
                self.close()
                if attempt < max_attempts:
                    time.sleep(min(3 * attempt, 12))
        assert last_exc is not None
        raise last_exc

    def load_remote_manifest(self) -> Dict[str, str]:
        def _fetch(ftp: ftplib.FTP) -> Optional[bytes]:
            buf = io.BytesIO()
            try:
                ftp.retrbinary(f"RETR {self.cfg.state_name}", buf.write)
                return buf.getvalue()
            except ftplib.error_perm:
                return None

        raw = self.run_resilient(_fetch)
        if not raw:
            return {}
        try:
            parsed = json.loads(raw.decode("utf-8"))
            files = parsed.get("files", {})
            return files if isinstance(files, dict) else {}
        except Exception as exc:
            print(f"[ftps-sync] Manifest remote rusak/diabaikan ({exc}), mulai full diff.", file=sys.stderr)
            return {}

    def save_remote_manifest(self, files: Dict[str, str]) -> None:
        payload = json.dumps(
            {
                "version": 3,
                "commit": self.cfg.commit_sha,
                "updated_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
                "files": dict(sorted(files.items())),
            },
            separators=(",", ":"),
        ).encode("utf-8")

        def _store(ftp: ftplib.FTP) -> None:
            ftp.storbinary(f"STOR {self.cfg.state_name}", io.BytesIO(payload), blocksize=32768)

        self.run_resilient(_store)

    def ensure_remote_dir(self, ftp: ftplib.FTP, rel_dir: str) -> None:
        if not rel_dir or rel_dir in self.ensured_dirs:
            return
        parts = [p for p in rel_dir.split("/") if p]
        prefix = ""
        for part in parts:
            prefix = f"{prefix}/{part}" if prefix else part
            if prefix in self.ensured_dirs:
                continue
            try:
                ftp.mkd(prefix)
            except ftplib.error_perm as exc:
                # Verify folder actually exists rather than ignoring genuine errors.
                try:
                    ftp.cwd(prefix)
                    ftp.cwd(self.base_cwd)
                except ftplib.error_perm:
                    raise exc
            self.ensured_dirs.add(prefix)

    def upload_file(self, rel_path: str, abs_path: str) -> None:
        rel_dir = posixpath.dirname(rel_path)

        def _put(ftp: ftplib.FTP) -> None:
            if rel_dir:
                self.ensure_remote_dir(ftp, rel_dir)
            with open(abs_path, "rb") as fp:
                ftp.storbinary(f"STOR {rel_path}", fp, blocksize=32768)

        self.run_resilient(_put)

    def delete_file(self, rel_path: str) -> None:
        def _del(ftp: ftplib.FTP) -> None:
            try:
                ftp.delete(rel_path)
            except ftplib.error_perm:
                pass

        self.run_resilient(_del)

    def clear_remote_compiled_views(self) -> int:
        """Delete stale storage/framework/views/*.php files while preserving .keep/.gitignore."""
        removed = 0

        def _clear(ftp: ftplib.FTP) -> int:
            count = 0
            try:
                entries = ftp.nlst("storage/framework/views")
            except ftplib.error_perm:
                return 0
            for entry in entries:
                name = posixpath.basename(entry.strip())
                if not name.endswith(".php"):
                    continue
                target = f"storage/framework/views/{name}"
                try:
                    ftp.delete(target)
                    count += 1
                except ftplib.error_perm:
                    pass
            return count

        removed = self.run_resilient(_clear)
        return removed

    def sync(self) -> Dict[str, int]:
        local_hashes, skipped_empty = collect_local_files(
            self.cfg.local_dir,
            self.cfg.excludes,
            self.cfg.state_name,
        )
        if skipped_empty:
            print(f"[ftps-sync] Melewati {len(skipped_empty)} file 0-byte: {', '.join(skipped_empty[:10])}")

        remote_hashes = self.load_remote_manifest()
        to_upload = [rel for rel, sha in local_hashes.items() if remote_hashes.get(rel) != sha]
        to_delete = [
            rel
            for rel in sorted(remote_hashes.keys())
            if rel not in local_hashes and not is_protected_remote(rel, self.cfg.state_name)
        ]

        print(
            f"[ftps-sync] Ringkasan ({self.cfg.state_name}): "
            f"total={len(local_hashes)}, upload={len(to_upload)}, "
            f"hapus={len(to_delete)}, sama={len(local_hashes) - len(to_upload)}"
        )

        uploaded_count = 0
        deleted_count = 0
        state_dirty = False
        working_state = dict(remote_hashes)
        base_abs = os.path.abspath(self.cfg.local_dir)

        try:
            for idx, rel in enumerate(to_upload, start=1):
                abs_path = os.path.join(base_abs, *rel.split("/"))
                self.upload_file(rel, abs_path)
                working_state[rel] = local_hashes[rel]
                uploaded_count += 1
                state_dirty = True
                if idx <= 10 or idx % 25 == 0 or idx == len(to_upload):
                    print(f"[ftps-sync] [{idx}/{len(to_upload)}] Uploaded: {rel}")
                if self.cfg.checkpoint_every > 0 and uploaded_count % self.cfg.checkpoint_every == 0:
                    self.save_remote_manifest(working_state)
                    state_dirty = False

            for rel in to_delete:
                self.delete_file(rel)
                working_state.pop(rel, None)
                deleted_count += 1
                state_dirty = True
                print(f"[ftps-sync] Deleted obsolete: {rel}")

            # Prune non-existent non-protected entries left in manifest from earlier runs
            for stale_key in list(working_state.keys()):
                if stale_key not in local_hashes and is_protected_remote(stale_key, self.cfg.state_name):
                    working_state.pop(stale_key, None)
                    state_dirty = True

            views_cleared = 0
            if self.cfg.clear_views and (uploaded_count > 0 or deleted_count > 0 or not remote_hashes):
                views_cleared = self.clear_remote_compiled_views()
                if views_cleared:
                    print(f"[ftps-sync] Cleared {views_cleared} cached Blade views in storage/framework/views")

            if state_dirty or not remote_hashes:
                self.save_remote_manifest(working_state)
        finally:
            self.close()

        return {
            "total": len(local_hashes),
            "uploaded": uploaded_count,
            "deleted": deleted_count,
            "views_cleared": views_cleared,
        }


def parse_args(argv: Optional[List[str]] = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Resilient FTPS/FTP incremental sync")
    parser.add_argument("--local-dir", default=".")
    parser.add_argument("--server-dir", default="/")
    parser.add_argument("--state-name", required=True)
    parser.add_argument("--protocol", default="ftps", choices=("ftps", "ftp"))
    parser.add_argument("--port", type=int, default=21)
    parser.add_argument("--timeout", type=int, default=30)
    parser.add_argument("--checkpoint-every", type=int, default=20)
    parser.add_argument("--verify-cert", action="store_true")
    parser.add_argument("--clear-views", action="store_true")
    parser.add_argument("--exclude-file", default="")
    return parser.parse_args(argv)


def main(argv: Optional[List[str]] = None) -> int:
    args = parse_args(argv)
    host = os.environ.get("FTP_SERVER", "").strip()
    user = os.environ.get("FTP_USERNAME", "").strip()
    password = os.environ.get("FTP_PASSWORD", "")

    if not host or not user or not password:
        print("FTP_SERVER, FTP_USERNAME, dan FTP_PASSWORD wajib diisi.", file=sys.stderr)
        return 1

    excludes: List[str] = []
    if args.exclude_file and os.path.isfile(args.exclude_file):
        with open(args.exclude_file, "r", encoding="utf-8") as fp:
            excludes = fp.read().splitlines()

    cfg = SyncConfig(
        host=host,
        port=args.port,
        user=user,
        password=password,
        protocol=args.protocol,
        local_dir=args.local_dir,
        server_dir=args.server_dir,
        state_name=args.state_name,
        excludes=excludes,
        clear_views=args.clear_views,
        verify_cert=args.verify_cert,
        timeout=args.timeout,
        checkpoint_every=args.checkpoint_every,
        commit_sha=os.environ.get("GITHUB_SHA", ""),
    )

    stats = FTPSyncClient(cfg).sync()
    print(f"[ftps-sync] Selesai: {json.dumps(stats)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
