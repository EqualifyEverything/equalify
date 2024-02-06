<?php
// Creates a list of pages with the percent equalfied.
function the_page_list($filters = '')
{
    global $report_id;
?>
    <div class="card p-4 h-100">
        <h3 class="visually-hidden">Pages</h3>
        <div id="pageListAccessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">URL</th>
                        <th scope="col" class="text-center">Active <span class="visually-hidden">Occurrences Count</span></th>
                    </tr>
                </thead>
                <tbody id="pagesContainer" aria-live="polite">
                    <!-- Pages will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="d-flex align-items-center mt-2" id="paginationControlsPages">
            <!-- Pagination for pages will be dynamically updated here -->
        </div>
    </div>

    <script>
        function fetchPages(page) {
            const announcer = document.getElementById('pageListAccessibilityAnnouncer');
            announcer.textContent = 'Loading pages, please wait.';

            const xhr = new XMLHttpRequest();
            const url = 'api/index.php?request=pages&current_results_page=' + page + '&<?php echo $filters; ?>';
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    updatePagesContainer(response.pages);
                    updatePaginationControlsPages(page, response.totalPages);
                    announcer.textContent = `Page ${page} of pages loaded.`;
                } else {
                    document.getElementById('pagesContainer').innerHTML = 'Error loading pages.';
                    announcer.textContent = 'Error loading page data.';
                }
            };
            xhr.onerror = function() {
                console.error("Error on AJAX request.");
                document.getElementById('pagesContainer').innerHTML = 'Error loading pages.';
                announcer.textContent = 'Error loading page data.';
            };
            xhr.send();
        }

        function updatePagesContainer(pages) {
            let html = pages.length ? '' : '<p class="my-2">No pages found.</p>';
            pages.forEach(page => {
                // Ensure counts are numbers
                const activeCount = parseInt(page.page_occurrences_active, 10);

                html += `
                    <tr>
                        <td> 
                            <a class="link-dark link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover d-inline-block text-truncate" style="max-width: 350px;" href="?view=page&report_id=<?php echo $report_id;?>&page_id=${page.page_id}" >
                               <span class="visually-hidden">Page:</span> ${page.page_url}
                            </a>
                        </td>
                        <td class="text-center">
                            ${activeCount.toLocaleString('en', {useGrouping:true})} <span class="visually-hidden">Active Occurrences on this Page</span>
                        </td>
                    </tr>
                `;
            });
            document.getElementById('pagesContainer').innerHTML = html;
        }

        function updatePaginationControlsPages(currentPage, totalPages) {
            let paginationControls = document.getElementById('paginationControlsPages');

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