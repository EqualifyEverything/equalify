<?php
// Get alert ID.
$alert_id = $_GET['id'];
if(empty($alert_id))
    throw new Exception('You have not supplied an alert id');

// Now lets get the alert.
$filtered_to_alert = array(
    array(
        'name' => 'id',
        'value' => $alert_id
    )
);
$report = (array)DataAccess::get_db_rows(
    'alerts', $filtered_to_alert
)['content'][0];
?>

<div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
    <div>
        <h1>
        
            <?php 
            // Name
            echo 'Alert '.$report['id'].' Details';
            ?>

        </h1>
    </div>
    <div class="lead">
        <span class="badge text-bg-dark" aria-describe="Website URL">
            <?php echo $report['url'];?>
        </span>

        <?php
        // Create badges for tags.
        $tags = $report['tags'];
        if(!empty($tags)){
            $tag_array = preg_split ("/\,/", $tags);
            foreach($tag_array as $tag){
                
                // Get Tag Title
                if(!empty($tag)){
                    $filtered_to_tag = array(
                        array(
                            'name' => 'slug',
                            'value' => $tag
                        )
                    );
                    $tag_info = (array)DataAccess::get_db_rows(
                        'tags', $filtered_to_tag
                    )['content'][0];
                    echo '<span class="badge bg-secondary" aria-describe="Tag">'.$tag_info['title'].'</span> ';
                }

            }
        }
        ?>

    </div>
</div>
<section id="meta_settings" class="mb-3 pb-3 border-bottom">

    <p class="lead">

        <?php 
        // Name
        echo $report['message'];
        ?>

    </p>

    <?php
    // Begin more info.
    if(!empty($report['more_info'])):
    ?>
    
    <h2>More Info</h2>

    <?php
    // This was formatted for axe-core.
    $count = 0;
    foreach(unserialize($report['more_info']) as $info){

        // Setup axe-core items
        if(empty($info->any[0]) && !empty($info->all[0])){
            $message = $info->all[0]->message;
        }elseif(!empty($info->any[0]) && empty($info->all[0])){
            $message = $info->any[0]->message;
        }else{
            $message = '';
        }

        $count++;
        echo '<div id="'.$count.'">';
        echo '<h3>'.$message.'</h3>';
        echo '<pre aria-describe="code snippet" class="rounded bg-secondary 
        text-white p-3 mb-1"><code>'.$info->html.'</code></pre>';
        echo '</div>';
    }
    ?>

    <?php
    //End more info.
    endif;
    ?>

</section>