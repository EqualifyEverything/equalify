<section>
    <h1 class="mb-3 pb-4 border-bottom">
        All Alerts
    </h1>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">View One <span class="ms-1 badge bg-primary rounded">14</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">View Two <span class="ms-1 badge bg-secondary rounded">2</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">View Three <span class="ms-1 badge bg-secondary rounded">19</span></a>
        </li>
        <li class="nav-item">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewSettingsModal">+ Add View</button>
        </li>
    </ul>

    <div class="row row-cols-lg-auto g-3 align-items-center mb-3">
        <div class="col-12">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" placeholder="Keyword or Website.." aria-label="Search Term" aria-describedby="basic-addon1">
                <button class="btn btn-outline-secondary" type="button">Search</button>
            </div>
        </div>
        <div class="col-12">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewSettingsModal">
                View Filters & Settings
            </button>
            <div class="modal fade" id="viewSettingsModal" aria-labelledby="filterModalLabel" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title h4" id="filterModalLabel">"View One"</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="actions/save_alert_filters.php">
                            <div class="modal-body">
                                <h3 class="h5">Filters</h3>
                                <div class="row gx-5 mb-3">
                                    <div class="col">
                                        <p class="form-label fw-semibold">Integrations</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="integration_little_forrest">
                                            <label class="form-check-label" for="integration_little_forrest">
                                                Little Forrest
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="integration_wave">
                                            <label class="form-check-label" for="integration_wave">
                                                WAVE
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <p class="form-label fw-semibold">Alert Types</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="type-error">
                                            <label class="form-check-label" for="type-error">
                                                Error
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="type-warning">
                                            <label class="form-check-label" for="type-warning">
                                                Warning
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="type-notice">
                                            <label class="form-check-label" for="type-notice">
                                                Notice
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <p class="form-label fw-semibold">Alert Source</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="source-error">
                                            <label class="form-check-label" for="source-error">
                                                Page
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="source-system">
                                            <label class="form-check-label" for="source-system">
                                                System
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="metaFilter1" class="form-label fw-semibold">Little Forrest Guidelines</label>
                                    <input type="text" id="metaFilter1" class="form-control" aria-describedby="metaFilter1Help">
                                    <div id="metaFilter1Help" class="form-text">All possible guidelines are listed on <a href="https://littleforest.co.uk/">Little Forrest's website</a>. Separate guidelines with commas.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="metaFilter1" class="form-label fw-semibold">Little Forrest Tags</label>
                                    <input type="text" id="metaFilter1" class="form-control" aria-describedby="metaFilter2Help">
                                    <div id="metaFilter2Help" class="form-text">All possible tags are listed on <a href="https://littleforest.co.uk/">Little Forrest's website</a>. Separate tags with commas.</div>
                                </div>
                                <hr>
                                <h3 class="h5">Settings</h3>
                                <div class="mb-3">
                                    <label for="viewSetting1" class="form-label fw-semibold">View Name</label>
                                    <input type="text" id="viewSetting2" class="form-control" aria-describedby="metaFilter1Help" value="View One">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-outline-danger">Delete View</button>
                                <button type="submit" class="btn btn-primary">Save View</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 fs-7 text-secondary">
            Showing xx of xxx results.
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // Begin Alerts
        $alerts = DataAccess::get_alerts([], get_current_page_number());
        $alerts_content = $alerts['content'];
        if(count($alerts_content) > 0 ): foreach($alerts_content as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">
                <?php 
                // Integration alerts link to the integration.
                if( $alert->source == 'page' ){
                    echo '<a class="btn btn-primary btn-sm"  href="'.DataAccess::get_site_details_uri($alert->page_id).'">Site Details</a>';
                }
                ?>

                <a href="actions/delete_alert.php?id=<?php echo $alert->id;?>" class="btn btn-outline-secondary btn-sm">
                    Dismiss
                </a>
            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="4">No alerts found.</td>
        </tr>

        <?php 
        // End Alerts
        endif;
        ?>

    </table>

    <?php
    // The pagination
    the_pagination($alerts['total_pages']);
    ?>

</section>