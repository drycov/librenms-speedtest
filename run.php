<?php
header('Content-Type: application/json');

// Путь к статус-файлу
$statusPath = __DIR__ . '/tmp/status.json';
$logPath = __DIR__ . '/tmp/speedtest.log';

// Сброс статуса в "выполняется"
file_put_contents($statusPath, json_encode(['done' => false]));

// Очистка старого лога (по желанию)
if (file_exists($logPath)) {
    unlink($logPath);
}

// Выполнение скрипта
$script = realpath(__DIR__ . '/bin/speedtest.sh');
exec("bash $script > $logPath 2>&1", $output, $exitCode);

// Обработка результата
if ($exitCode === 0) {
    file_put_contents($statusPath, json_encode(['done' => true]));
    echo json_encode([
        'status' => 'ok',
        'message' => 'Speedtest completed.',
        'exit_code' => $exitCode
    ]);
} else {
    file_put_contents($statusPath, json_encode(['done' => true, 'error' => true]));
    echo json_encode([
        'status' => 'error',
        'message' => 'Speedtest failed.',
        'exit_code' => $exitCode,
        'output' => $output
    ]);
}
