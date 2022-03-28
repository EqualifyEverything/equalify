<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Scans</h1>
        </div>
        <div>
            <a href="actions/scan_all_properties.php" class="btn btn-primary">Scan All Properties</a>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Property</th>
                <th scope="col">Status</th>
            </tr>
        </thead>

        <?php
        // Begin Scans
        $scans = get_scans($db);
        if(count($scans) > 0 ): foreach($scans as $scan):    
        ?>

        <tr>
            <td><?php echo $scan->time;?></td>
            <td>

                <?php                 
                // Set $property
                $property = get_property($db, $scan->property_id);
                ?>

                <a href="<?php the_property_view_uri($db, $scan->property_id);?>">

                    <?php echo $property->url;?>

                </a>
            </td>
            <td><?php echo ucwords($scan->status);?></td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="4">No scans found.</td>
        </tr>

        <?php 
        // End Scans
        endif;
        ?>

    </table>    
</section>