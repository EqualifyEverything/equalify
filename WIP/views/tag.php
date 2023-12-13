<?php
// The tag_id URL parameter defines the tag.
$tag_id = $_GET['tag_id'];

// For testing purposes, we'll define the tag.
$tag_id = 81;

// Let's get the various components we need to create the view.
require_once('../components/header.php');
require_once('../components/footer.php');
require_once('../components/chart_of_status.php');
require_once('../components/page_list.php');

?>
    <div class="row">
            <div class="col">
                <div class="card my-2 p-4">
                    <h2 class="visually-hidden">Status Occurrences Over Time</h2>

                    <?php
                    // Chart component.
                    the_chart_of_status('tag_ids=33');
                    ?>

                </div>
            </div>
        </div>
    <div class="card my-2 p-4 table-responsive">
        <h2 class="visually-hidden">Occurrences</h2>
        <table class="table">
        <thead>
            <tr>
                <th scope="col">Page</th>
                <th scope="col">Code Snippet</th>
                <th scope="col">Status</th>
                <th scope="col">Source</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row"><a href="index.php?view=page&id=">tulane.edu/fun</a></th>
                <td><code>&lt;img src=&quot;images/solar_system.jpg&quot; alt=&quot;Solar System&quot; width=&quot;472&quot;</code></td>
                <td>Active</td>
                <td>Equalify Scan</td>
                <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
            </tr>
            <tr>
                <th scope="row"><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</a></th>
                <td><code>&lt;area shape=&quot;rect&quot; coords=&quot;115,158,276,192&quot; href=&quot;</code></td>
                <td>Equalified</td>
                <td>Equalify Scan</td>
                <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
            </tr>
            <tr>
                <th scope="row"><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</a></th>
                <td><code>&lt;area shape=&quot;rect&quot; coords=&quot;115,193,276,234&quot; href=&quot;http://en.wikipedia.org/wi</code></td>
                <td>Ignored</td>
                <td>Equalify Scan</td>
                <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
            </tr>
            <tr>
                <th scope="row"><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</a></th>
                <td><code>&lt;area shape=&quot;rect&quot; coords=&quot;115,193,276,234&quot; href=&quot;http://en.wikipedia.org/wi</code></td>
                <td>Ignored</td>
                <td>Manually Added</td>
                <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
            </tr>
        </tbody>
        </table>
        <div class="d-flex align-items-center">
            <p class="text-secondary fs-6 my-0 me-3">
                17 Occurrences
            </p>
            <!-- Toggle buttons only if there are over 5 items, and toggle disabled if no prev/next items -->
            <div class="ms-md-auto btn-group d-inline">
                <button class="btn btn-sm btn-outline-secondary" disabled>
                    <span class="visually-hidden">Previous Page of Items</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                </button>
                <button class="btn btn-sm btn-outline-secondary">
                    <span class="visually-hidden">Next Page of Items</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>
