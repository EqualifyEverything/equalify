<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>New Site</h1>
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
                    <!-- TODO: Make this dynamic, depending on the scan functions in the active integrations. -->
                    <option value="a11ywatch_scan">A11yWatch Scan</option>
                    <option value="a11ywatch_sitemap_scan">A11yWatch Sitemap Scan</option>
                    <option value="a11ywatch_crawl">A11yWatch Crawl</option>
                </select>
            </div>
        </div>
        <div class="my-3">
            <button type="submit" id="submit" class="btn btn-primary">
                Add Site
            </button>
        </div>
    </form> 
</section>