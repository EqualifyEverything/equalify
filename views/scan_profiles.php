<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the site's view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

?>

<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Scan Profiles</h1>
        </div>
        <div>
            <a href="?view=new_scan_profile" class="btn btn-primary">New Profile</a>
        </div>
    </div>
    <div class="row row-cols-3 g-4 pb-4">
        
<?php
// Show Sites
$sites = DataAccess::get_db_rows(
    'sites', [], get_current_page_number()
);
if( count($sites['content']) > 0 ):
    foreach($sites['content'] as $site):  
?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    $status = $site->status;
                    if($status == 'archived'){
                        $extra_classes = 'bg-dark';
                        $text = 'Archived';
                    }elseif(empty($site->scanned)){
                        $extra_classes = 'bg-warning text-dark';
                        $text = 'Unscanned';
                    }else{
                        $extra_classes = 'bg-success';
                        $text = 'Active';
                    }
                    ?>

                    <span class="badge mb-2 <?php echo $extra_classes;?>">
                        <?php echo $text;?>
                    </span>

                    <?php
                    // The Type Badge
                    // TODO: Make this dynamic, depending on the scan functions in the active integrations.
                    $type = $site->type;
                    if($type == 'a11ywatch_scan'){
                        $type = 'A11yWatch Scan';
                    }elseif($type == 'a11ywatch_sitemap_scan'){
                        $type = 'A11yWatch Sitemap Scan';
                    }elseif($type == 'a11ywatch_crawl'){
                        $type = 'A11yWatch Crawl';
                    }
                    ?>

                    <span class="badge bg-light text-dark">
                        <?php echo $type;?>
                        <span class="visually-hidden">
                            Scan Type
                        </span>
                    </span>

                    <h2 class="h5 card-title">
                        <?php echo $site->url; ?> 
                    </h2>

                    <?php
                    // Button status depends on site status.
                    if($status == 'archived'){
                        $button_text = 'Activate';
                        $button_class = 'btn-outline-success';
                        $processing = false;
                    }else{
                        $button_text = 'Archive';
                        $button_class = 'btn-outline-secondary';
                        $processing = false;
                    }
                    ?>

                    <a class="btn <?php echo $button_class;?> btn-sm mt-2"  href="actions/toggle_scan_profile_status.php?id=<?php echo $site->id;?>&old_status=<?php echo $status;?>">
                        <?php if($processing) echo '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'; echo $button_text;?>
                    </a>
                </div>
            </div>
        </div>
        
        <?php 
        // Fallback.
        endforeach; else:
        ?>

            <p>No sites exist.</p>

        <?php 
        // End Sites
        endif;
        ?>

    </div>

    <?php
    // The pagination
    the_pagination($sites['total_pages']);
    ?>

</section>