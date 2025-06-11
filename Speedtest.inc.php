<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Speedtest Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css">
    <style>
        .img-graph {
            cursor: pointer;
            max-width: 100%;
            border: 0;
        }

        .graph-container {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="panel panel-default">
            <div class="panel-body">
                <img src="plugins/Speedtest/images/speedtest-logo.png" title="Speedtest Logo"
                    class="device-icon-header pull-left" style="max-height:25px;height:100%;margin-top:8px">
                <div class="pull-left" style="margin-top: 5px; margin-left: 10px;">
                    <span style="font-size: 12px;font-weight: bold">Last used Speedtest server:</span><br />
                    <span style="font-size: 12px;">
                        <?php
                        $path = "plugins/Speedtest/tmp/speedtest-server";
                        echo file_exists($path) ? htmlspecialchars(file_get_contents($path)) : "Not available";
                        ?>
                    </span>
                </div>
                <div class="pull-right">
                    <img src="plugins/Speedtest/images/ookla-logo.png" title="Ookla Logo" style="max-height: 50px">
                </div>
            </div>
        </div>

        <div class="text-right" style="margin-bottom: 10px;">
            <button id="run-speedtest" class="btn btn-primary">Run Speedtest</button>
        </div>

        <div id="charts-container">
            <div class="panel panel-default graph-container">
                <div class="panel-heading">
                    <h3 class="panel-title">Bandwidth</h3>
                </div>
                <div class="panel-body row">
                    <?php
                    $types = ['day', 'week', 'month', 'year'];
                    foreach ($types as $type) {
                        echo "<div class='col-md-3'>
                            <img src='plugins/Speedtest/png/speedtest-bandwidth-$type.png' class='img-graph' data-tippy-content='<img src=\"plugins/Speedtest/png/speedtest-bandwidth-$type.png\" style=\"max-width: 300px;\">'>
                        </div>";
                    }
                    ?>
                </div>
            </div>

            <div class="panel panel-default graph-container">
                <div class="panel-heading">
                    <h3 class="panel-title">Latency during Speedtest</h3>
                </div>
                <div class="panel-body row">
                    <?php
                    foreach ($types as $type) {
                        echo "<div class='col-md-3'>
                            <img src='plugins/Speedtest/png/speedtest-latency-$type.png' class='img-graph' data-tippy-content='<img src=\"plugins/Speedtest/png/speedtest-latency-$type.png\" style=\"max-width: 300px;\">'>
                        </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div id="loading-spinner" style="display:none; text-align:center; margin-top:20px;">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Running Speedtest...</span>
    </div>
</div>

<div id="live-result" class="well well-sm" style="display:none; font-family: monospace;"></div>


    <!-- Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <script>
    // Tippy.js: тултипы
    tippy('.img-graph', {
        allowHTML: true,
        interactive: true,
        theme: 'light',
        placement: 'top',
    });

    const button = document.getElementById('run-speedtest');
    const spinner = document.getElementById('loading-spinner');
    const liveResult = document.getElementById('live-result');

    button.addEventListener('click', () => {
        button.disabled = true;
        spinner.style.display = 'block';
        liveResult.style.display = 'block';
        liveResult.textContent = '';

        fetch('plugins/Speedtest/run.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                pollStatus(); // start polling
            })
            .catch(err => {
                console.error(err);
                alert('Error triggering speedtest.');
                spinner.style.display = 'none';
                button.disabled = false;
            });
    });

    function pollStatus() {
        let attempts = 0;
        const maxAttempts = 20; // ~60 сек
        const interval = setInterval(() => {
            fetch('plugins/Speedtest/tmp/status.json')
                .then(res => res.ok ? res.json() : Promise.reject('No status'))
                .then(status => {
                    if (status.done) {
                        clearInterval(interval);
                        refreshCharts();
                        fetchLogPreview();
                        button.disabled = false;
                        spinner.style.display = 'none';
                    } else {
                        fetchLogPreview();
                    }
                })
                .catch(() => {
                    if (++attempts > maxAttempts) {
                        clearInterval(interval);
                        button.disabled = false;
                        spinner.style.display = 'none';
                        alert('Speedtest did not respond in time.');
                    }
                });
        }, 3000);
    }

    function refreshCharts() {
        document.querySelectorAll('#charts-container img').forEach(img => {
            const baseSrc = img.src.split('?')[0];
            img.src = `${baseSrc}?t=${Date.now()}`;
        });
    }

    function fetchLogPreview() {
        fetch('plugins/Speedtest/tmp/speedtest.log')
            .then(res => res.ok ? res.text() : '')
            .then(text => {
                liveResult.textContent = text.trim().split('\n').slice(-10).join('\n');
            });
    }
</script>


</body>

</html>