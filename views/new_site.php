<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>New Site</h1>
    </div>

    <form action="actions/add_site.php" method="get" id="site_form">
        <div class="row">
            <div class="col">
                <label for="url" class="form-label">Site URL</label>
                <input id="url"  name="url" type="text" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper" required>
                <div id="url_helper" class="form-text"></div>
            </div>
            <div class="col-3">
                <label for="type" class="form-label">Site Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="wordpress">WordPress Site</option>
                    <option value="xml">Site via XML Sitemap</option>
                    <option value="single_page">Single Page</option>
                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" id="submit" class="btn btn-primary">
                Add Site
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

</section>