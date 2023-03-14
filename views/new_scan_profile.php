<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>New Scan Profile</h1>
    </div>
    <form action="actions/add_scan_profile.php" method="post" id="site_form">
        <div class="row">
            <div class="col">
                <label for="url" class="form-label">Site URL</label>
                <input id="url"  name="url" type="text" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper" required>
                <div id="url_helper" class="form-text"></div>
            </div>
            <div class="col-3">
                <label for="type" class="form-label">Scan Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="sitemap">XML Sitemap</option>
                    <option value="single_page">Single Page</option>
                    <option value="crawl">Crawl</option>
                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" id="submit" class="btn btn-primary">
                Add Profile
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
    if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'XML Sitemap' ){
        updateHelper(xmlHelperText, 'http://www.pih.org/')
    }else{
        updateHelper('', 'https://equalify.app/')
    }
    document.getElementById('type').addEventListener('change', function () {
        if ( document.getElementById('type').options[document.getElementById('type').selectedIndex].text == 'XML Sitemap' ) {
            updateHelper(xmlHelperText, 'http://www.pih.org/')
        } else {
            updateHelper('', 'https://equalify.app/')
        }
    });

    </script>

</section>