<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the property settings view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Let's setup variables so we don't have to deal
// with empty conditions.
$name = '';
$url = '';
$status = '';
$crawl_type = '';
$frequency = '';
$tests = array();

// id is used to load existing report info.
if(!empty($_GET['id'])){
    
    // Let's turn the ID into a session variable so
    // we can safely save existing content.
    session_start();
    $_SESSION['property_id'] = $_GET['id']; 

    // Get property info.
    $properties_filter = array(
        array(
            'name' => 'id',
            'value' => $_SESSION['property_id']
        )
    );
    $properties = DataAccess::get_db_rows( 'properties',
        $properties_filter, 1, 100000
    )['content'];

    // The property should only return one row.
    $property = $properties[0];

    // These values are required, so if they don't
    // return there's a problem.
    $name = $property->name;
    $url = $property->url;
    $status = $property->status;
    $crawl_type = $property->crawl_type;
    $frequency = $property->frequency;

    // This value is optional.
    if(!empty($property->tests))
        $tests = unserialize($property->tests);

}

?>

<h1>Property Settings</h1>
<form action="actions/save_property.php" method="post" id="site_form">
    <div class="row my-4">
        <div class="col">
            <label for="name" class="form-label h4 ">Property Name</label>
            <input id="name"  name="name" type="text" class="form-control form-control-lg" value="<?php echo $name;?>" required>
        </div>
        <div class="col-3">
            <label for="status" class="form-label h4">Status</label>
            <select id="status" name="status" class="form-select form-select-lg">
                <option value="active" <?php if($status == 'active') echo 'selected';?>>Active</option>
                <option value="archived" <?php if($status == 'archived') echo 'selected';?>>Archived</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="crawl_type" class="form-label h4">Crawl Type</label>
            <select id="crawl_type" name="crawl_type" class="form-select">
                <option value="xml" <?php if($crawl_type == 'xml') echo 'selected';?>>XML Sitemap</option>
                <option value="single_page" <?php if($crawl_type == 'single_page') echo 'selected';?>>Single Page</option>
            </select>
        </div>
        <div class="col">
            <label for="frequency" class="form-label h4">Scan Frequency</label>
            <select id="frequency" name="frequency" class="form-select">
                <option value="manually" <?php if($frequency == 'manually') echo 'selected';?>>Manually</option>
                <option value="hourly" <?php if($frequency == 'hourly') echo 'selected';?>>Hourly</option>
                <option value="daily" <?php if($frequency == 'daily') echo 'selected';?>>Daily</option>
                <option value="weekly" <?php if($frequency == 'weekly') echo 'selected';?>>Weekly</option>
                <option value="monthly" <?php if($frequency == 'monthly') echo 'selected';?>>Monthly</option>
            </select>
        </div>
        <div class="col">
            <label for="url" class="form-label h4">URL</label>
            <input id="url"  name="url" type="url" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper" value="<?php echo $url;?>" required>
            <div id="url_helper" class="form-text"></div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <p class="h4 mb-1">Automated Tests</p>
            <div class="form-check pb-1">
                <input class="form-check-input" type="checkbox" name="automated_scan" id="automated_scan" <?php if(in_array('automated_scan', $tests)) echo 'checked';?>>
                <label class="form-check-label" for="automated_scan" >
                    Automated Scan
                </label>
            </div>
            <div class="form-check pb-1">
                <input class="form-check-input" type="checkbox" name="ai_scan" id="ai_scan" <?php if(in_array('ai_scan', $tests)) echo 'checked';?>>
                <label class="form-check-label" for="ai_scan">
                    AI Scan - Experimental
                </label>
            </div>
        </div>
    </div>
    <div class=" my-3">
        <button type="submit" id="submit" class="btn btn-primary">
            Save Property
        </button>
    </div>
</form> 

<script>

// Add spinny wheel to button
document.getElementById('site_form').addEventListener('submit', function () {
    document.getElementById('submit').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding Content..';
    document.getElementById("submit").disabled = true;
});

// Add helper text to URL field.
function updateHelper(helperText, helperPlaceholder) {
    document.getElementById('url_helper').innerHTML = helperText;
    document.getElementById('url').placeholder = helperPlaceholder;
}
xmlHelperText = 'URL must have an associated <a href="https://www.sitemaps.org/protocol.html" target="_blank">XML sitemap</a>.';
if ( document.getElementById('crawl_type').options[document.getElementById('crawl_type').selectedIndex].text == 'XML Sitemap' ){
    updateHelper(xmlHelperText, 'http://www.pih.org/')
}else{
    updateHelper('', 'https://equalify.app/')
}
document.getElementById('crawl_type').addEventListener('change', function () {
    if ( document.getElementById('crawl_type').options[document.getElementById('crawl_type').selectedIndex].text == 'XML Sitemap' ) {
        updateHelper(xmlHelperText, 'http://www.pih.org/')
    } else {
        updateHelper('', 'https://equalify.app/')
    }
});

</script>
