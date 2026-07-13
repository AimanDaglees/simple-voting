        #!/usr/bin/env bash
        set -euo pipefail

        cd "$(dirname "$0")/.."

        test -f composer.json
        test -d web/modules/custom/simple_voting
        test -f web/modules/custom/simple_voting/simple_voting.info.yml
        test -f web/modules/custom/simple_voting/docs/TESTING.md
        test -f web/modules/custom/simple_voting/docs/CODE_STANDARDS.md
        test -f web/modules/custom/simple_voting/docs/api/Simple-Voting.postman_collection.json

        grep -q "core_version_requirement: ^11" \
          web/modules/custom/simple_voting/simple_voting.info.yml


if [[ ! -f database/simple-voting.sql.gz ]]; then
  echo "Missing database/simple-voting.sql.gz" >&2
  exit 1
fi
gzip -t database/simple-voting.sql.gz


        python3 -m json.tool \
          web/modules/custom/simple_voting/docs/api/Simple-Voting.postman_collection.json \
          >/dev/null

        echo "LANDO Drupal 11 package structure is valid."
