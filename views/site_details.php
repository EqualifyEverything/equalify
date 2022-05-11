<?php
// Set Page ID with optional fallboack.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if($id == false)
    throw new Exception('Format of page ID "'.$id.'" is invalid');

// Check if Page exists.
$page = DataAccess::get_page($id);

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
        echo DataAccess::get_page_badge($page);
        ?>

        </span>
    </h1>

</div>
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
        $alerts = DataAccess::get_alerts_by_site($page->site);
        if(count($alerts) > 0 ):
            foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo DataAccess::get_page_url($alert->page_id);?></td>
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

<?php
// Since we can add columns via integrations,
// we need to dynamically get columns.
$columns = DataAccess::get_column_names('pages');
if(!empty($columns)):
?>

<section id="relatives" class="mb-3 pb-4">
    <h2>Pages</h2>

    <table class="table">
        <thead>

            <tr>

    <?php
    foreach ($columns as $column){

        // Users don't need to see a few columns
        // because they cannot act on these or they
        // are listed elsewhere.
        $excluded_columns = array('is_parent', 'type', 'id');
        if( !in_array($column->COLUMN_NAME, $excluded_columns )){
            
            // Make column name human readable and formatted
            // with the various special conditions
            $name = $column->COLUMN_NAME;
            $name = str_replace('_', ' ', $name);
            $name = str_replace('url', 'URL', $name);
            $name = str_replace('wcag', 'WCAG', $name);
            $name = str_replace('2 1', '2.1', $name);
            $name = str_replace('wave', 'WAVE', $name);
            $name = ucwords($name);
            echo '<th scope="col">'.$name.'</th>';
 
        }
    }
    ?>

            </tr>
        </thead>
        <tbody>

    <?php
    // Limit pages to those related to this site.
    $page_filters = [
        array(
            'name'  => 'site',
            'value' => $page->site
        ),
    ];
    $site = DataAccess::get_pages($page_filters);
    $site_count = count($site);
    if($site_count > 0 ):
        foreach($site as $page):    
            echo '<tr>';

            // Again, we need to loop columns so that integrations
            // can add columns if they register new DB fields.
            foreach ($columns as $column){
        
                // Limiting columns again.
                $excluded_columns = array('is_parent', 'type', 'id');
                if( !in_array($column->COLUMN_NAME, $excluded_columns )){
                    
                    // Make column name human readable.
                    $text = $column->COLUMN_NAME;
                    echo '<td scope="col">'.$page->$text.'</td>';
         
                }
;
            }
        
        // End pages list.
            echo '</tr>';
        endforeach;
    endif;
    ?>

        </tbody>
    </table>
    <?php
    // End dynamically created columns.
    endif;
    ?>

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