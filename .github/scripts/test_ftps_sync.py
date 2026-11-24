"""Unit tests for .github/scripts/ftps_sync.py."""

import ftplib
import io
import json
import os
import ssl
import sys
import tempfile
import unittest

sys.path.insert(0, os.path.dirname(__file__))

import ftps_sync  # noqa: E402


class FakeFTPSServer:
    """In-memory mock matching ftplib.FTP / FTP_TLS behavior used by FTPSyncClient."""

    def __init__(self, initial_files=None):
        self.files = dict(initial_files or {})
        self.dirs = {"/"}
        self.cwd_path = "/"
        self.logged_in = False
        self.protected = False
        self.pasv = False
        self.deleted = []
        self.fail_next_stor = 0

    def connect(self, host, port=21, timeout=30):
        return "220 Fake Pure-FTPd"

    def login(self, user, passwd):
        self.logged_in = True
        return "230 Logged in"

    def prot_p(self):
        self.protected = True
        return "200 PROT P"

    def set_pasv(self, val):
        self.pasv = val

    def pwd(self):
        return self.cwd_path

    def cwd(self, path):
        norm = path if path.startswith("/") else "/" + path.strip("/")
        if norm not in self.dirs and path.strip("/") not in self.dirs:
            raise ftplib.error_perm(f"550 Can't change directory to {path}")
        self.cwd_path = norm

    def mkd(self, path):
        key = path.strip("/")
        if key in self.dirs:
            raise ftplib.error_perm(f"550 Can't create directory: File exists")
        self.dirs.add(key)

    def retrbinary(self, cmd, callback, blocksize=8192):
        target = cmd.split(" ", 1)[1].strip()
        if target not in self.files:
            raise ftplib.error_perm(f"550 Can't open {target}: No such file or directory")
        callback(self.files[target])

    def storbinary(self, cmd, fp, blocksize=8192):
        if self.fail_next_stor > 0:
            self.fail_next_stor -= 1
            raise ssl.SSLEOFError("Transient test drop")
        target = cmd.split(" ", 1)[1].strip()
        self.files[target] = fp.read()

    def delete(self, path):
        if path not in self.files:
            raise ftplib.error_perm(f"550 Could not delete {path}")
        del self.files[path]
        self.deleted.append(path)

    def nlst(self, directory):
        prefix = directory.rstrip("/") + "/"
        return [k for k in sorted(self.files.keys()) if k.startswith(prefix)]

    def quit(self):
        self.logged_in = False

    def close(self):
        self.logged_in = False


