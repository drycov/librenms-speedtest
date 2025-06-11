#!/bin/bash

# === Константы ===
BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_DIR="$BASE_DIR/../logs"
TMP_DIR="$BASE_DIR/../tmp"
RRD_UPDATE_SCRIPT="$BASE_DIR/update-rrd.php"
LOG_FILE="$LOG_DIR/speedtest.log"
SERVER_FILE="$TMP_DIR/speedtest-server"
STATUS_FILE="$TMP_DIR/status.json"

# === Проверка зависимостей ===
for cmd in speedtest jq php rrdtool; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "Error: '$cmd' is not installed." >&2
        exit 1
    fi
done

# === Подготовка директорий ===
mkdir -p "$LOG_DIR" "$TMP_DIR"

# === Обновление статуса: начато ===
echo '{"done": false, "timestamp": "'"$(date -Is)"'"}' > "$STATUS_FILE"

# === Запуск Speedtest ===
RESULT=$(speedtest --format=json 2>>"$LOG_FILE")
EXIT_CODE=$?

# === Проверка кода завершения и валидности JSON ===
if [ $EXIT_CODE -ne 0 ]; then
    echo "[$(date -Is)] Error: speedtest CLI failed with code $EXIT_CODE" >> "$LOG_FILE"
    echo '{"done": true, "error": "speedtest CLI failed"}' > "$STATUS_FILE"
    exit 1
fi

if ! echo "$RESULT" | jq empty 2>/dev/null; then
    echo "[$(date -Is)] Error: invalid JSON from speedtest CLI" >> "$LOG_FILE"
    echo '{"done": true, "error": "invalid JSON output"}' > "$STATUS_FILE"
    exit 1
fi

# === Сохраняем JSON в лог ===
echo "$RESULT" > "$LOG_FILE"

# === Сохраняем информацию о сервере ===
SERVER_INFO=$(echo "$RESULT" | jq -r '.server.name + ", " + .server.location')
echo "$SERVER_INFO" > "$SERVER_FILE"

# === Обновление RRD-файлов ===
php "$RRD_UPDATE_SCRIPT"
RRD_CODE=$?

if [ $RRD_CODE -ne 0 ]; then
    echo "[$(date -Is)] Warning: RRD update failed with code $RRD_CODE" >> "$LOG_FILE"
fi

# === Обновление статуса: завершено ===
echo '{"done": true, "timestamp": "'"$(date -Is)"'"}' > "$STATUS_FILE"
exit 0
