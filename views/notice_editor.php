<?php
//Setting up notice editor variables//
$currentNotice = new stdClass();
$viewTitle = "Notice Editor";
$message = '';
$statusArray = [
  'Equalified',
  'Active',
  'Ignored',
];
$existing_notices = [];
$relatedURL = '';
$meta = [];
$propertiesArray  = DataAccess::get_db_rows(
  'properties',
  [],
  1,
  1000000
)['content'];;
$snippetArray = ['test snippet'];
$moreInfoURL = '';
$notes = '';
$archived = 0;
$hasBulkImporter = false;
$hasVisualizer = false;

// This checks for an id in the url and populates field if one exists

if (!empty($_GET['notice_id'])) {
  session_start();

  $_SESSION['notice_id'] = $_GET['notice_id'];
  $notice_filter = array(
    array(
      'name' => 'id',
      'value' => $_SESSION['notice_id']
    )
  );
  $existing_notices = DataAccess::get_db_rows(
    'notices',
    [],
    1,
    1000000
  )['content'];

  // Let's find the item in the array with the matching id
  foreach ($existing_notices as $notice) {
    if ($notice->id == $_SESSION['notice_id']) {
      $currentNotice = $notice; 
      break; // Exit the loop once a match is found
    }
  }

  // Add source from notice for updating db
  $_SESSION['source'] = $currentNotice->source;
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
  // Let's check to see if there are any values in meta
  // and assign them as needed
  $meta = unserialize($currentNotice->meta);
  if (is_array($meta)) {
    if (isset($meta['snippets'])) {
      $snippetArray = $meta['snippets'];
    }
    if (isset($meta['more_info_url'])) {
      $moreInfoURL = $meta['more_info_url'];
    }
    if (isset($meta['notes'])) {
      $notes = $meta['notes'];
    }
  }
}
?>

<title><?php echo $viewTitle ?></title>
<div class="container mt-5">
  <div class="row mb-3">
    <h1 class="col-md-9"><?php echo $viewTitle ?></h1>
    <form action="actions/save_notice.php" method="post">
      <div id="input_test"></div>
      <!--Check for bulk importer-->
      <?php if ($hasBulkImporter) : ?>
        <div class="col-md-3 ">
          <a href="bulk_notice_importer" class="btn btn-secondary">Bulk Notice Importer</a>
        </div>
      <?php
      endif;
      ?>
  </div>
  <!-- First row: Message, Status, Related URL -->

  <div class="row mb-3">
    <div class="col-md-5 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="message">Message</label>
      <input type="text" class="form-control" id="message" name="message" placeholder="Message" value="<?php echo $message ?>">
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
      <input type="url" class="form-control" id="related_url" name="related_url" aria-describedby="url_helper" placeholder="Related URL:" value="<?php echo $relatedURL ?>" required>
    </div>
    <div id="url_helper" class="form-text"></div>
  </div>
  <!-- Second row: Related Property, Meta Code Snippets, Meta More Info URL   -->
  <div class="row">
    <div class="col-md-3 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="property_id">Related Property</label>
      <select class="form-select custom-select mr-sm-2" id="property_id" name="property_id" required>
        <?php
        // Populate Properties Array
        foreach ($propertiesArray as $property)
          echo '<option name="property_id" value=' . $property->id . '>' . $property->name . '</option>';
        ?>
      </select>
    </div>
    <div class="col-md-5 ml-md-3 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="meta_code_snippet[]">Meta:Code Snippet</label>
      <div id="snippets" class="list-group mb-3">
        <?php foreach ($snippetArray as $snippet) : ?>
          <div class="position-relative">
            <input class="list-group-item pr-5" name="meta_code_snippet[]" value="<?php echo $snippet ?>" />
            <a onclick="deleteField()" class="position-absolute end-0 top-50 translate-middle-y">
              <svg xmlns="http://www.w3.org/2000/svg" height="1.25em" viewBox="0 0 448 512">
                <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z" />
              </svg>
            </a>
          </div>
          <?php
        endforeach;
        ?>
        <input class="list-group-item pr-5" name="meta_code_snippet_new[]" value="<?php echo $snippet ?>" />
      </div>
      <a onclick="addField()" class="btn btn-secondary">Add Snippet</button>

      </a>
      <?php if ($hasVisualizer) : ?>
        <a href="add_report" class="btn btn-secondary">Visualizer</a>
      <?php endif; ?>
    </div>
    <div class="col-md-3 ml-md-3 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="meta_more_info_url">Meta: More Info URL</label>

      <input type="url" class="form-control" id="meta_more_info_url" name="meta_more_info_url" placeholder="More Info URL:" value="<?php echo $moreInfoURL ?>">
    </div>
  </div>
  <!-- Third row:  Meta Notes, Achive Notice-->
  <div class="row">
    <div class="col-md-4 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="meta_notes">Meta: Notes</label>

      <textarea type="text" class="form-control" id="meta_notes" name="meta_notes" placeholder="Notes:" aria-label="notes" value="<?php echo $notes ?>"><?php echo $notes ?></textarea>
    </div>
    <div class="col-md-4 mb-4">
      <label class="col-form-label-lg font-weight-bold" for="archived">Archive Notice:</label>
      <div class="form-check form-switch text-center ml-4">
        <input class="form-check-input  ml-4" name="archived" type="checkbox" id="archived" aria-label="Toggle Button" aria-checked="true"<?php if($archived) echo 'checked'; ?>>
      </div>
    </div>
  </div>
  <!-- Fourth row: Save Notice, Delete Notice-->

  <div class="row">
    <div class="col-md-4 mb-4">
      <button type="submit" id="submit" class="btn btn-primary">
        Save notice
      </button>
      <button type="submit" id="submit" class="btn btn-danger">
        Delete Notice
      </button>
    </div>
    <!-- <div class="col-md-4 mb-4">
    </div> -->
  </div>
  </form>
