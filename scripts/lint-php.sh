#!/usr/bin/env bash
set -euo pipefail
find . -type f -name '*.php' -not -path './storage/*' -print0 | while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done
echo "PHP syntax OK"
