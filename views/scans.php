<div class="container">
    <h1 class="display-5 my-4">Scans</h1>
    <div class="card bg-white p-4 my-2">
      <div class="d-flex flex-column flex-md-row align-items-center my-4">
          <h2 class="mb-0 me-2">Scan Queue</h2>
          <div class="ms-md-auto">

            <?php
            if(isset($_ENV['CONCURRENT_SCANS'])){
              $concurrent_scan_max = $_ENV['CONCURRENT_SCANS'];
            }else{
              // Concurrent scans default to 20 - see process_scans.php
              $concurrent_scan_max = 20;
            }
            ?>

            <button class="btn btn-primary" id="process_multiple_scans">Process <?php echo $concurrent_scan_max;?> Scans</button>
          </div>
      </div>
      <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Job Id</th>
            <th scope="col">Page URL</th>
            <th scope="col">Property</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody id="scanTableBody">
          <!-- Table rows will be populated by JavaScript -->
        </tbody>
      </table>
      <p id="scanStatusText" class="text-center"></p>
      <nav aria-label="Page navigation" class="d-flex justify-content-center">
          <ul class="pagination">
              <!-- Pagination will be populated by JavaScript -->
          </ul>
        </nav>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const resultsPerPage = 25;
  const scanTableBody = document.getElementById('scanTableBody');
  const paginationContainer = document.querySelector('.pagination');
  const scanStatusText = document.getElementById('scanStatusText');
  const processMultipleScansButton = document.getElementById('process_multiple_scans');

  // Set the scanStatusText element to be an ARIA live region for accessibility
  scanStatusText.setAttribute('aria-live', 'polite');

  // Function to disable all individual scan buttons
  function disableAllProcessScanButtons() {
    document.querySelectorAll('.process_single_scan').forEach(button => {
      button.disabled = true;
    });
  }

  // Update scan table with new data
  function updateScanTable(scans, currentPage, totalPages) {
    let processingExists = scans.some(scan => scan.queued_scan_processing); // Check if any scan is processing
    
    if (scans.length === 0) {
      // If there are no scans, display a fallback message
      scanTableBody.innerHTML = `
          <tr>
              <td colspan="5" class="text-center">No scans queued.</td>
          </tr>
      `;
      // Hide pagination and disable the 'Process Multiple Scans' button if no scans exist
      paginationContainer.style.display = 'none';
      processMultipleScansButton.disabled = true;
      scanStatusText.textContent = 'No scans queued';
    } else {
      // Populate table with scan data
      scanTableBody.innerHTML = scans.map(scan => `
          <tr>
              <td>${scan.queued_scan_job_id}</td>
              <td>${scan.page_url}</td>
              <td>${scan.property_name}</td>
              <td>
                  <button class="process_single_scan btn btn-sm btn-outline-primary" 
                          data-queued_scan_job_id="${scan.queued_scan_job_id}" 
                          data-property_id="${scan.queued_scan_property_id}"
                          ${scan.queued_scan_processing ? 'disabled' : ''}>Process Scan</button>
              </td>
          </tr>
      `).join('');

      // Show pagination
      paginationContainer.style.display = 'flex';

      // Update the text showing the current page of total pages
      scanStatusText.textContent = `Showing page ${currentPage} of ${totalPages}`;

      // Enable or disable the 'Process Multiple Scans' button
      processMultipleScansButton.disabled = processingExists;
    }
  }

  
  // Fetch and display scan data
  function refreshScanData(page = 1) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/index.php?request=queued_scans&current_results_page=${page}&results_per_page=${resultsPerPage}`, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            updateScanTable(response.scans, page, response.totalPages); // Pass page and totalPages to updateScanTable
            updatePagination(page, response.totalPages); // Update pagination and status text
        } else {
            console.error('Error fetching scan data:', xhr.statusText);
        }
    };
    xhr.onerror = function() {
        console.error('Request failed');
    };
    xhr.send();
  }

  // Update pagination
  function updatePagination(currentPage, totalPages) {

    // Update the page status text
    if(totalPages > 0){
      scanStatusText.textContent = `Showing page ${currentPage} of ${totalPages}.`;
    }else{
      scanStatusText.textContent = ``;
    }

    // Clear existing pagination controls
    paginationContainer.innerHTML = '';

    // Previous Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous" data-page="${currentPage - 1}">&laquo; Previous Page</a>`;
    paginationContainer.appendChild(prevLi);

    // Next Button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next" data-page="${currentPage + 1}">Next Page &raquo;</a>`;
    paginationContainer.appendChild(nextLi);

    // Add event listeners to the pagination controls
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const page = parseInt(this.dataset.page);
            if (page !== currentPage && page >= 1 && page <= totalPages) {
                refreshScanData(page); // Fetch and display the data for the selected page
            }
        });
    });

  }

  // Event delegation for process scan buttons
  scanTableBody.addEventListener('click', function(event) {
    const button = event.target.closest('.process_single_scan');
    if (button) {
      processScan(button);
    }
  });

  // Process an individual scan
  function processScan(button) {
    button.disabled = true;
    const jobId = button.getAttribute('data-queued_scan_job_id');
    const propertyId = button.getAttribute('data-property_id');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'actions/process_scans.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status >= 200 && xhr.status < 300) {
        console.log('Success:', xhr.responseText);
        refreshScanData(); // Refresh the scan data to reflect the changes
      } else {
        console.error('Error:', xhr.statusText);
        button.disabled = false; // Optionally re-enable the button if there was an error
      }
    };
    xhr.onerror = function() {
      console.error('Request failed');
      button.disabled = false; // Optionally re-enable the button if there was a network error
    };
    xhr.send('job_id=' + jobId + '&property_id=' + propertyId);
  }

  // Process multiple scans
  document.getElementById('process_multiple_scans').addEventListener('click', function() {
    this.disabled = true;
    disableAllProcessScanButtons(); // Disable all individual scan buttons

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'actions/process_scans.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status >= 200 && xhr.status < 300) {
        console.log('Success:', xhr.responseText);
        refreshScanData(); // Refresh the scan data to reflect the changes
      } else {
        console.error('Error:', xhr.statusText);
        this.disabled = false; // Re-enable the button if there was an error
      }
    }.bind(this);
    xhr.onerror = function() {
      console.error('Request failed');
      this.disabled = false; // Re-enable the button if there was a network error
    }.bind(this);
    xhr.send('process_multiple_scans=true');
  });

  // Initial scan data load and setup pagination
  refreshScanData();

});
</script>