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
        // Show Page Sites
        $filters = [
            array(
                'name'  => 'is_parent',
                'value' => '1'
            ),
        ];
        $pages = DataAccess::get_pages($filters);
        if($pages != NULL ):
            foreach($pages as $page):    
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    echo DataAccess::get_page_badge($page);
                    ?>

                    <?php
                    // The Type Badge
                    the_page_type_badge($page->type);
                    ?>
                    
                    <h2 class="h5 card-title">
                        <?php echo $page->site; ?> 
                    </h2>

                    <?php
                    // Button status depends on site status.
                    if($page->status == 'archived'){
                        $button_text = 'Activate Site';
                        $button_class = 'btn-outline-success';
                    }else{
                        $button_text = 'Archive Site';
                        $button_class = 'btn-outline-secondary';
                    }
                    ?>

                    <a class="btn <?php echo $button_class;?> btn-sm mt-2" href="actions/toggle_page_status.php?id=<?php echo $page->id;?>&old_status=<?php echo $page->status;?>">
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