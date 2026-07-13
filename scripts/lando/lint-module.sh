#!/usr/bin/env bash
set -euo pipefail

cd /app

find web/modules/custom/simple_voting \
  -type f \
  \( -name '*.php' -o -name '*.module' -o -name '*.install' \) \
  -print0 | xargs -0 -n1 php -l
