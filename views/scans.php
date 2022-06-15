<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Scans</h1>
        </div>
        <div>
            <a href="actions/queue_scan.php" class="btn btn-primary">New Scan</a>

        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody id="the_scans_rows">

            <?php

            // Show scans
            $scans = DataAccess::get_scans();
            the_scan_rows($scans);

            ?>

        </tbody>
    </table>
</section>