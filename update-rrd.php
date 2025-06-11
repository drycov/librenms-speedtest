#!/usr/bin/env php
<?php

$logFile = __DIR__ . '/../logs/speedtest.log';
$log = file_exists($logFile) ? file_get_contents($logFile) : null;
$data = json_decode($log, true);

if (!$data || !isset($data['upload'], $data['download'], $data['ping'])) {
    exit("Invalid speedtest.log\n");
}

$upload = round($data['upload']['bandwidth'] * 8 / 1000 / 1000, 2);   // Mbps
$download = round($data['download']['bandwidth'] * 8 / 1000 / 1000, 2); // Mbps
$ping = $data['ping']['latency'];                                      // ms

$rrdDir = __DIR__ . '/rrd';
@mkdir($rrdDir, 0777, true);

$rrdBandwidth = "$rrdDir/speedtest-bandwidth.rrd";
$rrdLatency = "$rrdDir/speedtest-latency.rrd";

// Создание RRD-файла, если не существует
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

// Обновление значений
exec("rrdtool update $rrdBandwidth N:$upload:$download");
exec("rrdtool update $rrdLatency N:$ping");

echo "RRD Updated: Upload={$upload}Mbps, Download={$download}Mbps, Latency={$ping}ms\n";
