<?php
// Set Page ID with optional fallboack.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if($id == false)
    throw new Exception('Format of page ID "'.$id.'" is invalid');

// Check if Page exists.
$page = get_page($db, $id);

if(empty($page) == 1)
    throw new Exception('There is no record of page "'.$id.'"');
?>

<div class="mb-3 pb-4 border-bottom">

    <h1>
    
        <?php 
        // Title
        echo $page->site;
        ?>

        <span class="float-end">
        
        <?php
        // Badge
        echo get_page_badge($db, $page);
        ?>

        </span>
    </h1>

</div>

<section id="relatives" class="mb-3 pb-4">
    <h2>Pages</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">URL</th>
                <th scope="col">Scanned</th>
            </tr>
        </thead>
        <tbody>

            <?php
            $page_filters = [
                array(
                    'name'  => 'site',
                    'value' => $page->site
                ),
            ];
            $site = get_pages($db, $page_filters);
            $site_count = count($site);
            if($site_count > 0 ):
                foreach($site as $page):    
            ?>

            <tr>
                <td>
                    <a href="<?php echo $page->url;?>" target="_blank"><?php echo $page->url;?></a>
                </td>

                <td>
                    <?php echo $page->scanned; ?>
                </td>
            </tr>

            <?php 
                endforeach;
            endif;
            ?>

        </tbody>
    </table>
</section>
<section id="alerts" class="mb-3 pb-4">
    <h2>Alerts</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Page</th>
                <th scope="col">Details</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        $alerts = get_alerts_by_site($db, $page->site);
        if(count($alerts) > 0 ):
            foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo get_page_url($db, $alert->page_id);?></td>
            <td><?php echo $alert->details;?></td>
            <td>
                <a href="actions/delete_alert.php?id=<?php echo $alert->id;?>&site_details_redirect=<?php echo $id;?>" class="btn btn-outline-secondary btn-sm">
                    Dismiss
                </a>
            </td>
        </tr>

        <?php 
            endforeach;
        else:
        ?>

        <tr>
            <td colspan="4">No alerts found.</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="page_options" class="mb-3 pb-4">
    <h2 class="pb-2">Options</h2>

    <?php
    // Set button to status conditions.
    if($page->status == 'archived'){
        $button_text = 'Activate Site';
        $button_class = 'btn-outline-success';
    }else{
        $button_text = 'Archive Site and Delete Alerts';
        $button_class = 'btn-outline-danger';
    }
    ?>

    <a href="actions/toggle_page_status.php?id=<?php echo $page->id;?>&old_status=<?php echo $page->status;?>" class="btn <?php echo $button_class;?>">
        <?php echo $button_text;?>
    </a>

</section>