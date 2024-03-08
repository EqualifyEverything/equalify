<?php
// Creates a modal to add properties.
function the_add_properties_button_and_modal() {
?>

<button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addPropertiesModal">
    Add Properties
</button>
<div class="modal fade" id="addPropertiesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="filterModalLabel">Add Properties</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="notifications-area mb-2">
                </div>  
                <div class="form-floating mb-4">
                    <select id="importOptions" class="form-select"  aria-label="Import Options">
                        <option value="messages" selected>Add Single Property</option>
                        <option value="properties">Upload CSV of Properties</option>
                    </select>
                    <label for="importOptions">Import Types</label>
                </div>
                <div id="singlePropertyForm">  
                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="propertyName" placeholder="Name">
                        <label for="propertyName">Property Name</label>
                    </div>
                    <div class="form-floating mb-2">
                        <input type="url" class="form-control" id="propertyUrl" placeholder="https://">
                        <label for="propertyUrl">Property URL</label>
                    </div>
                    <div class="form-floating mb-2">
                        <select id="discoveryProcess" class="form-select">
                            <option value="single">Single Page</option>
                            <option value="sitemap">Sitemap Scan</option>
                            <option value="crawl">Crawl</option>
                        </select>
                        <label for="discoveryProcess">Discovery Process</label>
                    </div>
                </div>                        
                <div id="csvUploadForm" style="display: none;"> 
                    <label for="csvFile">Upload CSV</label>
                    <input type="file" id="csvFile" class="form-control mb-2" accept=".csv">
                    <div class="form-text">Your file must be a CSV with two columns: "Website," with valid URLs, and "Discovery Process," including either "Single Page", "Sitemap", or "Crawl".</div>
                </div> 
            </div>
            <div class="modal-footer"> 
                <button type="button" class="btn btn-primary" id="submitProperties">Submit</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('importOptions').addEventListener('change', function() {
        let singleForm = document.getElementById('singlePropertyForm');
        let csvForm = document.getElementById('csvUploadForm');

        if (this.value === 'messages') {
            singleForm.style.display = 'block';
            csvForm.style.display = 'none';
        } else {
            singleForm.style.display = 'none';
            csvForm.style.display = 'block';
        }
    });
</script>
<?php
}
?>
