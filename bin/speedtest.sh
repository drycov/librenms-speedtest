#!/bin/bash

set -euo pipefail

# === Константы ===
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$BASE_DIR/../logs"
TMP_DIR="$BASE_DIR/../tmp"
RRD_UPDATE_SCRIPT="$BASE_DIR/update-rrd.php"
LOG_FILE="$LOG_DIR/speedtest.log"
SERVER_FILE="$TMP_DIR/speedtest-server"
STATUS_FILE="$TMP_DIR/status.json"
SPEEDTEST_BIN=$(command -v speedtest)
PHP_BIN=$(command -v php)

# === Проверка зависимостей ===
for cmd in speedtest jq php rrdtool; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "[$(date -Is)] Error: '$cmd' is not installed." | tee -a "$LOG_FILE" >&2
        echo '{"done": true, "error": "missing_dependency"}' > "$STATUS_FILE"
        exit 1
    fi
done

# === Подготовка директорий ===
mkdir -p "$LOG_DIR" "$TMP_DIR"

# === Обновление статуса: начато ===
echo '{"done": false, "timestamp": "'"$(date -Is)"'"}' > "$STATUS_FILE"

# === Запуск Speedtest CLI ===
echo "[$(date -Is)] Info: Running speedtest..." >> "$LOG_FILE"
RESULT=$($SPEEDTEST_BIN --format=json 2>>"$LOG_FILE") || {
    echo "[$(date -Is)] Error: speedtest CLI execution failed." >> "$LOG_FILE"
    echo '{"done": true, "error": "speedtest_failed"}' > "$STATUS_FILE"
    exit 1
}

# === Валидация JSON ===
if ! echo "$RESULT" | jq empty 2>/dev/null; then
    echo "[$(date -Is)] Error: invalid JSON from speedtest CLI" >> "$LOG_FILE"
    echo '{"done": true, "error": "invalid_json"}' > "$STATUS_FILE"
    exit 1
fi

# === Сохраняем результат ===
echo "$RESULT" > "$LOG_FILE"
echo "[$(date -Is)] Info: speedtest result saved." >> "$LOG_FILE"

# === Извлечение информации о сервере ===
SERVER_INFO=$(echo "$RESULT" | jq -r '.server.name + ", " + .server.location')
echo "$SERVER_INFO" > "$SERVER_FILE"
echo "[$(date -Is)] Info: Server - $SERVER_INFO" >> "$LOG_FILE"

# === Обновление RRD ===
echo "[$(date -Is)] Info: Updating RRD..." >> "$LOG_FILE"
if ! "$PHP_BIN" "$RRD_UPDATE_SCRIPT" >> "$LOG_FILE" 2>&1; then
    echo "[$(date -Is)] Warning: RRD update failed." >> "$LOG_FILE"
fi

# === Завершение ===
echo '{"done": true, "timestamp": "'"$(date -Is)"'"}' > "$STATUS_FILE"
echo "[$(date -Is)] Info: speedtest completed." >> "$LOG_FILE"
exit 0