class FTPSSyncTests(unittest.TestCase):
    def test_pure_ftps_sni_strips_ip_addresses_and_keeps_domains(self):
        ip_client = ftps_sync.PureFTPTLS(context=ftps_sync.build_tls_context())
        ip_client.host = "202.10.43.186"
        self.assertIsNone(ip_client._sni_hostname())

        domain_client = ftps_sync.PureFTPTLS(context=ftps_sync.build_tls_context())
        domain_client.host = "lewotobi.iixcp.rumahweb.net"
        self.assertEqual(domain_client._sni_hostname(), "lewotobi.iixcp.rumahweb.net")

    def test_excludes_and_negations_work_uniformly(self):
        patterns = [
            "**/.git*",
            "**/.git*/**",
            ".github/**",
            "**/vendor/**",
            "vendor/**",
            "*.zip",
            "**/.env",
            "**/.env.*",
            "!.env.example",
            "storage/logs/*.log",
            "storage/framework/sessions/**",
            "!storage/framework/sessions/.keep",
            "storage/framework/views/**",
            "!storage/framework/views/.keep",
            "api/**",
        ]
        self.assertTrue(ftps_sync.should_exclude("vendor/laravel/framework.php", patterns))
        self.assertTrue(ftps_sync.should_exclude(".github/workflows/deploy.yml", patterns))
        self.assertTrue(ftps_sync.should_exclude("app_core.zip", patterns))
        self.assertTrue(ftps_sync.should_exclude(".env", patterns))
        self.assertTrue(ftps_sync.should_exclude(".env.deploy", patterns))
        self.assertFalse(ftps_sync.should_exclude(".env.example", patterns))
        self.assertTrue(ftps_sync.should_exclude("storage/logs/laravel.log", patterns))
        self.assertFalse(ftps_sync.should_exclude("storage/logs/.keep", patterns))
        self.assertTrue(ftps_sync.should_exclude("storage/framework/sessions/abc", patterns))
        self.assertFalse(ftps_sync.should_exclude("storage/framework/sessions/.keep", patterns))
        self.assertTrue(ftps_sync.should_exclude("storage/framework/views/compiled.php", patterns))
        self.assertFalse(ftps_sync.should_exclude("storage/framework/views/.keep", patterns))
        self.assertTrue(ftps_sync.should_exclude("api/index.php", patterns))
        self.assertFalse(ftps_sync.should_exclude("resources/views/pre_generator.blade.php", patterns))

        public_patterns = [
            "**/.git*",
            ".well-known/**",
            "cgi-bin/**",
            "error_log",
            ".user.ini",
        ]
        self.assertFalse(ftps_sync.should_exclude(".htaccess", public_patterns))
        self.assertFalse(ftps_sync.should_exclude("index.php", public_patterns))
        self.assertTrue(ftps_sync.should_exclude(".well-known/acme-challenge/token", public_patterns))
        self.assertTrue(ftps_sync.should_exclude(".user.ini", public_patterns))
        self.assertTrue(ftps_sync.should_exclude("error_log", public_patterns))

    def test_incremental_sync_skips_empty_recovers_drop_and_clears_views(self):
        with tempfile.TemporaryDirectory() as tmp:
            os.makedirs(os.path.join(tmp, "resources", "views"))
            os.makedirs(os.path.join(tmp, "storage", "framework", "views"))
            with open(os.path.join(tmp, "resources", "views", "home.blade.php"), "w") as f:
                f.write("<h1>Updated</h1>")
            with open(os.path.join(tmp, "storage", "framework", "views", ".keep"), "w") as f:
                f.write("# keep\n")
            with open(os.path.join(tmp, "empty.txt"), "w") as f:
                pass

            fake = FakeFTPSServer(
                initial_files={
                    "storage/framework/views/stale123.php": b"<?php echo 'old';",
                    "old_removed.php": b"<?php // removed",
                    ".env": b"APP_KEY=secret",
                    ".ftp-deploy-manifest-app-v3.json": json.dumps(
                        {
                            "version": 3,
                            "files": {
                                "old_removed.php": "deadbeef",
                                ".env": "should-never-delete",
                            },
                        }
                    ).encode("utf-8"),
                }
            )
            fake.fail_next_stor = 1  # simulate one transient SSL drop

            cfg = ftps_sync.SyncConfig(
                host="202.10.43.186",
                port=21,
                user="u",
                password="p",
                protocol="ftps",
                local_dir=tmp,
                server_dir="/",
                state_name=".ftp-deploy-manifest-app-v3.json",
                excludes=["storage/framework/views/**", "!storage/framework/views/.keep"],
                clear_views=True,
                checkpoint_every=1,
            )

            stats = ftps_sync.FTPSyncClient(cfg, ftp_factory=lambda: fake).sync()

            self.assertEqual(stats["uploaded"], 2)
            self.assertEqual(stats["deleted"], 1)
            self.assertEqual(stats["views_cleared"], 1)
            self.assertIn("resources/views/home.blade.php", fake.files)
            self.assertIn("storage/framework/views/.keep", fake.files)
            self.assertNotIn("empty.txt", fake.files)
            self.assertNotIn("old_removed.php", fake.files)
            self.assertNotIn("storage/framework/views/stale123.php", fake.files)
            # Protected .env must remain intact even if previously listed in manifest
            self.assertIn(".env", fake.files)

            # Second run with no changes should upload 0 files
            stats2 = ftps_sync.FTPSyncClient(cfg, ftp_factory=lambda: fake).sync()
            self.assertEqual(stats2["uploaded"], 0)
            self.assertEqual(stats2["deleted"], 0)


if __name__ == "__main__":
    unittest.main()
