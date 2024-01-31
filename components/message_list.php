<?php
// Creates a list of messages with count of different statuses.
function the_message_list($filters = '')
{

    global $report_id;

?>

<div class="card my-2 p-4">
    <h3 class="visually-hidden">Messages</h3>
    <div id="messageListAccessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Messages</th>
                    <th scope="col" class="text-center">Equalified <span class="visually-hidden">Count</span></th>
                    <th scope="col" class="text-center">Active <span class="visually-hidden">Count</span></th>
                    <th scope="col" class="text-center">Total <span class="visually-hidden">Count</span></th>
                </tr>
            </thead>
            <tbody id="messagesContainer" aria-live="polite">
                <!-- Ocurrences will be loaded here -->
            </tbody>
        </table>
    </div>
    <div class="d-flex align-items-center mt-2" id="paginationControls">
        <!-- Pagination will be dynamically updated here -->
    </div>
</div>

<script>
    function fetchMessages(page) {
        const xhr = new XMLHttpRequest();
        const url = 'api?request=messages&results_per_page=10&current_results_page=' + page + '&<?php echo $filters; ?>';
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                updateMessagesContainer(response.messages);
                updatePaginationControls(page, response.totalPages);
                updateAccessibilityAnnouncer(`Page ${page} of messages loaded.`);
            } else {
                document.getElementById('messagesContainer').innerHTML = 'Error loading messages.';
                updateAccessibilityAnnouncer(`An error occurred while loading messages.`);
            }
        };
        xhr.onerror = function() {
            console.error("Error on AJAX request.");
            updateAccessibilityAnnouncer(`An error occurred while loading messages.`);
        };
        xhr.send();
    }

    function updateMessagesContainer(messages) {
        let html = messages.length ? '' : '<p>No messages found.</p>';
        messages.forEach(message => {
            // Ensure counts are numbers
            const equalifiedCount = parseInt(message.equalified_count, 10);
            const activeCount = parseInt(message.active_count, 10);
            const totalCount = parseInt(message.total_count, 10);

            // Add HTML for each message
            html += `
                <tr>
                    <td> 
                        <a class="link-dark link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="index.php?view=message&report_id=<?php echo $report_id;?>&message_id=${message.message_id}">
                            <span class="visually-hidden">Message:</span> ${message.message_title}
                        </a>
                    </td>
                    <td class="text-center">
                        ${equalifiedCount.toLocaleString('en', {useGrouping:true})} <span class="visually-hidden">Equalified of this Message</span>
                    </td>
                    <td class="text-center">
                        ${activeCount.toLocaleString('en', {useGrouping:true})} <span class="visually-hidden">Active of this Message</span>
                    </td>
                    <td class="text-center">
                        ${totalCount.toLocaleString('en', {useGrouping:true})} <span class="visually-hidden">Total of this Message</span>
                    </td>
                </tr>
            `;
        });
        document.getElementById('messagesContainer').innerHTML = html;
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
                paginationHtml += `<button onclick="fetchMessages(${currentPage - 1})" class="btn btn-sm btn-outline-secondary">
                                <span class="visually-hidden">Previous Page of Messages</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" /></svg>
                        </button>`;
            }

            // Next Page Button
            if (currentPage < totalPages) {
                paginationHtml += `<button onclick="fetchMessages(${currentPage + 1})" class="btn btn-sm btn-outline-secondary">
                                <span class="visually-hidden">Next Page of Messages</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" /></svg>
                        </button>`;
            }

            paginationHtml += `</div>`;
            paginationControls.innerHTML = paginationHtml;
        }

    }

    function updateAccessibilityAnnouncer(message) {
        document.getElementById('messageListAccessibilityAnnouncer').textContent = message;
    }

    // Initial fetch
    fetchMessages(1);
</script>

<?php
}
?>