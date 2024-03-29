<?php
// Creates a list of occurrences.
function the_occurrence_list($filters = '')
{
    global $report_id;
?>

<div class="card my-2 p-4">
    <h3 class="visually-hidden">Messages on Page</h3>
    <div id="pageOccurrencesListAccessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 45%">
                        Message
                    </th>
                    <th scope="col">
                        Code Snippet
                    </th>
                    <th scope="col">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody id="occurrencesContainer" aria-live="polite">
                <!-- Ocurrences will be loaded here -->
            </tbody>
        </table>
    </div>
    <div class="d-flex align-items-center mt-2" id="paginationControls">
        <!-- Pagination will be dynamically updated here -->
    </div>
</div>

<script>
    function fetchOccurrences(page) {
        const announcer = document.getElementById('pageOccurrencesListAccessibilityAnnouncer');
        announcer.textContent = 'Loading occurrences, please wait.';

        const xhr = new XMLHttpRequest();
        const url = 'api/index.php?request=occurrences&columns[]=occurrence_id,occurrence_code_snippet,occurrence_status&joined_columns[]=message_id,message_title&current_results_page=' + page + '&results_per_page=10&<?php echo $filters; ?>';
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                updateOccurrencesContainer(response.occurrences);
                updatePaginationControls(page, response.totalPages);
                announcer.textContent = `Page ${page} of occurrences loaded.`;
            } else {
                document.getElementById('occurrencesContainer').innerHTML = 'Error loading occurrences.';
                announcer.textContent = 'Error loading occurrences data.';
            }
        };
        xhr.onerror = function() {
            console.error("Error on AJAX request.");
            document.getElementById('occurrencesContainer').innerHTML = 'Error loading occurrences.';
            announcer.textContent = 'Error loading occurrences data.';
        };
        xhr.send();
    }

    function updateOccurrencesContainer(occurrences) {
        let html = occurrences.length ? '' : '<p>No occurrences found.</p>';
        occurrences.forEach(occurrence => {
            let codeSnippet = occurrence.occurrence_code_snippet.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            html += `
                <tr>
                    <td> 
                        <a class="link-dark link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="index.php?view=message&report_id=<?php echo $report_id;?>&message_id=${occurrence.message_id}">
                            <span class="visually-hidden">Message:</span> ${occurrence.message_title}
                        </a>
                    </td>
                    <td>
                        Code Snippet:</span> <pre><code>${codeSnippet}</code></pre>
                    </td>
                    <td>
                        <span class="visually-hidden">Status:</span> ${occurrence.occurrence_status}
                    </td>
                </tr>
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
