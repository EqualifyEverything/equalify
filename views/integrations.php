<section>
    <h1 class="mb-3 pb-4 border-bottom">All Integrations</h1>

    <div class="row row-cols-3 g-4 pb-4">

        <?php
        // Begin Integrations List
        $integrations = get_integrations($db);
        foreach ($integrations as $integration):
        ?>

        <div class="col">
            <div class="card position-relative">
                <img src="images/logo-<?php echo $integration->uri;?>.jpeg" class="card-img-top border-bottom" alt="Logo for Little Forrest">
                <div class="card-body">
                    <h2 class="h5 card-title">
                        <?php echo $integration->name;?>
                    </h2>
                    <div class="h5 position-absolute top-0 end-0 h2 p-3">

                        <?php
                        // Status Badge
                        the_integration_status($integration->status);
                        ?>

                    </div>
                    <p class="card-text">

                        <?php echo $integration->tagline;?>

                    </p>
                    <a href="?view=integration_details&id=<?php echo $integration->id;?>" class="btn btn-primary <?php if($integration->status == 'planned') echo 'disabled';?>">View Settings</a>
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
    <p>Request new integrations via <a href="https://github.com/bbertucc/equalify/issues" target="_blank">Equalify's GitHub Issues</a>. New integrations are regularly added!</p>
</section>