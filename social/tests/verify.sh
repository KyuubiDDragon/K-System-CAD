#!/usr/bin/env bash
# Disposable integration stack: never stops or resets the local demo.
set -euo pipefail
cd "$(dirname "$0")/../.."
export COMPOSE_PROJECT_NAME="kyuubi-social-check-$$"
export SOCIAL_TEST_PORT="${SOCIAL_TEST_PORT:-8087}"
export SOCIAL_TEST_UI_PORT="${SOCIAL_TEST_UI_PORT:-5186}"
export SOCIAL_TEST_URL="http://127.0.0.1:${SOCIAL_TEST_PORT}/api/social/index.php"
export SOCIAL_TEST_BROWSER_URL="http://127.0.0.1:${SOCIAL_TEST_UI_PORT}"
compose=(docker-compose -f backend/social/tests/compose.yml --profile browser)
trap '"${compose[@]}" down --volumes' EXIT
command -v docker-compose >/dev/null
docker build -t kyuubi-social-backend-dev backend
"${compose[@]}" up -d --build --wait
npm --prefix social test
(cd social && npx playwright test)
npm --prefix social run build
