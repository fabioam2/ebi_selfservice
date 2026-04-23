#!/usr/bin/env bash
# Inicia servidor PHP embutido para ambiente de teste.
set -euo pipefail

DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/opt/homebrew/bin/php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  PHP_BIN="$(command -v php || true)"
fi
if [ -z "$PHP_BIN" ] || ! "$PHP_BIN" -v >/dev/null 2>&1; then
  echo "PHP não encontrado. Instale com: brew install php" >&2
  exit 1
fi

HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"

echo "→ Workspace : $DIR"
echo "→ PHP       : $PHP_BIN ($($PHP_BIN -r 'echo PHP_VERSION;'))"
echo "→ Servindo  : http://$HOST:$PORT/"
echo "→ Router    : test-env/router.php (emula .htaccess)"
echo ""
echo "Credenciais padrão: Senha123!"
echo ""

# Garante instância semeada
if [ ! -f "$DIR/test-env/instance/public_html/ebi/index.php" ]; then
  echo "→ Semeando instância de teste..."
  "$PHP_BIN" "$DIR/test-env/seed.php"
fi

cd "$DIR"
exec "$PHP_BIN" -S "$HOST:$PORT" -t "$DIR" "$DIR/test-env/router.php"
