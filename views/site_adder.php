<?php
// Set URL Requests
$requested_type = '';
if(!empty($_GET['type']))
    $requested_type = $_GET['type'];
?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>Add Site</h1>
    </div>
    <form action="actions/add_property.php" method="get" >
        <div class="row">
            <div class="col">
                <label for="url" class="form-label">Site URL</label>
                <input id="url"  name="url" type="text" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper" required>
                <div id="url_helper" class="form-text"></div>
            </div>
            <div class="col-3">
                <label for="type" class="form-label">Scan Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="wordpress">WordPress Site</option>
                    <option value="xml">Site via XML Sitemap</option>
                    <option value="single_page">Single Page</option>
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
    // Load type from URL request.
    if(!empty($requested_type)) 
        echo "select('type', '".$requested_type."');";
    ?>

    // Add helper text to URL field.
    function updateHelper(helperText, helperPlaceholder) {
        document.getElementById('url_helper').innerHTML = helperText;
        document.getElementById('url').placeholder = helperPlaceholder;
    }
    var wordpressHelperText = 'Adds up to 100 WordPress pages.',
        xmlHelperText = 'URL must be a standard <a href="https://www.sitemaps.org/protocol.html" target="_blank">XML sitemap</a>.';
    if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
        updateHelper(wordpressHelperText, 'https://equalify.app/')
    }else if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'Site via XML Sitemap' ){
        updateHelper(xmlHelperText, 'http://www.pih.org/sitemap.xml')
    }else{
        updateHelper('', 'https://equalify.app/')
    }
    document.getElementById('type').addEventListener('change', function () {
        if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'WordPress Site' ){
            updateHelper(wordpressHelperText, 'https://equalify.app/')
        } else if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'Site via XML Sitemap' ) {
            updateHelper(xmlHelperText, 'http://www.pih.org/sitemap.xml')
        } else {
            updateHelper('', 'https://equalify.app/')
        }
    });

</script>