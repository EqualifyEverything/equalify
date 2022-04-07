<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Properties</h1>
        </div>
        <div>
            <a href="?view=property_adder" class="btn btn-primary">Add Property</a>
        </div>
    </div>
    <div class="row row-cols-3 g-4 pb-4">
        
        <?php
        // Show Property Groups
        $filters = [
            array(
                'name'  => 'is_parent',
                'value' => '1'
            ),
        ];
        $properties = get_properties($db, $filters);
        if(count($properties) > 0 ):
            foreach($properties as $property):    
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    echo get_property_badge($db, $property);
                    ?>

                    <?php
                    // The Type Badge
                    the_property_type_badge($property->type);
                    ?>
                    
                    <h2 class="h5 card-title">
                        <?php echo $property->group; ?> 
                    </h2>
                    <a type="button" class="btn btn-outline-primary btn-sm mt-2" href="?view=property_details&id=<?php echo $property->id;?>">View Details</a>
                </div>
            </div>
        </div>
        
        <?php 
            endforeach;
        else:
        ?>

            <p>No properties exist.</p>

        <?php 
        endif;
        ?>

        </tbody>
    </div>
</section>