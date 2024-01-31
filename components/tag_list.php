<?php
// Creates a list of tags with the number of occurrences per tag.
function the_tag_list($filters = '')
{
    global $report_id;
?>
    <div class="card pt-2 px-4 my-2 h-100">
        <h3 class="visually-hidden">Tags</h3>
        <div id="tagListAccessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
        <div class="row border-bottom py-2" aria-hidden="true">
            <strong class="col-7">Tag</strong>
            <strong class="col-3">Occurrences</strong>
        </div>
        <div id="tagsContainer" aria-live="polite"><!-- Tags will be loaded here --></div>
        <div class="d-flex align-items-center mt-2" id="paginationControlsTags">
            <!-- Pagination for tags will be dynamically updated here -->
        </div>
    </div>

    <script>
        function fetchTags(page) {
            const announcer = document.getElementById('tagListAccessibilityAnnouncer');
            announcer.textContent = 'Loading tags, please wait.';

            const xhr = new XMLHttpRequest();
            const url = 'api?request=tags&current_results_page=' + page + '&<?php echo $filters; ?>';
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        updateTagsContainer(response.tags);
                        updatePaginationControlsTags(page, response.totalPages);
                        announcer.textContent = `Page ${page} of tags loaded.`;
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        document.getElementById('tagsContainer').innerHTML = 'Error processing response.';
                        announcer.textContent = 'Error processing tag data.';
                    }
                } else {
                    document.getElementById('tagsContainer').innerHTML = 'Error loading tags.';
                    announcer.textContent = 'Error loading tag data.';
                }
            };
            xhr.onerror = function() {
                console.error("Error on AJAX request.");
                document.getElementById('tagsContainer').innerHTML = 'Error loading tags.';
                announcer.textContent = 'Error loading tag data.';
            };
            xhr.send();
        }

        function updateTagsContainer(tags) {
            let html = tags.length ? '' : '<p class="my-2">No tags found.</p>';
            tags.forEach(tag => {
                // Ensure counts are numbers
                const tagReferenceCount = parseInt(tag.tag_reference_count, 10);

                html += `
                    <a href="?view=tag&report_id=<?php echo $report_id;?>&tag_id=${tag.tag_id}" class="row text-body py-2 border-bottom">
                        <span class="col-7 text-truncate">${tag.tag_name}</span>
                        <span class="col-3 text-truncate">${tagReferenceCount.toLocaleString('en', {useGrouping:true})}</span>
                    </a>
                `;
            });
            document.getElementById('tagsContainer').innerHTML = html;
        }
        
        function updatePaginationControlsTags(currentPage, totalPages) {
            let paginationControls = document.getElementById('paginationControlsTags');

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
                    paginationHtml += `<button onclick="fetchTags(${currentPage - 1})" class="btn btn-sm btn-outline-secondary">
                                    <span class="visually-hidden">Previous Page of Tags</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" /></svg>
                            </button>`;
                }

                // Next Page Button
                if (currentPage < totalPages) {
                    paginationHtml += `<button onclick="fetchTags(${currentPage + 1})" class="btn btn-sm btn-outline-secondary">
                                    <span class="visually-hidden">Next Page of Tags</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" /></svg>
                            </button>`;
                }
                paginationHtml += `</div>`;
                paginationControls.innerHTML = paginationHtml;
            }
        }

        // Initial fetch
        fetchTags(1);
    </script>

<?php
}
?>