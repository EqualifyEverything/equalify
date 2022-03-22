
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
        $properties = get_properties($db);
        if(count($properties) > 0 ):
            foreach($properties as $property):    
        ?>

        <div class="col">
            <div class="card">
                <div class="card-body">

                    <?php
                    // Badge info
                    $alert_count = count(get_alerts_by_property($db, $property->id));
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
                        <?php echo $property->title; ?>
                    </h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <?php echo $property->url; ?> 
                    </h6>
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
    <form action="actions/add_and_test_property.php" method="get" >
        <div class="row">
            <div class="col">
                <label for="url" class="form-label">Property URL</label>
                <input type="text" class="form-control" name="url" aria-describedby="url_help" placeholder="https://decubing.com" value="https://decubing.com" >
            </div>
            <div class="col">
                <label for="parent" class="form-label">Parent Property</label>
                <select name="parent" id="parent" class="form-select">
                    <option value="none">None</option>

                    <?php 
                    // List Parent Properties
                    foreach ($properties as $property){ 
                        echo '<option value="'.$property->title.'">'.$property->title.'</option>';
                    } 
                    ?>

                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" class="btn btn-primary">Add and Scan Property</button>
            <div class="form-text">
                Supports WordPress sites, static web pages, PDFs, and XML site maps.
            </div>
        </div>
    </form> 
</section>