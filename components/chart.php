<?php
// Creates chart.js to include status by time and a table for screen reader users.
function the_chart($filters = '')
{
?>

<div class="card p-4 h-100">
    <h3 class="visually-hidden">Status Occurrences Over Time</h3>
    <canvas id="statusChart" width="400" height="180" style="display: none;"></canvas>
    <div id="noDataMessage"></div>
    <div id="chartAccessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
    <div id="statusDataTable" class="visually-hidden">
        <table aria-describedby="statusDataTable">
            <thead>
                <tr>
                    <th>Month</th>
                    <!-- Dataset headers will be added here -->
                </tr>
            </thead>
            <tbody>
                <!-- Data rows will be added here -->
            </tbody>
        </table>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const announcer = document.getElementById('chartAccessibilityAnnouncer');
            announcer.textContent = 'Loading chart data, please wait.';

            const url = 'api/index.php?request=chart&<?php echo $filters; ?>';
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    announcer.textContent = 'Chart data loaded.';

                    // Check if data is empty or not
                    if (data.labels.length === 0 || data.datasets.every(dataset => dataset.data.length === 0)) {
                        document.getElementById('noDataMessage').innerHTML = '<p>No chart data found.</p>';
                        announcer.textContent = 'No chart data found.';
                    } else {
                        populateDataTable(data);
                        loadChartJsAndDisplayChart(data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('noDataMessage').innerHTML = '<p>Error loading chart data.</p>';
                    announcer.textContent = 'Error loading chart data.';
                });
        });

        function populateDataTable(data) {
            var tableHead = document.querySelector('#statusDataTable thead tr');
            var tableBody = document.querySelector('#statusDataTable tbody');

            data.datasets.forEach(dataset => {
                var th = document.createElement('th');
                th.textContent = dataset.label;
                tableHead.appendChild(th);
            });

            data.labels.forEach((label, index) => {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                td.textContent = label;
                tr.appendChild(td);

                data.datasets.forEach(dataset => {
                    var td = document.createElement('td');
                    td.textContent = dataset.data[index];
                    tr.appendChild(td);
                });

                tableBody.appendChild(tr);
            });

            document.getElementById('statusDataTable').style.display = 'block';
        }

        function loadChartJsAndDisplayChart(data) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => renderChart(data);
            document.head.appendChild(script);
        }

        function renderChart(data) {
            var ctx = document.getElementById('statusChart').getContext('2d');
            document.getElementById('statusChart').style.display = 'block';
            new Chart(ctx, {
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
