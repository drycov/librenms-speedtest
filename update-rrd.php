#!/usr/bin/env php
<?php

$logFile = __DIR__ . '/../logs/speedtest.log';
$dataRaw = file_exists($logFile) ? file_get_contents($logFile) : null;
$data = json_decode($dataRaw, true);

if (!$data || !isset($data['upload'], $data['download'], $data['ping'])) {
    error_log("[" . date('c') . "] ERROR: Invalid speedtest.log\n", 3, $logFile);
    exit("Invalid speedtest.log\n");
}

// Конвертация в Mbps
$upload = round($data['upload']['bandwidth'] * 8 / 1000 / 1000, 2);
$download = round($data['download']['bandwidth'] * 8 / 1000 / 1000, 2);
$ping = round($data['ping']['latency'], 2);

$rrdDir = __DIR__ . '/rrd';
$pngDir = __DIR__ . '/png';

@mkdir($rrdDir, 0777, true);
@mkdir($pngDir, 0777, true);

$rrdBandwidth = "$rrdDir/speedtest-bandwidth.rrd";
$rrdLatency = "$rrdDir/speedtest-latency.rrd";

// === Создание RRD-файлов при отсутствии ===
if (!file_exists($rrdBandwidth)) {
    exec("rrdtool create $rrdBandwidth --step 300 \
        DS:upload:GAUGE:600:0:1000 \
        DS:download:GAUGE:600:0:1000 \
        RRA:AVERAGE:0.5:1:288 \
        RRA:AVERAGE:0.5:6:336 \
        RRA:AVERAGE:0.5:24:360 \
        RRA:AVERAGE:0.5:288:365");
}

if (!file_exists($rrdLatency)) {
    exec("rrdtool create $rrdLatency --step 300 \
        DS:latency:GAUGE:600:0:10000 \
        RRA:AVERAGE:0.5:1:288 \
        RRA:AVERAGE:0.5:6:336 \
        RRA:AVERAGE:0.5:24:360 \
        RRA:AVERAGE:0.5:288:365");
}

// === Обновление значений RRD ===
$rc = 0;
$out = [];

exec("rrdtool update $rrdBandwidth N:$upload:$download 2>&1", $out, $rc);
if ($rc !== 0) {
    error_log("[" . date('c') . "] ERROR bandwidth update: " . implode("\n", $out) . "\n", 3, $logFile);
}

exec("rrdtool update $rrdLatency N:$ping 2>&1", $out, $rc);
if ($rc !== 0) {
    error_log("[" . date('c') . "] ERROR latency update: " . implode("\n", $out) . "\n", 3, $logFile);
}

// === Генерация PNG-графиков ===
$periods = [
    'day'   => 86400,
    'week'  => 604800,
    'month' => 2592000,
    'year'  => 31536000
];

foreach ($periods as $label => $seconds) {
    $bandwidthPng = "$pngDir/speedtest-bandwidth-$label.png";
    $latencyPng   = "$pngDir/speedtest-latency-$label.png";

    exec("rrdtool graph $bandwidthPng --start -$seconds --width 600 --height 200 \
        --title 'Bandwidth ($label)' \
        DEF:up=$rrdBandwidth:upload:AVERAGE \
        DEF:down=$rrdBandwidth:download:AVERAGE \
        LINE2:up#00cc00:'Upload (Mbps)' \
        LINE2:down#0033cc:'Download (Mbps)' \
        COMMENT:'\\n' \
        GPRINT:up:LAST:'Current Up\\: %5.2lf Mbps' \
        GPRINT:down:LAST:'Current Down\\: %5.2lf Mbps\\n'");

    exec("rrdtool graph $latencyPng --start -$seconds --width 600 --height 200 \
        --title 'Latency ($label)' \
        DEF:ping=$rrdLatency:latency:AVERAGE \
        LINE2:ping#ff0000:'Latency (ms)' \
        COMMENT:'\\n' \
        GPRINT:ping:LAST:'Current\\: %5.2lf ms\\n'");
}

echo "✔ RRD updated. PNG graphs generated.\n";
echo "  - Upload={$upload} Mbps\n";
echo "  - Download={$download} Mbps\n";
echo "  - Latency={$ping} ms\n";
echo "";