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
    <div class="w-50">
        <h1>
        
            <?php 
            // Name
            echo 'Alert '.$report['id'].' Details';
            ?>

        </h1>
    </div>
    <div class="lead">
        <span class="badge text-bg-dark" aria-describe="Website URL">

            <?php 
            // Show first 20 characters of the url.
            if(strlen($report['url']) > 20){
                echo substr($report['url'], 0, 20).'...';
            }else{
                echo $report['url'];
            }
            ?>

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
<section id="meta_settings" class="mb-3 pb-3">

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
    
    <h2 class="fs-3">More Info</h2>

        <?php
        // This was formatted for a pretty-printed JSON dump.
        $info_pieces = json_decode($report['more_info'], true);
        $escaped_info = htmlspecialchars( $report['more_info'], 
            ENT_NOQUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false );
        if (empty($info_pieces)) {
            echo '<div><p>No additional info found for this alert</p></div>';
        } else {
            echo '<pre aria-describe="code snippet" class="rounded bg-secondary 
            text-white p-3 mb-1"><code>'.$escaped_info.'</code></pre>';
            // $count = 0;
            // foreach($info_pieces as $info_key => $info_val){
            //     $count++;
            //     echo '<div class="mb-3 pb-3 border-bottom" id="error-'.$count.'">';
            //     echo '<h3 class="fs-5">'.$info_key.'</h3>';
            //     echo '<pre aria-describe="code snippet" class="rounded bg-secondary 
            //     text-white p-3 mb-1"><code>'.$info_val.'</code></pre>';
            //     echo '</div>';
            // }
        }
        ?>

    <?php
    //End more info.
    endif;
    ?>

</section>