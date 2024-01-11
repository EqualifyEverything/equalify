<?php
// Creates chart.js to include status by time.
function the_chart($filters = '')
{
?>

<div class="card p-4 h-100">
    <h3 class="visually-hidden">Status Occurrences Over Time</h3>
    <canvas id="statusChart" width="400" height="180" style="display: none;"></canvas>
    <div id="noDataMessage"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const url = 'api?request=chart&<?php echo $filters; ?>';
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Check if data is empty or not
                    if (data.labels.length === 0 || data.datasets.every(dataset => dataset.data.length === 0)) {
                        // Display the message and keep the canvas hidden
                        document.getElementById('noDataMessage').innerHTML = '<p>No chart data found.</p>';
                    } else {
                        // Load Chart.js dynamically and display the chart
                        loadChartJsAndDisplayChart(data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('noDataMessage').innerHTML = '<p>No chart data</p>';
                });
        });

        function loadChartJsAndDisplayChart(data) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => renderChart(data);
            document.head.appendChild(script);
        }

        function renderChart(data) {
            var ctx = document.getElementById('statusChart').getContext('2d');
            document.getElementById('statusChart').style.display = 'block';
            var statusChart = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    elements: {
                        line: {
                            borderWidth: 8
                        }
                    }

                }
            });
        }
    </script>
</div>

<?php
}
?>