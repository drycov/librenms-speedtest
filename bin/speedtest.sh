#!/bin/bash

LOG_DIR="../logs"
LOG_FILE="${LOG_DIR}/speedtest.log"
SERVER_FILE="../tmp/speedtest-server"

mkdir -p "$LOG_DIR"
mkdir -p "$(dirname "$SERVER_FILE")"

# Запуск теста
RESULT=$(speedtest --format=json)

if [ $? -eq 0 ]; then
    echo "$RESULT" > "$LOG_FILE"
    echo "$(echo "$RESULT" | jq -r '.server.name, .server.location')" > "$SERVER_FILE"
    exit 0
else
    echo "Error: speedtest failed at $(date)" >> "$LOG_FILE"
    exit 1
fi
