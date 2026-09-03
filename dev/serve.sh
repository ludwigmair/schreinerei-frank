#!/usr/bin/env bash
# Schreinerei Frank – lokaler PHP-Dev-Server
# Nutzung: bash dev/serve.sh   (oder ./dev/serve.sh)
# Startet den PHP-Server und öffnet Website + Admin automatisch in Chrome.
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# Dev-Werte (Port, Admin-Pfad) kommen aus dem zentralen config-Block in site.json.
CFG_JSON="$(php -r '$c = require $argv[1] . "/php/config.php"; echo json_encode($c["project"] ?? []);' "$ROOT")"
CFG_PORT="$(printf '%s' "$CFG_JSON" | php -r 'echo (int)json_decode(stream_get_contents(STDIN), true)["port"] ?? 9999;')"
CFG_ADMIN="$(printf '%s' "$CFG_JSON" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["adminPath"] ?? "/frank-adm";')"

HOST="${HOST:-127.0.0.1}"
cd "$ROOT"

# Eindeutiger Port pro Projekt: von config.port (Default) ausgehend den ersten
# freien Port wählen, damit zwei lokal laufende Projekte nie kollidieren.
PREFERRED="${PORT:-${CFG_PORT:-9999}}"
PORT="$PREFERRED"
for i in $(seq 0 100); do
  if ! lsof -ti "tcp:${PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
    break
  fi
  PORT=$((PREFERRED + i + 1))
done

URL="http://localhost:${PORT}"
ADMIN_URL="${URL}${CFG_ADMIN}/"

echo "Schreinerei-Frank (PHP) läuft auf ${URL}"
echo "Admin: ${ADMIN_URL}"
if [ "$PORT" != "$PREFERRED" ]; then
  echo "Port ${PREFERRED} war belegt – nutze freien Port ${PORT}."
fi

# PHP-Server im Hintergrund starten (loggt in dev/server.log)
php -S "${HOST}:${PORT}" dev/router.php > dev/server.log 2>&1 &
SERVER_PID=$!

# Warten, bis der Server antwortet
for i in $(seq 1 20); do
  if curl -sf -o /dev/null "${URL}/"; then
    break
  fi
  sleep 0.5
done

# Website + Admin in Chrome öffnen
if open -Ra "Google Chrome" 2>/dev/null; then
  open -a "Google Chrome" "${URL}"
  open -a "Google Chrome" "${ADMIN_URL}"
  echo "Geöffnet in Chrome: ${URL} und ${ADMIN_URL}"
else
  echo "Starten Sie im Browser: ${URL} und ${ADMIN_URL}"
fi

# Server im Vordergrund halten (Strg+C zum Beenden)
wait "$SERVER_PID"
