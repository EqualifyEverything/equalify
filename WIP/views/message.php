<?php
// The page_id URL parameter defines the page.
$message_id = $_GET['message_id'] ;
if($message_id == ''){
    throw new Exception(
        'message_id is missing'
    );
}

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/title.php');
require_once('components/body.php');
require_once('components/message_occurrences_list.php');

?>

<div class="d-flex flex-column flex-md-row align-items-center my-4">

    <?php
    // Messsage Title
    the_title($message_id, 'message');
    ?>

</div>

<div class="row">
    <div class="col-6">
        <div class="card p-4 h-100">
            <h2 >AI Analysis <span class="badge bg-secondary">Experimental</span></h2>
            <p class="lead">Your code is missing alt text.</p>
            <p>From available Equalify data on this message, lorem ipsum dolor sit amet, consectetur adipisicing elit. Praesentium aspernatur sint minima non, dolores consectetur veritatis eos iusto fugit totam nobis veniam officia modi accusantium amet sequi, incidunt porro iste.</p>
            <p class="text-body-secondary border-top pt-2"><strong>NOTE: This feature is experimental.</strong> We're testing how large language models interpret your scan results. <a href="#" class="link-secondary">Contact us</a> to let us know what you think.</p>
        </div>
    </div>
    <div class="col-6">
        <div class="card p-4">
            <h2 class="visually-hidden">Occurrences Over Time</h2>
            <canvas id="equalifiedByTime" class="" style="height: 300px"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                var ctx = document.getElementById('equalifiedByTime').getContext('2d');
                var equalifiedByTime = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ["Jul '23", "Aug '23", "Sep '23", "Oct '23", "Nov '23", "Dec '23"],
                        datasets: [
                            {
                                label: 'Equalified',
                                data: [1, 2, 2, 2, 3, 3],
                                borderColor: 'rgba(11, 101, 21, 1)',
                            }
                            ,{
                                label: 'Ignored',
                                data: [4, 5, 10, 5, 0, 4],
                                borderColor: 'rgba(108, 117, 125, 1)',
                            }
                            ,{
                                label: 'Active',
                                data: [6, 5, 11, 9, 14, 10],
                                borderColor: 'rgba(171, 39, 12, 1)',
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
                                borderWidth: 8
                            }
                        }
                    }
                });
            });
            </script>
        </div>
    </div>
</div>

<?php
// Message Occurrences
the_message_occurrences_list("messages=$message_id");
?>