<?php
// Set URL Requests
$requested_parent = '';
if(!empty($_GET['parent']))
    $requested_parent = $_GET['parent'];
$requested_type = '';
if(!empty($_GET['type']))
    $requested_type = $_GET['type'];
?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>Add Property</h1>
    </div>
    <form action="actions/add_property.php" method="get" >
        <div class="row">
            <div class="col">
                <label for="url" class="form-label">Property URL</label>
                <input id="url"  name="url" type="text" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper">
                <div id="url_helper" class="form-text"></div>
            </div>
            <div class="col-3">
                <label for="type" class="form-label">Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="static">Static Page</option>
                    <option value="wordpress">WordPress Site</option>
                    <option value="drupal_7" disabled>Drupal 7 Site (Coming Soon)</option>
                </select>
            </div>
            <div id="parent_field" class="col-4" style="display:none">
                <label for="parent" class="form-label">Parent</label>
                <select id="parent" name="parent" class="form-select">
                    <option value="">None</option>

                    <?php
                    // Show Non-WordPress Property Parents because
                    // WordPress properties are automatically loaded
                    $filters = [
                        array(
                            'name'  => 'parent',
                            'value' => ''
                        ),
                        array(
                            'name'  => 'type',
                            'value' => 'static'
                        )
                    ];
                    $parents = get_properties($db, $filters);
                    if( count($parents) > 0 ){
                        foreach ($parents as $parent){
                            echo '<option value="'.$parent->url.'">'.$parent->url.'</option>';
                        }
                    }
                    ?>                

                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" class="btn btn-primary">Add Property</button>
        </div>
    </form> 
</section>

<script>

    // Change a select element's option.
    function select(selectId, optionValToSelect){
        var selectElement = document.getElementById(selectId);
        var selectOptions = selectElement.options;
        for (var opt, j = 0; opt = selectOptions[j]; j++) {
            if (opt.value == optionValToSelect) {
                selectElement.selectedIndex = j;
                break;
            }
        }
    }

    <?php
    // Load parents from URL request.
    if(!empty($requested_parent)) 
        echo "select('parent', '".$requested_parent."');";

    // Load type from URL request.
    if(!empty($requested_type)) 
        echo "select('type', '".$requested_type."');";
    ?>

    // Restrict "parents" to static pages.
    if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'Static Page' ){
        document.getElementById('parent_field').style.display = 'block';
    };
    document.getElementById('type').addEventListener('change', function () {
        if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'Static Page' ){
            document.getElementById('parent_field').style.display = 'block';
        } else {
            document.getElementById('parent_field').style.display = 'none';
        }
    });

    // Add helper text to URL field.
    $helperText = 'WordPress site pages are automatically added (up to 100 pages).'
    if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
        document.getElementById('url_helper').textContent = $helperText;
    };
    document.getElementById('type').addEventListener('change', function () {
        if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
            document.getElementById('url_helper').textContent = $helperText;
        } else {
            document.getElementById('url_helper').textContent = '';
        }
    });

</script>