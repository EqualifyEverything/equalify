<?php
// Creates a list of pages with the percent equalfied.
function the_message_occurrences_list($filters = '')
{
?>
<div class="card my-2 p-4 table-responsive">
    <h2 class="visually-hidden">Occurrences</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Page</th>
                <th scope="col">Code Snippet</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody id="messageOcurrencesContainer" aria-live="polite"><!-- Ocurrences will be loaded here --></tbody>
    </table>
    <div class="d-flex align-items-center mt-2" id="messageOccurrencesPaginationControls">
        <!-- Pagination for pages will be dynamically updated here -->
    </div>
</div>

    <script>
        function fetchPages(page) {
            const xhr = new XMLHttpRequest();
            const url = 'api?request=occurrences&columns[]=occurrence_code_snippet,occurrence_status&joined_columns[]=page_url,page_id&current_results_page=' + page + '&<?php echo $filters; ?>';
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    updateMessageOccurrencesContainer(response.occurrences);
                    updateMessageOccurrencesPagination(page, response.totalPages);
                } else {
                    document.getElementById('messageOcurrencesContainer').innerHTML = 'Error loading pages.';
                }
            };
            xhr.send();
        }

        function updateMessageOccurrencesContainer(occurrences) {
            let html = occurrences.length ? '' : '<tr><td colspan="3">No pages found.</td></tr>';
            occurrences.forEach(occurrence => {
                let codeSnippet = occurrence.occurrence_code_snippet.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                html += `
                    <tr>
                        <td><a class="text-truncate" href="index.php?view=page&id=${occurrence.page_id}">${occurrence.page_url}</td>
                        <td><code>${codeSnippet}</code></td>
                        <td class="text-capitalize">${occurrence.occurrence_status}</td>
                    </tr>

        `;
            });
            document.getElementById('messageOcurrencesContainer').innerHTML = html;
        }

        function updateMessageOccurrencesPagination(currentPage, totalPages) {
            let paginationControls = document.getElementById('messageOccurrencesPaginationControls');

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
                    paginationHtml += `<button onclick="fetchPages(${currentPage - 1})" class="btn btn-sm btn-outline-secondary">
                                    <span class="visually-hidden">Previous Page of Pages</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" /></svg>
                                </button>`;
                }

                // Next Page Button
                if (currentPage < totalPages) {
                    paginationHtml += `<button onclick="fetchPages(${currentPage + 1})" class="btn btn-sm btn-outline-secondary">
                                    <span class="visually-hidden">Next Page of Pages</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" /></svg>
                            </button>`;
                }

                paginationHtml += `</div>`;
                paginationControls.innerHTML = paginationHtml;

            }

        }

        // Initial fetch
        fetchPages(1);
    </script>

<?php
}
?>