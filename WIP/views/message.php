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
require_once('components/chart.php');
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
            <p>From available Equalify data on this message, lorem ipsum dolor sit amet, consectetur adipisicing elit. Praesentium aspernatur sint minima non, dolores consectetur veritatis eos iusto fugit totam nobis veniam officia modi accusantium amet sequi, incidunt porro iste.</p>
            <p class="text-body-secondary border-top pt-2"><strong>NOTE: This feature is experimental.</strong> We're testing how large language models interpret your scan results. <a href="#" class="link-secondary">Contact us</a> to let us know what you think.</p>
        </div>
    </div>
    <div class="col-6">

        <?php
        // Chart
        the_chart("messages=$message_id");
        ?>

    </div>
</div>

<?php
// Message Occurrences
the_message_occurrences_list("messages=$message_id");
?>