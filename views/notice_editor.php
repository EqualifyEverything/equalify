<?php
//Setting up notice editor variables//
$currentNotice = new stdClass();
$viewTitle = "Notice Editor";
$id = -1;
$notice_id = '';
$message = '';
// $status = 'Status';
$statusArray = [
  'Equalified',
  'Active',
  'Ignored',
];
$existing_notices = [];
$relatedURL = '';
$propertiesArrayDummy = [
  1,
  2,
  3,
  4,
];
$propertiesArray = array();
// $snippet = 'There are currently no snippets';
$snippetArray = [];
$moreInfoURL = '';
$notes = '';
$hasBulkImporter = false;
$hasVisualizer = false;
?>
<?php
// This checks for an id in the url and populates field if one exists
if (empty($_GET['notice_id'])) {
  echo 'no notice id provided';
}

if (!empty($_GET['notice_id'])) {
  $notice_id = $_GET['notice_id'];
  $existing_notices = DataAccess::get_db_rows(
    'notices',
    [],
    1,
    1000000
  )['content'];

  // Let's find the item in the array with the matching id
  foreach ($existing_notices as $notice) {
    if ($notice->id == $notice_id) {
      $currentNotice = $notice;
      break; // Exit the loop once a match is found
    }
  } 
  }

  // Check if a match was found and populate fields
  if (!empty(get_object_vars($currentNotice))) {
    
    $id = $currentNotice->id;
    $message = $currentNotice->message;
    $status = $currentNotice->status;
    $relatedURL = $currentNotice->related_url;
    $property = $currentNotice->property_id;
    $source = $currentNotice->source;
    $archived = $currentNotice->archived;
    //!!!These variables will be included under meta when updated!!!
    // $snippetArray = $currentNotice->meta->snippets;
    // $moreInfoURL = $currentNotice->meta->more_info_url;
    // $notes = $currentNotice->meta->notes;

    // Use $itemId and $itemName as needed
  } else {
    echo "Item with ID $notice_id not found.";
  }

?>

<main class="container">
  <title><?php echo $viewTitle ?></title>
  <div class="container mt-5">
    <div class="row mb-3">
      <h1 class="col-md-9"><?php echo $viewTitle ?></h1>
      <form action="actions/save_notice.php" method="post">
      <!--Check for bulk importer-->
      <?php if ($hasBulkImporter) : ?>
        <div class="col-md-3 ">
          <a href="bulk_notice_importer" class="btn btn-secondary">Bulk Notice Importer</a>
        </div>
      <?php
      endif;
      ?>
    </div>
      <!-- First row -->

      <div class="row mb-3">
        <div class="col-md-5 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="message">Message</label>
          <input type="text" class="form-control" id="message" name="message" placeholder="Message" value="<?php echo $message ?>" required>
        </div>
        <div class="col-md-3 ml-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="status">Status</label>
          <select class="form-select custom-select mr-sm-2" id="status" name="status">
            <?php
            // Populate Status Dropdown
            foreach ($statusArray as $status) :
              echo '<option name="status" value=' . $status . '>' . $status . '</option>';
            endforeach;
            ?>
          </select>
        </div>
        <div class="col-md-3 ml-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="related_url">Related URL</label>
          <input type="url" class="form-control" id="related_url" name="related_url" placeholder="Related URL:" value="<?php echo $relatedURL ?>" required>
        </div>
      </div>
      <!-- Second row -->
      <div class="row">
        <div class="col-md-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="property_id">Related Property</label>
          <select class="form-select custom-select mr-sm-2" id="property_id" name="property_id">
            <?php
            // Populate Properties Array
            foreach ($propertiesArrayDummy as $property)
              echo '<option name="property_id" value=' . $property . '>' . $property . '</option>';
            ?>
          </select>
        </div>
        <div class="col-md-5 ml-md-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="meta_code_snippet">Meta:Code Snippet</label>
          <ul class="list-group mb-3">
            <?php foreach ($snippetArray as $snippet) : ?>
              <li class="list-group-item d-flex align-items-start">

                <?php echo '<code  id="meta_code_snippet" name="meta_code_snippet">' . htmlspecialchars($snippet) . '</code>' ?>

                <a href="#" class="ml-3 justify-content-end">
                  <svg xmlns="http://www.w3.org/2000/svg" height="1.25em" viewbox="0 0 448 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                    <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z" />
                  </svg>
                </a>
              </li>
            <?php
            endforeach;
            ?>
          </ul>
          <a href="add_report" class="btn btn-secondary">Add</a>
          <?php if ($hasVisualizer) : ?>
            <a href="add_report" class="btn btn-secondary">Visualizer</a>
          <?php endif; ?>
        </div>
        <div class="col-md-3 ml-md-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="moreInfoURLInput">Meta: More Info URL</label>

          <input type="url" class="form-control" id="moreInfoURLInput" name="moreInfoURLInput" placeholder="More Info URL:" value="<?php echo $moreInfoURL ?>" required>
        </div>
      </div>
      <!-- Third row -->

      <div class="row">
        <div class="col-md-4 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="metaNotesInput">Meta: Notes</label>

          <textarea type="text" class="form-control" id="metaNotesInput" name="metaNotesInput" placeholder="Notes:" aria-label="notes" value="<?php echo $notes ?>"><?php echo $notes ?></textarea>
        </div>
      </div>
      <!-- Fourth row -->

      <div class="row">
        <div class="col-md-4 mb-4">
          <button type="submit" id="submit" class="btn btn-primary">
                Save notice
            </button>
          <button type="submit" id="submit" class="btn btn-danger">
                Delete Notice
            </button>
        </div>
      </div>
    </form>
  </div>

  <!-- create button for adding reports -->

</main>