<section>
    <h1 class="mb-3 pb-4 border-bottom">All Integrations</h1>

    <div class="row row-cols-3 g-4 pb-4">

        <?php
        // Get list of active integrations to use in the integrations list.
        $active_integrations = unserialize(DataAccess::get_meta_value('active_integrations'));

        // Begin Integrations List
        $integrations = uploaded_integrations('integrations');
        foreach($integrations as $integration):
            $meta = get_integration_meta('integrations/'.$integration['uri'].'/functions.php');

            // Set status.
            if(!empty($meta['status'])){
                $status = $meta['status'];
            }elseif (($key = array_search($integration['uri'], $active_integrations)) !== false) {
                $status = 'Active';
            }else{
                $status = 'Disabled';
            }
        ?>

        <div class="col">
            <div class="card position-relative">
                <img src="integrations/<?php echo $integration['uri'];?>/logo.jpg" class="card-img-top border-bottom" alt="Logo for Little Forest">
                <div class="card-body">
                    <h2 class="h5 card-title">

                        <?php
                        // Name.
                        echo $meta['name'];
                        ?>

                    </h2>
                    <div class="h5 position-absolute top-0 end-0 h2 p-3">
                        
                        <?php
                        // Badge.
                        the_integration_status_badge($status);
                        ?>
                        
                    </div>
                    <p class="card-text">

                        <?php
                        // Description.
                        echo $meta['description'];
                        ?>

                    </p>

                    <?php
                    // Show settings button if integration has settings
                    $integration_fields = get_integration_fields( $integration['uri'] );
                    if(!empty($integration_fields['settings']))
                        the_integration_settings_button($integration['uri'], $status);
                    ?>

                    <?php
                    // Activation button.
                    the_integration_activation_button($integration['uri'], $status);
                    ?>

                </div>
            </div>
        </div>

        <?php
        // End integrations list.
        endforeach;
        ?>

    </div>
    <h2 class="border-top mt-3 pt-4">
        Need another integration?
    </h2>
    <p>
        Request new integrations via <a href="https://github.com/bbertucc/equalify/issues" target="_blank">Equalify's GitHub repo</a>.
    </p>
</section>