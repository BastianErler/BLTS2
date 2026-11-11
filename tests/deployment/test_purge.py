import contextlib
import io
import json
import os
from pathlib import Path
import runpy
import tempfile
import unittest
from unittest.mock import patch

SCRIPT = Path(__file__).resolve().parents[2] / 'scripts/deploy/purge_cloudflare.py'


class Response:
    def __enter__(self):
        return io.BytesIO(b'{"success":true}')

    def __exit__(self, *args):
        pass


class PurgeTest(unittest.TestCase):
    def test_only_mutable_current_and_previous_files_are_purged(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            current = root / 'releases' / ('a' * 40)
            previous = root / 'releases' / ('b' * 40)
            for release in (current, previous):
                (release / 'resources/vue/dist/assets').mkdir(parents=True)
                (release / 'resources/vue/dist/assets/hash.js').write_text('asset')
            (current / 'resources/vue/dist/manifest.webmanifest').write_text('{}')
            (previous / 'resources/vue/dist/old-icon.png').write_text('old')
            (root / 'current').symlink_to(current)
            (root / '.previous-release').write_text(str(previous))
            captured = []

            def request(req, timeout):
                captured.extend(json.loads(req.data)['files'])
                return Response()

            env = {'DEPLOY_ROOT': str(root), 'PUBLIC_URL': 'https://example.test',
                   'CLOUDFLARE_ZONE_ID': 'a' * 32, 'CLOUDFLARE_API_TOKEN': 'test-token'}
            with patch.dict(os.environ, env), patch('urllib.request.urlopen', request), contextlib.redirect_stdout(io.StringIO()):
                runpy.run_path(str(SCRIPT), run_name='__main__')
            self.assertIn('https://example.test/sw.js', captured)
            self.assertIn('https://example.test/old-icon.png', captured)
            self.assertFalse(any('/assets/' in item or '/api/' in item for item in captured))


if __name__ == '__main__':
    unittest.main()
