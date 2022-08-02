<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the sites view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

?>

<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Sites</h1>
        </div>
        <div>
            <a href="?view=new_site" class="btn btn-primary">Add Site</a>
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
                    $type = $site->type;
                    if($type == 'wordpress'){
                        $type = 'WordPress';
                    }elseif($type == 'xml'){
                        $type = 'XML';
                    }elseif($type == 'single_page'){
                        $type = 'Single Page';
                    }
                    ?>

                    <span class="badge bg-light text-dark">
                        <?php echo $type;?>
                        <span class="visually-hidden">
                            Site Type
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

                    <a class="btn <?php echo $button_class;?> btn-sm mt-2"  href="actions/toggle_site_status.php?site=<?php echo $site->url;?>&old_status=<?php echo $status;?>">
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