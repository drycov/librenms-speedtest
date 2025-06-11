#!/usr/bin/env php
<?php
header('Content-Type: application/json');

$statusPath = __DIR__ . '/tmp/status.json';
$logPath = __DIR__ . '/tmp/speedtest.log';
$scriptPath = realpath(__DIR__ . '/bin/speedtest.sh');

// Проверка существования исполняемого скрипта
if (!$scriptPath || !file_exists($scriptPath)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Speedtest script not found.'
    ]);
    exit;
}

// Установка статуса выполнения
file_put_contents($statusPath, json_encode(['done' => false, 'timestamp' => time()]));

// Очистка старого лога
if (file_exists($logPath)) {
    unlink($logPath);
}

// Выполнение с безопасной командой
$cmd = 'bash ' . escapeshellarg($scriptPath) . ' > ' . escapeshellarg($logPath) . ' 2>&1';
exec($cmd, $output, $exitCode);

// Обработка результата
$result = [
    'timestamp' => time(),
    'status' => $exitCode === 0 ? 'ok' : 'error',
    'message' => $exitCode === 0 ? 'Speedtest completed.' : 'Speedtest failed.',
    'exit_code' => $exitCode
];

// Сохраняем статус-файл
$statusPayload = ['done' => true, 'timestamp' => time()];
if ($exitCode !== 0) {
    $statusPayload['error'] = true;
    $result['output'] = file_exists($logPath) ? file($logPath, FILE_IGNORE_NEW_LINES) : [];
}

file_put_contents($statusPath, json_encode($statusPayload, JSON_PRETTY_PRINT));

// Ответ клиенту
echo json_encode($result, JSON_PRETTY_PRINT);
