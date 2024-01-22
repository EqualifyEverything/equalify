<?php
// Helpers
require_once('helpers/get_property.php');

// Load existing property info.
if(isset($_GET['property_id'])){
    
    // Existing property values
    $property_id = $_GET['property_id'];
    $property = get_property($property_id);
    $name = $property['property_name'];
    $url = $property['property_url'];
    $processed_date = $property['property_processed'];
    $processing = $property['property_processing'];

// Default data for new properties
}else{
    
    // Default data for new properties
    $property_id ='';
    $name = '';
    $url = '';
    $processed_date = '';
    $processing = '';

}

// Let's turn the ID into a session variable so
// we can safely save existing content.
$_SESSION['property_id'] = $property_id; 

?>

<div class="container">
    <h1 class="display-5 my-4"><?php echo $name;?> Settings</h1>
    <div class="card  bg-white p-4 my-2">
        <form action="actions/save_property_settings.php" method="post" id="site_form">
            <div class="row mb-4">
                <div class="col">
                    <label for="property_name" class="form-label h4">Property Name</label>
                    <input id="property_name"  name="property_name" type="text" class="form-control form-control-lg" value="<?php echo $name;?>" required>
                </div>
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

                <?php
                // Only show disabled link for exiting properties
                if(isset($_GET['property_id']))
                  echo '<button type="button" id="delete_property" class="btn btn-lg btn-danger" data-bs-toggle="modal" data-bs-target="#deletionModal">Delete Property</button>';
                ?>
                
            </div>
        </form> 
    </div>
</div>
<div class="modal fade" id="deletionModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="modalLabel">Are you sure you want to delete the property?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Deleting a property will remove all data associated with the property. You cannot undo a deletion.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="actions/delete_property.php" class="btn btn-danger">Yes, Delete the Property</a>
      </div>
    </div>
  </div>
</div>

