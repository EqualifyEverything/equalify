<h1>All Sites</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">Sites are updated.</div>'
?>

<table class="table">
    <thead>
        <tr>
            <th scope="col">Alerts</th>
            <th scope="col">Site</th>
        </tr>
    </thead>
    <tbody>

    <?php
    $records = get_all_sites($db);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>

    <tr>
        <td>
            <a href="?view=site_details&id=<?php echo $record->id;?>#events">
                <?php echo count(get_alerts_by_site($db, $record->id));?>
            </a>
        </td>
        <td>
            <a href="?view=site_details&id=<?php echo $record->id;?>">
                <?php echo $record->url; ?> 
            </a>
        </td>
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
<hr>
<h2>Add a Site</h2>
<form action="actions/equalify_site.php" method="get" >
    <label for="url" class="form-label">Site URL</label>
    <input type="text" class="form-control" name="url" aria-describedby="url_help" placeholder="https://decubing.com" value="https://decubing.com" >
    <div class="form-text">
        Currently supports WordPress 4.2 - 5.9.2 sites with API enabled.
    </div>
    <button type="submit" class="btn btn-primary my-3">Equalify Site</button>
</form>