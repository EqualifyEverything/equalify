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
                <form id="addPropertyForm">
                    <div id="addPropertiesNotifications" class="mb-2" aria-live="polite"></div>  
                    <div class="form-floating mb-4">
                        <select id="importOptions" class="form-select" aria-label="Import Options" required>
                            <option value="single_property" selected>Add Single Property</option>
                            <option value="csv">Upload CSV of Properties</option>
                        </select>
                        <label for="importOptions">Import Types</label>
                        <div class="invalid-feedback">
                            Please select an import type.
                        </div>
                    </div>
                    <div id="singlePropertyForm">  
                        <div class="form-floating mb-2">
                            <input type="text" class="form-control" id="propertyName" placeholder="Name" required>
                            <label for="propertyName">Property Name</label>
                            <div class="invalid-feedback">
                                Please provide a property name.
                            </div>
                        </div>
                        <div class="form-floating mb-2">
                            <input type="url" class="form-control" id="propertyUrl" placeholder="https://" required>
                            <label for="propertyUrl">Property URL</label>
                            <div class="invalid-feedback">
                                Please provide a valid URL.
                            </div>
                        </div>
                        <div class="form-floating mb-2">
                            <select id="discoveryProcess" class="form-select" required>
                                <option value="" selected disabled>Choose...</option>
                                <option value="single_page_import">Single Page Import</option>
                                <option value="sitemap_import">Sitemap Import</option>
                                <option value="crawl">Crawl</option>
                            </select>
                            <label for="discoveryProcess">Discovery Process</label>
                            <div class="invalid-feedback">
                                Please select a discovery process.
                            </div>
                        </div>
                    </div>                        
                    <div id="csvUploadForm" style="display: none;"> 
                        <label for="csvFile" class="form-label">Upload CSV</label>
                        <input type="file" id="csvFile" class="form-control mb-2" accept=".csv" required>
                        <div class="form-text">Valid CSVs must include three cololumns "Name", "URL" and "Discovery" in that order. Valid Discovery options are "Single Page Import", "Sitemap Import", or "Crawl."</div>
                        <div class="invalid-feedback">
                            Please upload a CSV file.
                        </div>
                    </div> 
                </form>
            </div>
            <div class="modal-footer"> 
                <button type="button" class="btn btn-primary" id="submitProperties">Submit</button>
                <a href="?view=settings" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('importOptions').addEventListener('change', function() {
        let singleForm = document.getElementById('singlePropertyForm');
        let csvForm = document.getElementById('csvUploadForm');

        if (this.value === 'single_property') {
            singleForm.style.display = 'block';
            csvForm.style.display = 'none';
        } else {
            singleForm.style.display = 'none';
            csvForm.style.display = 'block';
        }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addPropertyForm');
        const submitButton = document.getElementById('submitProperties');
        const notificationsArea = document.getElementById('addPropertiesNotifications');
        const importOptions = document.getElementById('importOptions');

        const setFormState = (isEnabled) => {
            const elements = form.querySelectorAll('input, select, button');
            elements.forEach(el => el.disabled = !isEnabled);
        };

        const showFeedback = (message, isSuccess) => {
            notificationsArea.innerHTML = message;
            notificationsArea.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
        };

        const validateAndSubmitForm = () => {
            let isValidForm = true; // Assume form is valid initially

            // Clear existing validation states
            form.classList.remove('was-validated');

            if (importOptions.value === 'single_property') {
                const propertyName = document.getElementById('propertyName').value.trim();
                const propertyUrl = document.getElementById('propertyUrl').value.trim();
                const discoveryProcess = document.getElementById('discoveryProcess').value;

                if (!propertyName || !propertyUrl || !discoveryProcess) {
                    isValidForm = false; // Mark form as invalid if any field is empty
                }
            } else if (importOptions.value === 'csv') {
                const csvFile = document.getElementById('csvFile').files.length;
                if (csvFile === 0) {
                    isValidForm = false; // Mark form as invalid if CSV file is not uploaded
                }
            }

            if (!isValidForm) {
                // Show form validation feedback
                form.classList.add('was-validated');
                return;
            }

            // Form is valid, proceed with submission
            setFormState(false);
            notificationsArea.innerHTML = 'Submitting... Please wait.';
            notificationsArea.className = 'alert alert-info';

            let formData = new FormData();
            let submitUrl = ''; // Initialize submitUrl variable

            // Update the formData and submitUrl based on the selected option
            if (importOptions.value === 'single_property') {
                formData.append('propertyName', document.getElementById('propertyName').value);
                formData.append('propertyUrl', document.getElementById('propertyUrl').value);
                formData.append('discoveryProcess', document.getElementById('discoveryProcess').value);
                submitUrl = 'actions/add_single_property.php'; // Set URL for single property submission
            } else if (importOptions.value === 'csv') {
                formData.append('csvFile', document.getElementById('csvFile').files[0]);
                submitUrl = 'actions/add_csv_properties.php'; // Set URL for CSV submission
            }

            fetch(submitUrl, { // Use submitUrl in the fetch call
                method: 'POST',
                body: formData,
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok.');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    showFeedback(`Error: ${data.error}`, false);
                } else if (data.failed && data.failed.length > 0) {
                    // Handle case where some properties were not valid/working
                    const failedMessages = data.failed.map(f => `${f.property_name ? f.property_name + ': ' : ''}${f.log}`).join('<br>');
                    showFeedback(`Error: ${failedMessages}`, false);
                } else {
                    showFeedback('Property added!', true);
                    form.reset(); // Reset form on successful submission
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFeedback('An error occurred. Please try again.', false);
            })
            .finally(() => {
                setFormState(true); // Re-enable form elements after submission
            });
        };
        submitButton.addEventListener('click', function(event) {
            event.preventDefault();
            validateAndSubmitForm();
        });
    });
</script>

<?php
}
?>
