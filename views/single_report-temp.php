<?php
// "Equalify" means fixing accessibility issues, and so 
// every aspect of our reporting page should be designed
// designed to equalify as many issues as possible.
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="d-flex flex-column flex-md-row align-items-center my-4">
    <h1>Tulane Accessibility</h1>
    <div class="ms-md-auto">
        <!-- If the date filter, search, or the toggled statuses change, show save button. Save button Saves current view. -->
        <button class="btn btn-primary">Save</button>
        <!-- Search button toggles search popup, where a user can search for tags, notice messages, and page URLs. -->
        <button class="btn btn-outline-secondary">
            <span class="visually-hidden">Toggle Search</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
        </button>
        <button type="button" class="btn btn-outline-secondary" aria-label="Update Date"> 
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
            Jul 1 - Dec 31, 2023
        </button>
        <a href="index.php?view=single_report_settings" class="btn btn-outline-secondary">
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434l.071-.286zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5zm0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78h4.723zM5.048 3.967c-.03.021-.058.043-.087.065l.087-.065zm-.431.355A4.984 4.984 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8 4.617 4.322zm.344 7.646.087.065-.087-.065z"/>
            </svg>
            <span class="visually-hidden">Report Settings</span>
        </a>
    </div>
</div>
<!-- #active_searches is only seen if a search was created -->
<div id="active_searches">
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
                <span class="h1">233</span><br>Equalified <span class="badge rounded-pill bg-success">+32%<span class="visually-hidden">Change from last month</span></span>
            </a>
        </li>
        <li class="nav-item">
            <!-- Toggle active class when filter is selected-->
            <a id="active" class="active nav-link text-white" aria-current="filter" href="#">
                <span class="h1">139</span><br>Active
            </a>
        </li>
        <li class="nav-item">
            <!-- Toggle active class when filter is selected -->
            <a id="ignored" class="nav-link text-white" href="#">
                <span class="h1">32</span><br>Ignored
            </a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col">
        <div class="card my-2 p-4">
            <h2 class="visually-hidden">Notices Over Time</h2>
            <canvas id="equalifiedByTime" class="" style="height: 300px"></canvas>
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
                                data: [110, 120, 130, 140, 150, 230],
                                borderColor: 'rgba(11, 101, 21, 1)',
                                backgroundColor: 'rgba(11, 101, 21, .6)',
                            }
                            // This is ignored because the ignored toggle is off.
                            // ,{
                            //     label: 'Ignored',
                            //     data: [0, 5, 10, 15, 20, 32],
                            //     borderColor: 'rgba(108, 117, 125, 1)',
                            //     backgroundColor: 'rgba(108, 117, 125, .6)',
                            // }
                            ,{
                                label: 'Active',
                                data: [90, 75, 60, 45, 30, 139],
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
<div class="row">
    <div class="col">
        <div class="card py-2 px-4 my-2 table-responsive">
            <h2 class="visually-hidden">Notices</h2>
            <!-- Only show notices with status that are toggled (e.g.- all notices, but ignored notices are shown now) -->
            <div class="mb-2">
                <div class="row border-bottom py-2" aria-hidden="true">
                    <strong class="col-4">
                        Message
                    </strong>
                    <strong class="col-4">
                        Code
                    </strong>
                    <strong class="col-2">
                        Status
                    </strong>
                    <strong class="col-2">
                        Occurances
                    </strong>
                </div>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Image lacks alt text
                    </span>
                    <span class="col-4" aria-label="code">
                        <code>&lt;img src="https://tulane.edu/logo.jgp"></code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="occurances">
                        38
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Image lacks alt text
                    </span>
                    <span class="col-4" aria-label="code">
                        <code>&lt;img src="https://tulane.edu/logo.jgp"></code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="occurances">
                        38
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Insufficient color contrast
                    </span>
                    <span class="col-4">
                        <code>&lt;p&gt;Sample text here...&lt;/p&gt;&lt;p&gt;Some more sample&lt;/p&gt;</code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="Occurances">
                        22
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Missing form labels
                    </span>
                    <span class="col-4">
                        <code>&lt;h2&gt;Another sample &lt;h3&gt;can be&lt;/h3&gt; put on th&lt;/h2&gt;...</code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="Occurances">
                        16
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Empty headings
                    </span>
                    <span class="col-4">
                        <code>Hello world&lt;br&gt;&lt;p&gt;Text goes here to demo a concre...</code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="Occurances">
                        12
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_notice&id=">
                    <span class="col-4">
                        Missing document language
                    </span>
                    <span class="col-4">
                        <code>&lt;p&gt;Sample text here...&lt;/p&gt;</code>
                    </span>
                    <span class="col-2" aria-label="Status">
                        Active
                    </span>
                    <span class="col-2" aria-label="Occurances">
                        4
                    </span>
                </a>
            </div>
            <div class="d-flex align-items-center">
                <p class="text-secondary fs-6 my-0 me-3">
                    1,239 Notices
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
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card py-2 px-4 my-2 ">
            <!-- Only show tags belonging to notices that are visible (e.g.- all notices, but ignored notices are shown now) -->
            <h2 class="visually-hidden">Tags</h2>
            <div class="mb-2">
                <div class="row border-bottom py-2" aria-hidden="true">
                    <strong class="col-7">
                        Tag
                    </strong>
                    <strong class="col-3">
                        Occurances
                    </strong>
                </div>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_tag&id=">
                    <span class="col-7">
                        WCAG 2.1.8
                    </span>
                    <span class="col-3" aria-label="Occurances">
                        129
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_tag&id=">
                    <span class="col-7">
                        WCAG 1.2.2
                    </span>
                    <span class="col-3" aria-label="Occurances">
                        108
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_tag&id=">
                    <span class="col-7">
                        WCAG 3.2.1
                    </span>
                    <span class="col-3" aria-label="Occurances">
                        39
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_tag&id=">
                    <span class="col-7">
                        WCAG 1.2.4
                    </span>
                    <span class="col-3" aria-label="Occurances">
                        37
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_tag&id=">
                    <span class="col-7">
                        WCAG 2.1.1
                    </span>
                    <span class="col-3" aria-label="Occurances">
                        30
                    </span>
                </a>
            </div>
            <div class="d-flex align-items-center">
                <p class="text-secondary fs-6 my-0 me-3">
                    34 Tags
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
    </div>
    <div class="col">
        <div class="card py-2 px-4 my-2 table-responsive">
            <!-- Only show pages that have notices that are visible (e.g.- all notices, but ignored notices are shown now) -->
            <h2 class="visually-hidden">Pages</h2>
            <div class="mb-2">
                <div class="row border-bottom py-2" aria-hidden="true">
                    <strong class="col-7">
                        URL
                    </strong>
                    <strong class="col-3">
                        Equalified
                    </strong>
                </div>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_page&id=">
                    <span class="col-7">
                        tulane.edu/home
                    </span>
                    <span class="col-3" aria-label="Equalified">
                        3%
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_page&id=">
                    <span class="col-7">
                        tulane.edu/visit
                    </span>
                    <span class="col-3" aria-label="Equalified">
                        13%
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_page&id=">
                    <span class="col-7">
                    tulane.edu/apply
                    </span>
                    <span class="col-3" aria-label="Equalified">
                       53%
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_page&id=">
                    <span class="col-7">
                    tulane.edu/events
                    </span>
                    <span class="col-3" aria-label="Equalified">
                        61%
                    </span>
                </a>
                <a class="row text-body py-2 border-bottom" href="index.php?view=single_page&id=">
                    <span class="col-7">
                        giving.tulane.edu/s/1586/Giving/	
                    </span>
                    <span class="col-3" aria-label="Equalified">
                        73%
                    </span>
                </a>
            </div>
            <div class="d-flex align-items-center">
                <p class="text-secondary fs-6 my-0 me-3">
                    1,200 Pages
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
    </div>
</div>