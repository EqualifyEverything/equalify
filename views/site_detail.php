<?php
// Set Site ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check for correct info
if($id == false || empty(get_site_url($db, $id)) == 1):
    throw new Exception('Site ID format is invalid or site does not exist.');

// Begin Content
else:
?>

<h1><?php echo get_site_url($db, $id);?></h1>

<h2>Pages</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">WCAG Errors</th>
            <th scope="col">URL</th>
        </tr>
    </thead>
    <tbody>

    <?php
    $records = get_all_pages($db, $id);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>
        <tr>
            <td><?php echo $record->wcag_errors; ?> </td>
            <td><?php echo $record->url; ?> </td>
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

<h2>Site Actions</h2>
<a href="actions/delete_site_and_pages.php?id=<?php echo $id;?>" class="btn btn-outline-danger">Permanently Delete Site and Pages</a>

<?php
// End Content
endif;
?>