<?php
// Helpers
require_once('helpers/get_scans.php');
require_once('helpers/get_scans_count.php');
require_once('components/success_or_error_message.php');

// Pagination Setup
$results_per_page = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$page = max(1, $page); // Ensure the current page is not less than 1
$totalScans = get_scans_count();
$totalPages = ceil($totalScans / $results_per_page);
$page = min($page, $totalPages); // Ensure the current page is not more than the last page
$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);

// Calculate the offset
$offset = ($page - 1) * $results_per_page;

// Setup scans using new get_scans function
$scans = get_scans($results_per_page, $offset);
?>

<div class="container">
  
    <?php
    // Success or Error message
    the_success_or_error_message();
    ?>

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

            <a class="btn btn-primary" href="actions/process_scans.php">Process <?php echo $concurrent_scan_max;?> Scans</a>
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
        <tbody>

        <?php if (empty($scans)): ?>

          <tr>
              <td colspan="5">No scans queued.</td>
          </tr>

        <?php else: ?>

          <?php foreach ($scans as $scan): ?>

            <tr>
                <td><?php echo htmlspecialchars($scan['queued_scan_job_id']); ?></td>
                <td><?php echo htmlspecialchars($scan['page_url'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($scan['property_name']); ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="actions/process_scans.php?<?php echo 'job_id='.$scan['queued_scan_job_id'].'&property_id='.$scan['queued_scan_property_id'];?>">Scan Page</a>
                </td>
            </tr>
            
        <?php endforeach; ?>

        <?php endif; ?>

        </tbody>
      </table>
      <nav aria-label="Pages of Queued Scans" class="d-flex justify-content-center">
          <ul class="pagination">

              <?php 
              // Only show link if there are multiple pages to clear up DOM for screen readers.
              if ($totalPages > 1 && $page > 1): 
              ?>

              <li class="page-item">
                  <a class="page-link" href="?view=scans&page=<?php echo $prevPage; ?>" aria-label="Previous Page of Scans">
                      <span aria-hidden="true">&laquo; Previous</span>
                  </a>
              </li>

              <?php
              endif;
              ?>

              <li class="page-item disabled">
                  <span class="page-link"><span class="visually-hidden">Currently on Scan Queue Results</span> Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
              </li>

              <?php 
              // Only show link if there are multiple pages to clear up DOM for screen readers.
              if ($page < $totalPages): 
              ?>

              <li class="page-item">
                  <a class="page-link" href="?view=scans&page=<?php echo $nextPage; ?>" aria-label="Next Page of Scans">
                      <span aria-hidden="true">Next &raquo;</span>
                  </a>
              </li>

              <?php
              endif;
              ?>

          </ul>
      </nav>
    </div>
</div>