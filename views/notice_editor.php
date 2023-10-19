<?php
//Setting up alert editor variables//
$viewTitle = "Notice Editor";
$message = "This is a sample message";
$status = 'Status';
$statusArray = [
  'Equalified',
  'Active',
  'Ignored',
];
$relatedURL = 'www.sampleurl.com/example';
$property = 'Sample Property';
$propertiesArray = [
  'sampleProperty1',
  'sampleProperty2',
  'sampleProperty3',
  'sampleProperty4',
];
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
          <input type="text" class="form-control" id="alertMessageInput" placeholder="Message" value=<?php echo $message ?> required>
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
          <input type="url" class="form-control" id="relatedURLInput" placeholder="Related URL:" value=<?php echo $relatedURL ?> required>
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
                
                <?php echo '<code  id="metaCodeSnippetInput">'.htmlspecialchars($snippet).'</code>'?>
                
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

          <input type="url" class="form-control" id="moreInfoURLInput" placeholder="More Info URL:" value=<?php echo $moreInfoURL ?> required>
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