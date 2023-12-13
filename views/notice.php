<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the single notice view.
 * 
 * "Equalify" means fixing accessibility issues, and so 
 * every aspect of this page should be designed designed
 * to equalify the issue.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
?>

<div class="d-flex flex-column flex-md-row align-items-center my-4">
    <h1 style="max-width:800px">
        Active <code>&lt;area&gt;</code> elements need alternate text
    </h1>
    <div class="ms-md-auto">
        <!-- If the date filter, search, or the toggled statuses change, show save button. Save button Saves current view. -->
        <button class="btn btn-primary">Save</button>
        <!-- Search button toggles search popup, where a user can search for tags, notice messages, and page URLs. -->
        <button class="btn btn-outline-secondary">
            <span class="visually-hidden">Toggle Search</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
            <!-- Note: Search cannot include notice because we are already on a notice page. -->
        </button>
        <button type="button" class="btn btn-outline-secondary" aria-label="Update Date"> 
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
            Jul 1 - Dec 31, 2023
        </button>
    </div>
</div>
<!-- #active_searches is only seen if a search was created -->
<div id="active_searches">
    <!-- Filter added when selected in search -->
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Small button group">
        <!-- Main button pops up search w/ loaded variable -->
        <button type="button" class="btn btn-outline-secondary"><span class="fw-semibold pe-1">Property:</span> Tulane Websites</button>
        <!-- Close button removes filter -->
        <button type="button" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            <span class="visually-hidden">Remove Filter</span>
        </button>
    </div>
    <!-- Filter added when selected in search -->
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Small button group">
        <!-- Main button pops up search w/ loaded variable -->
        <button type="button" class="btn btn-outline-secondary"><span class="fw-semibold pe-1">URL:</span> //tulane</button>
        <!-- Close button removes filter -->
        <button type="button" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            <span class="visually-hidden">Remove Filter</span>
        </button>
    </div>
    <!-- Filter added when selected in search -->
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Small button group">
        <!-- Main button pops up search w/ loaded variable -->
        <button type="button" class="btn btn-outline-secondary">
            <span class="fw-semibold pe-1">Tag:</span> WCAG
        </button>
        <!-- Close button removes filter -->
        <button type="button" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            <span class="visually-hidden">Remove Filter</span>
        </button>
    </div>
    <!-- Filter added when selected in search -->
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Small button group">
        <!-- Main button pops up search w/ loaded variable -->
        <button type="button" class="btn btn-outline-secondary">
            <span class="fw-semibold pe-1">Message:</span> Hello World
        </button>
        <!-- Close button removes filter -->
        <button type="button" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            <span class="visually-hidden">Remove Filter</span>
        </button>
    </div>
    <!-- Filter added when selected in search -->
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Small button group">
        <!-- Main button pops up search w/ loaded variable -->
        <button type="button" class="btn btn-outline-secondary">
            <span class="fw-semibold pe-1">Code:</span> <code class="text-secondary">&lt;p&gt;Sampl</code>
        </button>
        <!-- Close button removes filter -->
        <button type="button" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            <span class="visually-hidden">Remove Filter</span>
        </button>
    </div>
</div>
<div id="reports_filter" class="my-2 rounded-3 bg-secondary text-center p-2 border">
    <ul class="nav d-flex justify-content-around" aria-label="Click to toggle any of these statuses. Toggling a status will hide/show related data.">
        <li class="nav-item">
            <!-- Toggle active class when filter is selected-->
            <a id="equalified" class="active nav-link text-white" aria-current="filter" href="#">
                <span class="h1">3</span><br>Equalified <span class="badge rounded-pill bg-success">+2%<span class="visually-hidden">Change from last month</span></span>
            </a>
        </li>
        <li class="nav-item">
            <!-- Toggle active class when filter is selected-->
            <a id="active" class="active nav-link text-white" aria-current="filter" href="#">
                <span class="h1">10</span><br>Active
            </a>
        </li>
        <li class="nav-item">
            <!-- Toggle active class when filter is selected -->
            <a id="ignored" class="active nav-link text-white" href="#">
                <span class="h1">4</span><br>Ignored
            </a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col-6">
        <div class="card p-4  h-100">
            <h2 class="h3">Plain Language Fix</h2>
            <p class="lead">Wherever you have an <code>&lt;area&gt;</code> element, make sure that element includes <code>alt=</code>.
            <h2 class="h4">Example</h2>
<code><pre>
&lt;img src="images/solar_system.jpg" alt="Solar System" width="472" height="800" usemap="#Map"/&gt;
&lt;map name="Map"&gt;
&lt;area shape="rect" coords="115,158,276,192" href="http://en.wikipedia.org/wiki/Mercury_%28planet%29" alt="Mercury"&gt;
&lt;area shape="rect" coords="115,193,276,234" href="http://en.wikipedia.org/wiki/Venus" alt="Venus"&gt;
&lt;!-- Remaining hotspots in image map... --&gt;
&lt;/map&gt;
</pre></code>
        </div>
    </div>
    <div class="col-6">
        <div class="card p-4 h-100">
            <h2 class="visually-hidden">Notices Over Time</h2>
            <canvas id="equalifiedByTime" class="" style="height: 300px"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                var ctx = document.getElementById('equalifiedByTime').getContext('2d');
                var equalifiedByTime = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ["Jul '23", "Aug '23", "Sep '23", "Oct '23", "Nov '23", "Dec '23"],
                        datasets: [
                            {
                                label: 'Equalified',
                                data: [1, 2, 2, 2, 3, 3],
                                borderColor: 'rgba(11, 101, 21, 1)',
                                backgroundColor: 'rgba(11, 101, 21, .6)',
                            }
                            ,{
                                label: 'Ignored',
                                data: [4, 5, 10, 5, 0, 4],
                                borderColor: 'rgba(108, 117, 125, 1)',
                                backgroundColor: 'rgba(108, 117, 125, .6)',
                            }
                            ,{
                                label: 'Active',
                                data: [6, 5, 11, 9, 14, 10],
                                borderColor: 'rgba(171, 39, 12, 1)',
                                backgroundColor: 'rgba(171, 39, 12, .6)',
                            }
                        ]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        elements: {
                            line: {
                                borderWidth: 4
                            }
                        }
                    }
                });
            });
            </script>
        </div>
    </div>
</div>
<div class="card my-2 p-4 table-responsive">
    <h2 class="visually-hidden">Occurrences</h2>
    <!-- This would dynamically change, based on filters. (Ie- If "ignored" is not active, no ignored items would show.) -->
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
            <td><a href="index.php?view=page&id=">tulane.edu/fun</td>
            <td><code>&lt;img src=&quot;images/solar_system.jpg&quot; alt=&quot;Solar System&quot; width=&quot;472&quot;</code></td>
            <td>Active</td>
            <td>Equalify Scan</td>
            <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
        </tr>
        <tr>
            <td><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</td>
            <td><code>&lt;area shape=&quot;rect&quot; coords=&quot;115,158,276,192&quot; href=&quot;</code></td>
            <td>Equalified</td>
            <td>Equalify Scan</td>
            <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
        </tr>
        <tr>
            <td><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</td>
            <td><code>&lt;area shape=&quot;rect&quot; coords=&quot;115,193,276,234&quot; href=&quot;http://en.wikipedia.org/wi</code></td>
            <td>Ignored</td>
            <td>Equalify Scan</td>
            <td><button class="btn btn-sm btn-outline-secondary">Edit</button></td>
        </tr>
        <tr>
            <td><a href="index.php?view=page&id=">giving.tulane.edu/example-of-a-long-url</td>
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