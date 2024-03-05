<?php
// Helpers
require_once('helpers/get_property.php');
require_once('components/success_or_error_message.php');

// Load existing property info.
if(isset($_GET['property_id'])){
    
    // Existing property values
    $property_id = $_GET['property_id'];
    $property = get_property($property_id);
    $scanning = isset($property['property_processing']) ? $property['property_processing'] : '';
    $name = isset($property['property_name']) ? $property['property_name'] : ''; 
    $url = isset($property['property_url']) ? $property['property_url'] : ''; 
    $scanning = isset($property['property_processing']) ? $property['property_processing'] : ''; 
    $property_processed = isset($property['property_processed']) ? $property['property_processed'] : ''; 

    if(!empty($property_processed)){
      $date_time = new DateTime($property_processed);
      $scanned_date = 'Processed '.$date_time->format('n/j/y \a\t G:i');
    }else{
      $scanned_date = 'Not processed';
    }


// Default data for new properties
}else{
    
    // Default data for new properties
    $property_id ='';
    $name = '';
    $url = '';
    $scanned_date = '';
    $scanning = '';

}

// Let's turn the ID into a session variable so
// we can safely save existing content with the form.
$_SESSION['property_id'] = $property_id; 

?>

<div class="container">

  <?php
  // Error message
  the_success_or_error_message();
  ?>

  <h1 class="display-5 my-4"><?php echo $name;?> Settings</h1>
  <div class="card  bg-white p-4 my-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="me-2 mb-0">Scan Settings</h2>
        <div class="d-flex justify-content-end align-items-center">
          <small id="property_scan_status" class="text-body-secondary" aria-live="assertive"><?php echo $scanned_date; ?>.</small>
          <button id="scanButton" class="ms-1 btn btn-primary my-0">Send to Scan</button>
        </div>
    </div>
      <form action="actions/save_property_settings.php" method="post" id="site_form">
          <div class="row mb-4">
              <div class="col">
                  <label for="property_name" class="form-label h4">Property Name</label>
                  <input id="property_name"  name="property_name" type="text" class="form-control form-control-lg" value="<?php echo $name;?>" required>
              </div>
              <div class="col">
                  <label for="property_url" class="form-label h4">Sitemap URL</label>
                  <input id="property_url"  name="property_url" type="url" class="form-control form-control-lg" aria-describedby="url_helper" value="<?php echo $url;?>" style="max-width:480px;" required>
                  <div id="url_helper" class="form-text">Sitemaps must follow valid <a href="https://www.sitemaps.org/protocol.html" target="_blank">XML Sitemap schema</a>.<br> Example:"https://equalify.app/sitemap.xml"</div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  var scanButton = document.getElementById('scanButton');
  var statusDisplay = document.getElementById('property_scan_status');

  scanButton.addEventListener('click', function() {
      // Disable the button
      scanButton.disabled = true;

      // Update the status for screen readers
      statusDisplay.textContent = 'Property processing...';
      statusDisplay.setAttribute('aria-live', 'assertive');

      // Start the AJAX request
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'actions/process_property.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      xhr.onload = function() {

          // Update the status with the response
          statusDisplay.textContent = xhr.responseText;

          // Re-enable the button
          scanButton.disabled = false;
      };

      xhr.onerror = function() {
          // Update the status with the error message
          statusDisplay.textContent = 'Error: Could not connect to the server.';
          scanButton.disabled = false;
      };

      xhr.send();
  });
});
</script>