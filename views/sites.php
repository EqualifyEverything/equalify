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
        $pages = get_pages($db, $filters);
        if($pages != NULL ):
            foreach($pages as $page):    
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    echo get_page_badge($db, $page);
                    ?>

                    <?php
                    // The Type Badge
                    the_page_type_badge($page->type);
                    ?>
                    
                    <h2 class="h5 card-title">
                        <?php echo $page->site; ?> 
                    </h2>
                    <a type="button" class="btn btn-outline-primary btn-sm mt-2" href="?view=site_details&id=<?php echo $page->id;?>">View Details</a>
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