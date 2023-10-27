<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="row my-4">
    <div class="col">
        <h1>Tulane Accessibility</h1>
    </div>
    <div class="col text-end">
        <!-- If any updates have been done, show Save Updates button -->
        <a href="index.php?view=report_setting" class="btn btn-primary">Save Updates</a>
        <!-- Search button toggles search bar -->
        <button class="btn btn-outline-secondary">
            <span class="visually-hidden">Toggle Search</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
        </button>
        <button type="button" class="btn btn-outline-secondary"> 
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
            Jul 1 - December 31, 2023
        </button>
        <a href="index.php?view=single_report_settings" class="btn btn-secondary">Report Settings</a>
    </div>
</div>
<div class="my-2 rounded-3 bg-secondary text-white text-center p-3">
    <div class="row">
        <div class="col">
            <span class="h1">99</span><br>Equalified
        </div>
        <div class="col">
            <span class="h1">139</span><br>Active
        </div>
        <div class="col">
            <span class="h1">3</span><br>Ignored
        </div>
        <div class="col">
            <span class="h1">0</span><br>Goals Completed
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card my-2 p-4">
            <canvas id="equalifiedByTime" class="my-4" style="height: 300px"></canvas>
            <script>
            document.addEventListener('DOMContentLoaded', (event) => {
            var ctx = document.getElementById('equalifiedByTime').getContext('2d');
            var equalifiedByTime = new Chart(ctx, {
                type: 'line',
                data: {
                labels: ["Jul '23", "Aug '23", "Sep '23", "Oct '23", "Nov '23", "Dec '23"],
                datasets: [{
                    label: 'Ignored',
                    data: [1, 22, 12, 32, 2, 3],
                    borderColor: 'rgba(108, 117, 125, 1)',
                    backgroundColor: 'rgba(108, 117, 125, .5)'
                },{
                    label: 'Equalified',
                    data: [11, 22, 44, 66, 88, 99],
                    borderColor: 'rgba(24, 97, 33, 1)',
                    backgroundColor: 'rgba(24, 97, 33, .5)'
                },{
                    label: 'Active',
                    data: [293, 287, 233, 223, 203, 139],
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, .5)'
                }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
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
        <div class="card p-4 my-2 table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                    <th scope="col">Message</th>
                    <th scope="col">Code</th>
                    <th scope="col">Status</th>
                    <th scope="col">Weight</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Image lacks alt text</td>
                        <td class="text-truncate"><code>&lt;img src="https://tulane.edu/logo.jgp"></code></td>
                        <td>Active</td>
                        <td>38</td>
                    </tr>
                    <tr>
                        <td>Insufficient color contrast</td>
                        <td class="text-truncate"><code>&lt;p&gt;Sample text here...&lt;/p&gt;&lt;p&gt;Some more sample&lt;/p&gt;</code></td>
                        <td>Active</td>
                        <td>22</td>
                    </tr>
                    <tr>
                        <td>Missing form labels</td>
                        <td><code>&lt;h2&gt;Another sample &lt;h3&gt;can be&lt;/h3&gt; put on th&lt;/h2&gt;</code>...</td>
                        <td>Active</td>
                        <td>16</td>
                    </tr>
                    <tr>
                        <td>Empty headings</td>
                        <td><code>Hello world&lt;br&gt;&lt;p&gt;Text goes here to demo a concre</code>...</td>
                        <td>Active</td>
                        <td>12</td>
                    </tr>
                    <tr>
                        <td>Missing document language</td>
                        <td><code>&lt;p&gt;Sample text here...&lt;/p&gt;</code></td>
                        <td>Active</td>
                        <td>4</td>
                    </tr>
                </tbody>
            </table>
            <!-- Toggle buttons only if there are over 5 items, and toggle disabled if no prev/next items -->
            <div class="btn-group d-inline">
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
<div class="row">
    <div class="col">
        <div class="card p-4 my-2 ">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Tag</th>
                        <th scope="col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WCAG 1.2.2</td>
                        <td>123</td>
                    </tr>
                    <tr>
                        <td>WCAG 2.2.2</td>
                        <td>119</td>
                    </tr>
                    <tr>
                        <td>WCAG 1.1.2</td>
                        <td>112</td>
                    </tr>
                    <tr>
                        <td>WCAG 2.1.2</td>
                        <td>88</td>
                    </tr>
                    <tr>
                        <td>WCAG 2.1.2</td>
                        <td>73</td>
                    </tr>
                </tbody>
            </table>
            <!-- Toggle buttons only if there are over 5 items, and toggle disabled if no prev/next items -->
            <div class="btn-group d-inline">
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
    <div class="col">
        <div class="card p-4 my-2 table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">URL</th>
                        <th scope="col">Equalified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>tulane.edu/home</td>
                        <td>3%</td>
                    </tr>
                    <tr>
                        <td>tulane.edu/visit</td>
                        <td>13%</td>
                    </tr>
                    <tr>
                        <td>tulane.edu/events</td>
                        <td>48%</td>
                    </tr>
                    <tr>
                        <td>tulane.edu/apply</td>
                        <td>58%</td>
                    </tr>
                    <tr>
                        <td>giving.tulane.edu/s/1586/Giving/</td>
                        <td>78%</td>
                    </tr>
                </tbody>
            </table>
            <!-- Toggle buttons only if there are over 5 items, and toggle disabled if no prev/next items -->
            <div class="btn-group d-inline">
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