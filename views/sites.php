<h1 class="mb-3 pb-4 border-bottom">All Sites</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="event event-success" role="event">Sites are updated.</div>'
?>

<div class="row row-cols-3 g-4 pb-4 border-bottom">
    <?php
    $sites = get_sites($db);
    if(count($sites) > 0 ):
        foreach($sites as $site):    
    ?>

    <div class="col">
        <div class="card">
            <div class="card-body">

                <?php
                // Badge info
                $alert_count = count(get_event_alerts_by_site($db, $site->id));
                if($alert_count == 0){
                    $badge_status = 'bg-success';
                    $badge_content = '✅ Equalified';
                }else{
                    $badge_status = 'bg-danger';
                    $badge_content = $alert_count.' Alerts';
                };
                ?>
                <span class="badge mb-2 <?php echo $badge_status;?>"> <?php echo $badge_content;?> </span>
                <h5 class="card-title">
                    <?php echo $site->title; ?>
                </h5>
                <h6 class="card-subtitle mb-2 text-muted">
                    <?php echo $site->url; ?> 
                </h6>
                <a type="button" class="btn btn-outline-primary btn-sm" href="?view=site_details&id=<?php echo $site->id;?>">View Details</a>
            </div>
        </div>
    </div>
    
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
</div>
<div class="my-3">
    <button class="btn btn-primary">Scan All Sites</button>
    <div class="form-text">
        <?php echo count($sites)*5;?> credits will be charged to scan <?php echo count($sites);?> sites.
    </div>
</div>
<!-- <hr>
<h2>Add a Site</h2>
<form action="actions/add_and_test_site.php" method="get" >
    <label for="url" class="form-label">Site URL</label>
    <input type="text" class="form-control" name="url" aria-describedby="url_help" placeholder="https://decubing.com" value="https://decubing.com" >
    <div class="form-text">
        Currently supports WordPress 4.2 - 5.9.2 sites with API enabled.
    </div>
    <button type="submit" class="btn btn-primary my-3">Add and Test Site</button>
</form> -->