<?php
// Set Site ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check for correct info
if($id == false)
    throw new Exception('Site ID format is invalid.');

// Check if Site exists
$site = get_site($db, $id);
if(empty($site) == 1)
    throw new Exception('Site does not exist.');

// Set Account Info
$account_info = get_account($db, USER_ID);
?>

<div class="mb-3 pb-4 border-bottom">
    <h1><?php echo $site->title;?></h1>
    <a class="h2 link-secondary" href="<?php echo $site->url;?>" target="_blank"><?php echo $site->url;?></a>
</div>
<section id="pages" class="mb-3 pb-4 border-bottom">
    <h2>Pages</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">URL</th>
                <th scope="col">WCAG Errors</th>
            </tr>
        </thead>
        <tbody>

        <?php
        $pages = get_site_pages($db, $id);
        if(count($pages) > 0 ):
            foreach($pages as $page):    
        ?>
            <tr>
                <td><?php echo $page->url; ?></td>
                <td>
                    
                    <?php 
                    // Link to page accessibility inspector
                    if($account_info->accessibility_testing_service == 'Little Forrest')
                        $wcag_inspector_url = 'https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.$page->url;
                        if($account_info->accessibility_testing_service == 'WAVE')
                        $wcag_inspector_url = 'https://wave.webaim.org/report#/'.$page->url
                    ?>

                    <a href="<?php echo $wcag_inspector_url;?>" target="_blank"><?php echo $page->wcag_errors; ?></a>
                </td>
            </tr>
        <?php 
            endforeach;
        else:
        ?>

            <tr>
                <td colspan="2">Site has no pages.</td>
            </tr>

        <?php 
        endif;
        ?>

        </tbody>
    </table>
</section>
<section id="events" class="mb-3 pb-4 border-bottom">
    <h2>Related Events</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Time</th>
                <th scope="col">Status</th>
            </tr>
        </thead>

        <?php
        $events = get_events_by_site($db, $id);
        if(count($events) > 0 ):
            foreach($events as $event):    
        ?>

        <tr>
            <td><?php echo $event->type;?></td>
            <td><?php echo $event->time;?></td>
            <td><?php echo ucwords($event->status);?></td>
        </tr>

        <?php 
            endforeach;
        else:
        ?>

        <tr>
            <td colspan="2">No events found.</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="site_options" class="mb-3 pb-4 border-bottom">
    <h2 class="pb-2">Site Options</h2>
    <a href="" class="btn btn-primary">Rescan Site</a>
    <div class="d-inline">
        <a href="actions/delete_site_data.php?id=<?php echo $site->id;?>" class="btn btn-outline-danger">Perminently Delete Site, Pages, and Events</a>
    </div>
</section>