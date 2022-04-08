<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Sites</h1>
        </div>
        <div>
            <a href="?view=site_adder" class="btn btn-primary">Add Site</a>
        </div>
    </div>
    <div class="row row-cols-3 g-4 pb-4">
        <table class="sites table">
            <thead>
                <tr>
                    <th scope="col">Site</th>
                    <th scope="col">Status</th>
                    <th scope="col">Scanned</th>
                    <th scope="col">Type</th>
                    <th scope="col">Alerts</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $properties = get_properties($db);
                if(count($properties) > 0 ):
                    foreach($properties as $property):    
                ?>

                <tr <?php if($property->is_parent == 1) echo 'class="parent"'?>>
                    <td>
                        <?php echo $property->group; ?> 
                    </td>
                    <td>
                        <?php echo $property->status; ?> 
                    </td>
                    <td>
                        <?php echo $property->scanned; ?> 
                    </td>
                    <td>
                        <?php echo $property->type; ?> 
                    </td>
                    <td>
                    </td>
                </tr>
            
            <?php 
                endforeach;
            else:
            ?>

                <tr>
                    <td span="4">
                        No properties exist.
                    </td>
                </tr>

            <?php 
            endif;
            ?>
        
            </tbody>
        </table>
    </div>
</section>