#!/usr/bin/env bash
# Constitution quality gates (T055, T056). Run from repo root.
set -uo pipefail
fail=0

echo "== Gate III: жодних зовнішніх asset-<script>/<link>/CDN у view (Принцип III) =="
# Ловимо ЗАВАНТАЖЕННЯ ассетів (script/link/img src|href, CSS url()/@import) із зовнішнього хосту,
# але НЕ навігаційні <a href> (вони не є завантаженням ассета).
if grep -rInE '<(script|link|img)[^>]+(src|href)=["'"'"']https?://|url\(\s*["'"'"']?https?://|@import\s+["'"'"']?https?://' crm/resources/views 2>/dev/null; then
  echo "  FAIL: зовнішнє завантаження ассета у view"; fail=1
else
  echo "  OK"
fi

echo "== Gate I: у plugin/ немає Laravel-залежностей (Принцип I) =="
if grep -rInE 'Illuminate\\|laravel/|use App\\' plugin --include='*.php' 2>/dev/null; then
  echo "  FAIL: Laravel-посилання у plugin/"; fail=1
else
  echo "  OK"
fi

echo "== Gate II: у plugin/ немає захардкоджених http-адрес у коді (Принцип II, FR-024) =="
if grep -rInE 'https?://[a-z0-9.-]+' plugin --include='*.php' 2>/dev/null; then
  echo "  FAIL: захардкоджений URL у plugin/*.php (адреса має братися з конфігурації)"; fail=1
else
  echo "  OK"
fi

echo "== Gate: секрети не в репозиторії =="
if git ls-files 2>/dev/null | grep -E '(^|/)\.env$' ; then
  echo "  FAIL: .env у git"; fail=1
else
  echo "  OK"
fi

echo "== gates: fail=$fail =="
exit $fail
