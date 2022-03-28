<section>
    <h1 class="mb-3 pb-4 border-bottom">All Integrations</h1>

    <div class="row row-cols-3 g-4 pb-4">

        <?php
        // Begin Integrations List
        $integrations = uploaded_integrations('integrations');
        foreach($integrations as $integration):
        ?>

        <div class="col">
            <div class="card position-relative">
                <img src="integrations/<?php echo $integration['uri'];?>/logo.jpg" class="card-img-top border-bottom" alt="Logo for Little Forrest">
                <div class="card-body">
                    <h2 class="h5 card-title">
                        Name
                    </h2>
                    <div class="h5 position-absolute top-0 end-0 h2 p-3">
                        Status
                    </div>
                    <p class="card-text">
                        Tagline
                    </p>
                </div>
            </div>
        </div>

        <?php
        // End Integrations List
        endforeach;
        ?>

    </div>
    <hr>
    <h2>
        Need another integration?
    </h2>
    <p>Request new integrations via <a href="https://github.com/bbertucc/equalify/issues" target="_blank">Equalify's GitHub Issues</a>.</p>
</section>