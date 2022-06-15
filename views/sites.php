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
        $sites = DataAccess::get_sites();
        if($sites != NULL ):
            foreach($sites as $site):  
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    $status = DataAccess::get_site_status($site);
                    if($status == 'archived'){
                        $bg_class = 'bg-dark';
                        $text = 'Archived';
                    }else{
                        $bg_class = 'bg-success';
                        $text = 'Active';
                    }
                    echo '<span class="badge mb-2 '.$bg_class.'">'.$text.'</span>';
                    ?>

                    <?php
                    // The Type Badge
                    $type = DataAccess::get_site_type($site);
                    if($type == 'wordpress'){
                        $type = 'WordPress';
                    }elseif($type == 'xml'){
                        $type = 'XML';
                    }elseif($type == 'single_page'){
                        $type = 'Single Page';
                    }
                    echo '<span class="badge bg-light text-dark">'
                        .$type.
                        '<span class="visually-hidden"> Site Type</span></span>';
                    ?>
                    
                    <h2 class="h5 card-title">
                        <?php echo $site; ?> 
                    </h2>

                    <?php
                    // Button status depends on site status.
                    if($status == 'archived'){
                        $button_text = 'Activate';
                        $button_class = 'btn-outline-success';
                    }else{
                        $button_text = 'Archive';
                        $button_class = 'btn-outline-secondary';
                    }
                    ?>

                    <a class="btn <?php echo $button_class;?> btn-sm mt-2" href="actions/toggle_site_status.php?site=<?php echo $site;?>&old_status=<?php echo $status;?>">
                        <?php echo $button_text;?>
                    </a>
                </div>
            </div>
        </div>
        
        <?php 
            endforeach;
        else:
        ?>

            <p>No sites exist.</p>

        <?php 
        endif;
        ?>

        </tbody>
    </div>
</section>