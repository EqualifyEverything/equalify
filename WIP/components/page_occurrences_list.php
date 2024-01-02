<?php
// Creates a list of occurrences.
function the_occurrence_list($filters = '')
{
?>

<div class="card my-2 p-4 table-responsive">
    <h3 class="visually-hidden">Occurrences</h3>
    <div>
        <div class="row border-bottom py-2" aria-hidden="true">
            <strong class="col-5">
                Message
            </strong>
            <strong class="col-5">
                Code Snippet
            </strong>
            <strong class="col-2">
                Status
            </strong>
        </div>
    </div>
    <div id="occurrencesContainer" aria-live="polite">
        <!-- Messages will be loaded here -->
    </div>
    <div class="d-flex align-items-center mt-2" id="paginationControls">
        <!-- Pagination will be dynamically updated here -->
    </div>
</div>

<script>
    function fetchOccurrences(page) {
        const xhr = new XMLHttpRequest();
        const url = 'api?request=occurrences&columns[]=occurrence_id,occurrence_code_snippet,occurrence_status&joined_columns[]=message_id,message_title&current_results_page=' + page + '&results_per_page=10&<?php echo $filters; ?>';
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                updateOccurrencesContainer(response.occurrences);
                updatePaginationControls(page, response.totalPages);
                console.log(response);
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
        let html = occurrences.length ? '' : '<p>No occurrences found.</p>';
        occurrences.forEach(occurrence => {
            let codeSnippet = occurrence.occurrence_code_snippet.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            html += `
                <a class="row text-body py-2 border-bottom" href="index.php?view=message&message_id=${occurrence.message_id}">
                    <span class="col-5" aria-label="Message">${occurrence.message_title}</span>
                    <span class="col-5" aria-label="Code Snippet"><code><pre>${codeSnippet}</pre></code></span>
                    <span class="col-2 text-capitalize" aria-label="Status">${occurrence.occurrence_status}</span>
                </a>
            `;
        });
        document.getElementById('occurrencesContainer').innerHTML = html;
    }

    function updatePaginationControls(currentPage, totalPages) {
        let paginationControls = document.getElementById('paginationControls');

        if (totalPages <= 1) {
            // If only one page, clear the pagination controls
            paginationControls.innerHTML = '';
        } else {

            let paginationHtml = `
                <p class="text-secondary fs-6 my-0 me-3">
                    Page ${currentPage} of ${totalPages}
                </p>
                <div class="ms-md-auto btn-group d-inline">
            `;

            // Previous Page Button
            if (currentPage > 1) {
                paginationHtml += `<button onclick="fetchOccurrences(${currentPage - 1})" class="btn btn-sm btn-outline-secondary">
                                <span class="visually-hidden">Previous Page of Occurrences</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" /></svg>
                            </button>`;
            }

            // Next Page Button
            if (currentPage < totalPages) {
                paginationHtml += `<button onclick="fetchOccurrences(${currentPage + 1})" class="btn btn-sm btn-outline-secondary">
                                <span class="visually-hidden">Next Page of Occurrences</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" /></svg>
                        </button>`;
            }

            paginationHtml += `</div>`;
            paginationControls.innerHTML = paginationHtml;

        }

    }

    // Initial fetch
    fetchOccurrences(1);
</script>

<?php
}
?>