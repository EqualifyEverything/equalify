<?php
// Helpers
require_once('helpers/get_property.php');

// Let's setup variables so we don't have to deal
// with empty conditions.
$name = '';
$url = '';
$status = '';
$crawl_type = '';
$frequency = '';
$tests = array();
$property_id = $_GET['property_id'];

// id is used to load existing property info.
if(!empty($_GET['property_id'])){
    
    // Let's turn the ID into a session variable so
    // we can safely save existing content.
    session_start();
    $_SESSION['property_id'] = $property_id; 

    // The property should only return one row.
    $property = get_property($property_id);

    // These values are required, so if they don't
    // return there's a problem.
    $name = $property['property_name'];
    $url = $property['property_url'];
    $archived = $property['property_archived'];
    $processed_date = $property['property_processed'];
    $processing = $property['property_processing'];
}

?>

<div class="container">
    <h1 class="display-5 my-4">Property Settings</h1>
    <div class="card  bg-white p-4 my-2">
        <form action="actions/save_property_settings.php" method="post" id="site_form">
            <div class="row mb-4">
                <div class="col">
                    <label for="property_name" class="form-label h4">Property Name</label>
                    <input id="property_name"  name="property_name" type="text" class="form-control form-control-lg" value="<?php echo $name;?>" required>
                </div>
                <div class="col-3">
                    <label for="property_archived" class="form-label h4">Status</label>
                    <select id="property_archived" name="property_archived" class="form-select form-select-lg">
                        <option value="" <?php if($archived == 0 || $archived == false || empty($archived)) echo 'selected';?>>Active</option>
                        <option value="1" <?php if($archived == 1 || $archived == true) echo 'selected';?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="property_url" class="form-label h4">Sitemap URL</label>
                    <input id="property_url"  name="property_url" type="url" class="form-control form-control-lg" placeholder="https://equalify.app/sitemap.xml" aria-describedby="url_helper" value="<?php echo $url;?>" style="max-width:480px;" required>
                    <div id="url_helper" class="form-text">Sitemaps must follow valid <a href="https://www.sitemaps.org/protocol.html" target="_blank">XML Sitemap schema</a>.</div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" id="submit" class="btn btn-lg btn-primary">
                    Save Property
                </button>
            </div>
        </form> 
    </div>
</div>