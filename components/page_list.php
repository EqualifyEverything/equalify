<?php
// Creates a list of pages with the percent equalfied.
function the_page_list($filters = '')
{
    global $report_id;
?>
    <div class="card pt-2 px-4 my-2 h-100">
        <h3 class="visually-hidden">Pages</h3>
        <div class="row border-bottom py-2" aria-hidden="true">
            <strong class="col-7">URL</strong>
            <strong class="col-3">Active</strong>
        </div>
        <div id="pagesContainer" aria-live="polite"><!-- Pages will be loaded here --></div>
        <div class="d-flex align-items-center mt-2" id="paginationControlsPages">
            <!-- Pagination for pages will be dynamically updated here -->
        </div>
    </div>

    <script>
        function fetchPages(page) {
            const xhr = new XMLHttpRequest();
            const url = 'api?request=pages&current_results_page=' + page + '&<?php echo $filters; ?>';
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    updatePagesContainer(response.pages);
                    updatePaginationControlsPages(page, response.totalPages);
                } else {
                    document.getElementById('pagesContainer').innerHTML = 'Error loading pages.';
                }
            };
            xhr.send();
        }

        function updatePagesContainer(pages) {
            let html = pages.length ? '' : '<p class="my-2">No pages found.</p>';
            pages.forEach(page => {
                // Ensure counts are numbers
                const activeCount = parseInt(page.page_occurrences_active, 10);

                html += `
            <a href="?view=page&report_id=<?php echo $report_id;?>&page_id=${page.page_id}" class="row text-body py-2 border-bottom">
                <span class="col-7 text-truncate">${page.page_url}</span>
                <span class="col-3 text-truncate">${activeCount.toLocaleString('en', {useGrouping:true})}</span>
            </a>
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