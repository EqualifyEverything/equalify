<?php
//Setting up alert editor variables//
$viewTitle = "Notice Editor";
$message = "Please enter message";
$status = 'Status';
$statusArray = [
  'Equalified',
  'Active',
  'Ignored',
];
$currentNotice = '';
$existing_notices = array();
$relatedURL = 'Please enter related URL';
$property = 'Enter Property';
$propertiesArrayDummy = [
  'sampleProperty1',
  'sampleProperty2',
  'sampleProperty3',
  'sampleProperty4',
];
$propertiesArray = array();
$snippet = '<h1>This is a sample code snippet,/h1>';
$snippetArray = [
  '<h1>This is a sample code snippet,</h1>',
  '<h1>This is a sample code snippet,</h1>',
  '<h1>This is a sample code snippet,</h1>',
  '<h1>This is a sample code snippet that is quite a bit larger to show the overflow...</h1>',
];
$moreInfoURL = 'www.moreinfo.com/example';
$notes = 'this is a sample note, with markdown';
$hasBulkImporter = false;
$hasVisualizer = false;
?>
<?php
// We use this view to customize reports if a id is 
// provided, otherwise we create a new report.
if (!empty($_GET['notice_id'])) {
  // Set the meta name
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
      $message = $currentNotice->message;
      break; // Exit the loop once a match is found
    }
  }

  // Check if a match was found
  if (isset($currentNotice)) {
    // Access the found item
    echo $currentNotice->message;
    $noticeId = $currentNotice->id;
    $message = $currentNotice->message;
    $status = $currentNotice->status;
    $relatedURL = $currentNotice->related_url;
    $property = $currentNotice->property_id;
    //!!!These variables will be included under meta when updated!!!
    // $snippetArray = $currentNotice->meta->snippets;
    // $moreInfoURL = $currentNotice->meta->more_info_url;
    // $notes = $currentNotice->meta->notes;

    // Use $itemId and $itemName as needed
    echo "Item ID: $noticeId, Item Message: $message";
  } else {
    echo "Item with ID $notice_id not found.";
  }
  // Some reports are preset. Presets have restricted fields
  // you can edit and special naming rules.
  // $presets = array(
  //     'report_equalified', 'report_ignored', 'report_all',
  //     'report_active', 'report_active'
  // );
  // if(in_array($name, $presets))
  //     $preset = TRUE;

  // Let's load in predefined variables for the report.

  // Some reports, like Equalified notices, won't
  // have data, so we'll have to prepare variables
  if (empty($existing_notices)) {

    // Set the default title field.
    if ($name == 'report_equalified') {
      $title = 'Equalified Notices';
    } elseif ($name == 'report_ignored') {
      $title = 'Ignored Notices';
    } elseif ($name == 'report_all') {
      $title = 'All Notices';
    } elseif ($name == 'report_active') {
      $title = 'Active Notices';
    }

    // Set the default status field.
    if ($name == 'report_equalified') {
      $status = 'equalified';
    } elseif ($name == 'report_ignored') {
      $status = 'ignored';
    } elseif ($name == 'report_active') {
      $status = 'active';
    }
  }

  // }else{

  //     // Let's reformat the meta so we can use it in a
  //     // more understandable format. The dynamically
  //     // added content is added to $dynamic_meta.
  //     $dynamic_meta = array();
  //     foreach($existing_report as $report) {
  //         if($report['name'] == 'title'){
  //             $title = $report['value'];
  //         }elseif($report['name'] == 'type'){
  //             $type = $report['value'];
  //         }elseif($report['name'] == 'status'){
  //             $status = $report['value'];
  //         }elseif($report['name'] == 'property_id'){
  //             $property_id = $report['value'];
  //         }else{
  //             $dynamic_meta[] = $report['name'];
  //         }
  //     }

  // }

}
?>

<main class="container">
  <title><?php echo $viewTitle ?></title>
  <div class="container mt-5">
    <div class="row mb-3">
      <h1 class="col-md-9"><?php echo $viewTitle ?></h1>
      <!--Check for bulk importer-->
      <?php if ($hasBulkImporter) : ?>
        <div class="col-md-3 ">
          <a href="bulk_notice_importer" class="btn btn-secondary">Bulk Notice Importer</a>
        </div>
      <?php
      endif;
      ?>
    </div>
    <form action="actions/save_notice.php">
      <!-- First row -->

      <div class="row mb-3">
        <div class="col-md-5 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="alertMessageInput">Message</label>
          <input type="text" class="form-control" id="alertMessageInput" placeholder="Message" value="<?php echo $message ?>" required>
        </div>
        <div class="col-md-3 ml-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="alertStatusSelectInput">Status</label>
          <select class="form-select custom-select mr-sm-2" id="alertStatusSelectInput">
            <?php
            // Populate Status Dropdown
            foreach ($statusArray as $status) :
              echo '<option value=' . $status . '>' . $status . '</option>';
            endforeach;
            ?>
          </select>
        </div>
        <div class="col-md-3 ml-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="relatedURLInput">Related URL</label>
          <input type="url" class="form-control" id="relatedURLInput" placeholder="Related URL:" value="<?php echo $relatedURL ?>" required>
        </div>
      </div>
      <!-- Second row -->
      <div class="row">
        <div class="col-md-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="relatedPropertyInput">Related Property</label>
          <select class="form-select custom-select mr-sm-2" id="relatedPropertyInput">
            <?php
            // Populate Properties Array
            foreach ($propertiesArray as $property)
              echo '<option value=' . $property . '>' . $property . '</option>';
            ?>
          </select>
        </div>
        <div class="col-md-5 ml-md-3 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="metaCodeSnippetInput">Meta:Code Snippet</label>
          <ul class="list-group mb-3">
            <?php foreach ($snippetArray as $snippet) : ?>
              <li class="list-group-item d-flex align-items-start">

                <?php echo '<code  id="metaCodeSnippetInput">' . htmlspecialchars($snippet) . '</code>' ?>

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

          <input type="url" class="form-control" id="moreInfoURLInput" placeholder="More Info URL:" value="<?php echo $moreInfoURL ?>" required>
        </div>
      </div>
      <!-- Third row -->

      <div class="row">
        <div class="col-md-4 mb-4">
          <label class="col-form-label-lg font-weight-bold" for="metaNotesInput">Meta: Notes</label>

          <textarea type="text" class="form-control" id="metaNotesInput" placeholder="Notes:" aria-label="notes" value=<?php echo $notes ?>></textarea>
        </div>
      </div>
      <!-- Fourth row -->

      <div class="row">
        <div class="col-md-4 mb-4">
          <a href="save_notice" class="btn btn-primary">Save Notice</a>
          <a href="delete_notice" class="btn btn-danger">Delete Notice</a>
        </div>
      </div>
    </form>
  </div>

  <!-- create button for adding reports -->

</main>