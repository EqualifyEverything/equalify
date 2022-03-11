<h1>All Sites</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">New site was added and equalified.</div>'
?>

<table class="table">
    <thead>
        <tr>
            <th scope="col">Status</th>
            <th scope="col">URL</th>
            <th scope="col">WCAG Errors</th>
            <th scope="col">Action</th>
        </tr>
    </thead>
    <tbody>

    <?php
    $records = get_all_sites($db);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>
        <tr>
            <td><?php echo $record->status; ?> </td>
            <td><?php echo $record->url; ?> </td>
            <td>TBA</td>
            <td><a href="?view=site_detail&id=<?php echo $record->id;?>">View Details</a></td>
        </tr>
    <?php 
        endforeach;
    else:
    ?>

        <tr>
            <td colspan="4">No sites have been equalified.</td>
        </tr>

    <?php 
    endif;
    ?>

    </tbody>
</table>
<h2> New Site</h2>
<form action="actions/equalify_site.php" method="get" >
    <label for="url" class="form-label">Site URL</label>
    <input type="text" class="form-control" name="url" aria-describedby="url_help" placeholder="https://decubing.com" value="https://decubing.com" >
    <div class="form-text">
        One credit is subtracted for every page that is equalified.
    </div>
    <button type="submit" class="btn btn-primary my-4">Equalify Site</button>
</form>