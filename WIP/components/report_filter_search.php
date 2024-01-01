<?php
// Creates an accessible autocomplete to add filters.
function the_report_filter_search($report_id) {
?>

<link rel="stylesheet" href="vendor/bbertucc/accessible-autocomplete/dist/accessible-autocomplete.min.css" />
<script type="text/javascript" src="vendor/bbertucc/accessible-autocomplete/dist/accessible-autocomplete.min.js"></script>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
    Add Filters
</button>
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="filterModalLabel">Add Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-floating mb-4">
                    <select id="filterType" class="form-select"  aria-label="Filter Types">
                        <option value="messages">Messages</option>
                        <option value="tags">Tags</option>
                        <option value="properties">Properties</option>
                        <option value="pages">Related URL</option>
                    </select>
                    <label for="filterType">Select Filter Type</label>
                </div>
                <label for="autocompleteInput" class="form-label">Search and Select Filter Values:</label>
                <div id="autocompleteWrapper"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectElement = document.getElementById('filterType');
    var autocompleteWrapper = document.getElementById('autocompleteWrapper');
    var reportId = "<?php echo $report_id; ?>";
    var currentAutocomplete;

    function fetchAndPopulateAutocomplete(filterType) {
        var apiUrl = "api/?request=" + filterType + "&results_per_page=99999";
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (filterType === 'properties') {
                    objectKey = 'property';
                } else if (filterType.endsWith('s')) {
                    objectKey = filterType.slice(0, -1);
                } else {
                    objectKey = filterType;
                }
                var items = data[filterType].map(item => {
                    return {
                        label: item[objectKey + '_name'] || item[objectKey + '_title'] || item[objectKey + '_url'],
                        value: item[objectKey + '_id']
                    };
                });
                setupAutocomplete(items);
            }).catch(error => {
                console.error('Error fetching data: ', error);
            });
    }

    function setupAutocomplete(items) {
        // Clear the existing autocomplete if it exists
        autocompleteWrapper.innerHTML = '';

        // Initialize a new autocomplete instance
        currentAutocomplete = accessibleAutocomplete({
            element: autocompleteWrapper,
            id: 'autocompleteInput', // Ensure this ID is unique
            source: function(query, populateResults) {
                var filteredItems = items.filter(function(item) {
                    return item.label.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                });
                populateResults(filteredItems);
            },
            onConfirm: function(confirmed) {
                if (confirmed) {
                    submitFilter(selectElement.value, confirmed.label, confirmed.value);
                }
            },
            templates: {
                inputValue: function(item) {
                    return item ? item.label : ''; // Ensure the correct display of selected item
                },
                suggestion: function(item) {
                    return item.label; // Ensure the correct display of suggestions
                }
            }
        });
    }

    function submitFilter(filterType, filterValue, filterID) {

        // Helper function to build a query string from an object
        function buildQueryString(params) {
            return Object.keys(params).map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key])).join('&');
        }

        // Construct the filter parameters
        var filterParams = {
            'filter_type': filterType,
            'filter_value': filterValue,
            'filter_id': filterID,
            'filter_change': 'add'
        };

        // Build the filter string from the filter parameters
        var filterString = buildQueryString(filterParams);

        // Construct the full URL with the report ID and the filter string
        var url = "actions/queue_report_filter_change.php?report_id=" + encodeURIComponent(reportId) + "&filters[]=" + encodeURIComponent(filterString);

        // Navigate to the URL
        window.location.href = url;
        
    }

    selectElement.addEventListener('change', function() {
        fetchAndPopulateAutocomplete(this.value);
    });

    // Initialize for the first selected option
    fetchAndPopulateAutocomplete(selectElement.value);
});

</script>

<?php
}
?>