</div>

<!-- create button for adding reports -->

<script>
  function addField($snippet_text) {
    let snippets = document.getElementById("input_test");
    // let snippets = document.getElementById("snippets");
    let newSnippet = document.createElement("div");
    newSnippet.className = "position-relative";
    // newSnippet.className = "position-relative";

    let inputField = document.createElement("input");
    // inputField.type = "text";
    inputField.name = "meta_code_snippet[]";
    inputField.className = "list-group-item pr-5";

    let deleteButton = document.createElement("a");
    deleteButton.className = "delete position-absolute end-0 top-50 translate-middle-y";
    deleteButton.onclick = function() {
      deleteField(this);
    };
    let svgIcon = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svgIcon.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    svgIcon.setAttribute("height", "1.25em");
    svgIcon.setAttribute("viewBox", "0 0 448 512");

    let path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", "M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z");

    svgIcon.appendChild(path);
    deleteButton.appendChild(svgIcon);

    newSnippet.appendChild(inputField);
    newSnippet.appendChild(deleteButton);

    snippets.appendChild(newSnippet);
  }


  function deleteField(button) {
    var field = button.parentElement;
    field.remove();
  }
  // Add helper text to URL field.
  function updateHelper(helperText, helperPlaceholder) {
    document.getElementById('url_helper').innerHTML = helperText;
    document.getElementById('related_url').placeholder = helperPlaceholder;
  }
  xmlHelperText = 'URL must have an associated <a href="https://www.sitemaps.org/protocol.html" target="_blank">XML sitemap</a>.';
  if (document.getElementById('crawl_type').options[document.getElementById('crawl_type').selectedIndex].text == 'XML Sitemap') {
    updateHelper(xmlHelperText, 'http://www.pih.org/')
  } else {
    updateHelper('', 'https://equalify.app/')
  }
  document.getElementById('crawl_type').addEventListener('change', function() {
    if (document.getElementById('crawl_type').options[document.getElementById('crawl_type').selectedIndex].text == 'XML Sitemap') {
      updateHelper(xmlHelperText, 'http://www.pih.org/')
    } else {
      updateHelper('', 'https://equalify.app/')
    }
  });
</script>