<?php
// Creates a table of occurrences.
function the_occurrence_table($filters = '')
{
?>

<div class="card my-2 p-4 table-responsive">
    <h2 class="visually-hidden">Occurrences</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="col-5">Message</th>
                <th scope="col" class="col-5">Code Snippet</th>
                <th scope="col" class="col-1">Status</th>
                <th scope="col" class="col-1">Tags</th>
            </tr>
        </thead>
        <tbody id="occurrencesContainer">
            <!-- Occurrences will be loaded here via AJAX -->
        </tbody>
    </table>
    <div class="d-flex align-items-center" id="paginationControls">
        <!-- Pagination will be dynamically updated here -->
    </div>
</div>

<script>
    function fetchOccurrences(page) {
        const xhr = new XMLHttpRequest();
        const url = 'api?request_type=occurrence_list&current_results_page=' + page;
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                updateOccurrencesContainer(response.occurrences);
                updatePaginationControls(page, response.totalPages);
            } else {
                document.getElementById('occurrencesContainer').innerHTML = 'Error loading occurrences.';
            }
        };
        xhr.onerror = function() {
            console.error("Error on AJAX request.");
            document.getElementById('occurrencesContainer').innerHTML = 'Error loading occurrences.';
        };
        xhr.send();
    }

    function updateOccurrencesContainer(occurrences) {
        let html = occurrences.length ? '' : '<tr><td colspan="4">No occurrences found.</td></tr>';
        occurrences.forEach(occurrence => {
            html += `
                <tr>
                    <td>${occurrence.message_title}</td>
                    <td><small><pre><code>${occurrence.code_snippet}</code></pre></small></td>
                    <td>${occurrence.status}</td>
                    <td>${occurrence.tags.split(', ').map(tag => `<a href='index.php?view=tag&id=${tag}'>${tag}</a>`).join(', ')}</td>
                </tr>
            `;
        });
        document.getElementById('occurrencesContainer').innerHTML = html;
    }

    function updatePaginationControls(currentPage, totalPages) {
        let paginationHtml = totalPages > 1 ? `<p class="text-secondary fs-6 my-0 me-3">Page ${currentPage} of ${totalPages}</p>` : '';
        paginationHtml += `<div class="ms-md-auto btn-group d-inline">`;

        if (currentPage > 1) {
            paginationHtml += `<button onclick="fetchOccurrences(${currentPage - 1})" class="btn btn-sm btn-outline-secondary">Previous</button>`;
        }

        if (currentPage < totalPages) {
            paginationHtml += `<button onclick="fetchOccurrences(${currentPage + 1})" class="btn btn-sm btn-outline-secondary">Next</button>`;
        }

        paginationHtml += `</div>`;
        document.getElementById('paginationControls').innerHTML = paginationHtml;
    }

    // Initial fetch
    fetchOccurrences(1);
</script>

<?php
}
?>