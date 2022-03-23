
<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="event event-success" role="event">Properties are updated.</div>'
?>

<section>
    <div class="mb-3 pb-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Properties</h1>
        </div>
        <div>
            <button class="btn btn-outline-primary">Scan All Properties</button>
        </div>
    </div>
    <div class="row row-cols-3 g-4 pb-4">
        
        <?php
        $properties = get_properties($db, 'parents');
        if(count($properties) > 0 ):
            foreach($properties as $property):    
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // The Status Badge
                    the_status_badge($property);
                    ?>
                    
                    <h5 class="card-title">
                        <?php echo $property->url; ?> 
                    </h5>
                    <a type="button" class="btn btn-outline-secondary btn-sm" href="?view=property_details&id=<?php echo $property->id;?>">View Details</a>
                </div>
            </div>
        </div>
        
        <?php 
            endforeach;
        else:
        ?>

            <tr>
                <td colspan="4">No properties have been equalified.</td>
            </tr>

        <?php 
        endif;
        ?>

        </tbody>
    </div>
</section>
<section class="py-4 mb-3 border-top">
    <h2>Add a Property</h2>
    <form action="actions/add_and_scan_property.php" method="get" >
        <div class="row">
            <div class="col-8">
                <label for="url" class="form-label">Property URL</label>
                <input id="url" type="text" class="form-control" name="url" aria-describedby="url_help" placeholder="https://edupack.dev" value="https://edupack.dev" >
            </div>
            <div class="col-4">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="wordpress">WordPress Site</option>
                    <option value="drupal">Drupal Site</option>
                    <option value="static">Static Page</option>
                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" class="btn btn-primary">Add and Scan Property</button>
            <div class="form-text">
                Supports most WordPress properties.
            </div>
        </div>
    </form> 
</section>