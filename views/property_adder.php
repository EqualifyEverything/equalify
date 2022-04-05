<?php
// Set URL Requests
$requested_group = '';
if(!empty($_GET['group']))
    $requested_group = $_GET['group'];
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
                    <option value="xml">Site (via XML Sitemap)</option>
                </select>
            </div>
            <div id="group_field" class="col-4">
                <label for="group" class="form-label">Group</label>
                <select id="group" name="group" class="form-select">
                    <option value="">None</option>

                    <?php
                    // Show Non-WordPress Property Groups because
                    // WordPress properties are automatically loaded
                    $filters = [
                        array(
                            'name'  => 'is_parent',
                            'value' => '1'
                        ),
                        array(
                            'name'  => 'type',
                            'value' => 'static'
                        )
                    ];
                    $groups = get_properties($db, $filters);
                    if( count($groups) > 0 ){
                        foreach ($groups as $group){
                            echo '<option value="'.$group->url.'">'.$group->url.'</option>';
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
    // Load groups from URL request.
    if(!empty($requested_group)) 
        echo "select('group', '".$requested_group."');";

    // Load type from URL request.
    if(!empty($requested_type)) 
        echo "select('type', '".$requested_type."');";
    ?>

    // Add helper text to URL field.
    if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
        document.getElementById('url_helper').textContent = 'Adds up to 100 WordPress pages.';
    };
    document.getElementById('type').addEventListener('change', function () {
        if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
            document.getElementById('url_helper').textContent = $helperText;
        } else {
            document.getElementById('url_helper').textContent = '';
        }
    });

</script>