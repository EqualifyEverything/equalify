<h1 class="my-4">Report Settings</h1>
<div class="card my-2 p-4">
    <form>
        <h2 class="h5">General Settings</h2>
        <div class="row my-4">
            <div class="col">
                <label for="reportName" class="form-label">Report Name</label>
                <input type="text" class="form-control" id="reportName" value="Tulane Accessibility">
            </div>
            <div class="col-3">
                <p class="form-label">Visibility</p>
                <select class="form-select" aria-label="Visibility Select">
                    <option value="public" selected>Public</option>
                    <option value="private">Private</option>
                </select>
            </div>
        </div>
        <div class="my-4">
            <h2 class="h5">Included Properties</h2>
            <div class="row my-3">
                <div class="col-auto">
                    <div class="form-check col-auto">
                        <input class="form-check-input" type="checkbox" value="" id="tulaneUniversitySitemap" checked>
                        <label class="form-check-label" for="tulaneUniversitySitemap">
                            Tulane University Sitemap
                        </label>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="tulaneHome">
                        <label class="form-check-label" for="tulaneHome">
                            Tulane Homepage
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <a href="index.php?view=single_report-temp" class="my-4 btn btn-primary">Save Settings</a> <button class="btn btn-outline-danger">Delete Report</button>

    </form>
</div>