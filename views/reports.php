<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the reports setting's view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
?>

<div class="d-flex flex-column flex-md-row align-items-center my-4">
    <h1>Reports</h1>
    <div class="ms-md-auto">
        <a class="btn btn-primary" href="index.php?view=report_settings">New Report</a>
    </div>
</div>


<div id="reports_content" class="row row-cols-1 row-cols-md-3 g-4 justify-content-md-center">

<?php
// Show Scan Profiles
$reports = DataAccess::get_db_rows(
    'reports', [], get_current_page_number()
);
if( count($reports['content']) > 0 ):
    foreach($reports['content'] as $report): 
?>

    <div class="col">
        <div class="card">
            <div class="card-body">
d                <h2 class="h5 card-title p-2 my-2">
                    <?php echo $report->title;?>
                </h2>
                <table class="table table-borderless table-hover ">
                    <thead class="visually-hidden">
                        <tr>
                        <th scope="col">First</th>
                        <th scope="col">Last</th>
                        <th scope="col">Handle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <th scope="row">Mark</th>
                        <td>Otto</td>
                        <td>@mdo</td>
                        </tr>
                        <tr>
                        <th scope="row">Jacob</th>
                        <td>Thornton</td>
                        <td>@fat</td>
                        </tr>
                        <tr>
                        <th scope="row">Larry</th>
                        <td>Thornton</td>
                        <td>@twitter</td>
                        </tr>
                    </tbody>
                </table>
                <a href="index.php?view=report&id=<?php echo $report->id;?>" class="btn btn-secondary m-2">
                    View Report
                </a>
            </div>
        </div>
    </div>

<?php 
// End Reports
 endforeach; endif;
?>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
    createChart('chart1', 'Dataset 1', [110, 120, 130, 140, 150, 230], 'rgba(11, 101, 21, 1)');
    createChart('chart2', 'Dataset 2', [210, 220, 230, 240, 250, 330], 'rgba(101, 11, 21, 1)');
    createChart('chart3', 'Dataset 3', [310, 320, 330, 340, 350, 430], 'rgba(21, 11, 101, 1)');
});

function createChart(canvasId, label, data, borderColor) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jul '23", "Aug '23", "Sep '23", "Oct '23", "Nov '23", "Dec '23"],
            datasets: [
                {
                    label: label,
                    fill: true,
                    data: data,
                    borderColor: borderColor,
                    backgroundColor: borderColor.replace('1)', '0.6)'),
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            elements: {
                line: {
                    borderWidth: 2
                }
            },
            scales: {
                x: {
                    grid:{
                        display: false
                    },
                    ticks: {
                        display: false
                    },
                    border: {
                        color: borderColor,
                        width: 2
                    }
                },
                y: {
                    grid:{
                        display: true,
                    },
                    ticks: {
                        display: false,
                    },
                    border: {
                        display: false
                    }
                }

            }
        }
    });
}
</script>