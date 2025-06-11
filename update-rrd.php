#!/usr/bin/env php

<?php

$log = file_get_contents(__DIR__ . '/../logs/speedtest.log');
$data = json_decode($log, true);

if (!$data) exit;

$upload = round($data['upload']['bandwidth'] * 8 / 1000 / 1000, 2); // Mbps
$download = round($data['download']['bandwidth'] * 8 / 1000 / 1000, 2); // Mbps
$ping = $data['ping']['latency'];

$rrd_bandwidth = 'plugins/Speedtest/rrd/speedtest-bandwidth.rrd';
$rrd_latency = 'plugins/Speedtest/rrd/speedtest-latency.rrd';

exec("rrdtool update $rrd_bandwidth N:$upload:$download");
exec("rrdtool update $rrd_latency N:$ping");
