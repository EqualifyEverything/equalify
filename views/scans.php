<?php
// Helpers
require_once('helpers/get_scans.php');
require_once('helpers/get_scans_count.php');

// Pagination Setup
$limit = 25; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$page = max(1, $page); // Ensure the current page is not less than 1
$totalScans = get_scans_count();
$totalPages = ceil($totalScans / $limit);
$page = min($page, $totalPages); // Ensure the current page is not more than the last page
$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);

// Setup scans
$scans = get_scans($page, $limit);

?>

<div class="container">
    <h1 class="display-5 my-4">Scans</h1>
    <div class="card  bg-white p-4 my-2">
      <h2 class="mb-2">Scan Queue</h2>
      <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Job Id</th>
            <th scope="col">Page URL</th>
            <th scope="col">Property</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>

        <?php if (empty($scans)): ?>

          <tr>
              <td colspan="4">No scans available.</td>
          </tr>

        <?php else: ?>

          <?php foreach ($scans as $scan): ?>

            <tr>
                <td><?php echo htmlspecialchars($scan['queued_scan_job_id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($scan['page_url'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($scan['property_name'] ?? ''); ?></td>
                <td>

                  <?php
                    // Determine the status based on 'queued_scan_processing'
                    $status = $scan['queued_scan_processing'];
                    if ($status === null) {
                        echo 'Queued';
                    } elseif ($status == 1) {
                        echo 'Processing';
                    } else {
                        echo 'Idle'; // You can adjust this as per your system's status representation
                    }
                  ?>

                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Run Scan</button>
                </td>
            </tr>
            
        <?php endforeach; ?>

        <?php endif; ?>

        </tbody>
      </table>
      <nav aria-label="Page navigation" class="d-flex justify-content-center">
          <ul class="pagination">
              <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                  <a class="page-link" href="?view=scans&page=<?php echo $prevPage; ?>" aria-label="Previous">
                      <span aria-hidden="true">&laquo; Previous</span>
                  </a>
              </li>
              <li class="page-item disabled">
                  <span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
              </li>
              <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                  <a class="page-link" href="?view=scans&page=<?php echo $nextPage; ?>" aria-label="Next">
                      <span aria-hidden="true">Next &raquo;</span>
                  </a>
              </li>
          </ul>
      </nav>
    </div>
</div>