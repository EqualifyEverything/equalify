<div class="mb-3 pb-4 border-bottom">
    <h1>All Sites</h1>
</div>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="event event-success" role="event">Sites are updated.</div>'
?>

<section class="mb-3 pb-4">
    <div class="row row-cols-3 g-4 pb-4">
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
                    $alert_count = count(get_alerts_by_site($db, $site->id));
                    if($alert_count == 0){
                        $badge_status = 'bg-success';
                        $badge_content = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16"><path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/><path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/></svg> Equalified';
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
</section>
<section class="my-3 pb-4">
    <button class="btn btn-primary">Rescan All Sites</button>
    <div class="form-text">
        <?php echo count($sites)*5;?> credits will be charged to scan <?php echo count($sites);?> sites.
    </div>
</section>
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