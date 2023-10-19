<div id="content" class="container-xxl">
    <aside class="pt-4">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="#properties" class="nav-link active" aria-current="page">
                    Properties
                </a>
            </li>
            <li>
                <a href="#integrations" class="nav-link link-body-emphasis">
                    Integrations
                </a>
            </li>
        </ul>
    </aside>
    <main class="container">
        <section id="properties">
            <h1>Equalify Settings</h1>
            <h2 class="mt-5">Properties</h2>
            <div class="border-bottom px-2 py-3">
                <p class="h5 m-0">Another Property
                    <a class="icon-link float-end" style="--bs-link-hover-color-rgb: 25, 135, 84;" href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                        </svg>
                        <span class="visually-hidden">Edit Property</span>
                    </a>
                </p>
            </div>
            <div class="border-bottom px-2 py-3">
                <p class="h5 m-0">Another Property
                    <a class="icon-link float-end" style="--bs-link-hover-color-rgb: 25, 135, 84;" href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                        </svg>
                        <span class="visually-hidden">Edit Property</span>
                    </a>
                </p>
            </div>

        </section>
        <section id="integrations">
            <h2 class="mt-5 mb-4">Integrations</h2>
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
                        <img src="integrations/<?php echo $integration['uri'];?>/logo.jpg" class="card-img-top border-bottom" alt="Logo for <?php echo $meta['name'];?>">
                        <div class="card-body">
                            <h3 class="h5 card-title">

                                <?php
                                // Name.
                                echo $meta['name'];
                                ?>

                            </h3>
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
            <h3 class="h5">
                Need another integration?
            </h3>
            <p>
                Request new integrations via <a href="https://github.com/bbertucc/equalify/issues" target="_blank">Equalify's GitHub repo</a>.
            </p>
        </section>
    </main>
</div>