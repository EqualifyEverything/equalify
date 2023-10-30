<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the single alert view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Get notice ID.
$notice_id = $_GET['id'];
if(empty($notice_id))
    throw new Exception('You have not supplied a notice id');

// Now lets get the notice.
$filtered_to_notice = array(
    array(
        'name' => 'id',
        'value' => $notice_id
    )
);
$report = (array)DataAccess::get_db_rows(
    'notices', $filtered_to_notice
)['content'][0];
?>

<div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
    <div class="w-50">
        <h1>
        
            <?php 
            // Name
            echo 'Notice '.$report['id'].' Details';
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
            echo '<div class="mb-3 pb-3 border-bottom" id="error-'.$count.'">';
            echo '<h3 class="fs-5">'.$message.'</h3>';
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