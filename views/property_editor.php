<main class="container">
    <h1>New Property</h1>
    <form action="actions/add_property.php" method="post" id="site_form">
        <div class="row my-4">
            <div class="col">
                <label for="name" class="form-label h4">Property Name</label>
                <input id="name"  name="name" type="text" class="form-control" required>
            </div>
            <div class="col">
                <label for="url" class="form-label h4">URL</label>
                <input id="url"  name="url" type="text" class="form-control" placeholder="https://equalify.app" aria-describedby="url_helper" required>
                <div id="url_helper" class="form-text"></div>
            </div>
            <div class="col-3">
                <label for="status" class="form-label h4">Status</label>
                <select id="status" name="type" class="form-select">
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>
        <div class="row my-4">
            <div class="col">
                <label for="type" class="form-label h4">Crawl Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="xml">XML Sitemap</option>
                    <option value="single_page">Single Page</option>
                </select>
            </div>
            <div class="col">
                <label for="frequency" class="form-label h4">Frequency</label>
                <select id="frequency" name="type" class="form-select">
                    <option value="manually">Manually</option>
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="col">
                <p class="h4 mb-1">Automated Tests</p>
                <div class="form-check pb-1">
                    <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                    <label class="form-check-label" for="flexCheckDefault">
                        Automated Scan
                    </label>
                </div>
                <div class="form-check pb-1">
                    <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked>
                    <label class="form-check-label" for="flexCheckChecked">
                        AI Scan - Experimental
                    </label>
                </div>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" id="submit" class="btn btn-primary">
                Save Property
            </button>
            <button type="submit" id="submit" class="btn btn-outline-danger">
                Delete
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

</main>