<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the scan setting's view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
?>

<h1 class="visually-hidden">Equalify Settings</h1>
<div class="card bg-white p-4 my-2">
    <section>
        <h2>Properties</h2>
        <?php
        // Start properties query.
        $existing_properties = DataAccess::get_db_rows('properties', array(), 1, 10000)['content'];
        if(!empty($existing_properties)): foreach($existing_properties as $property):
        ?>
        
        <div class="border-bottom px-2 py-3">
            <p class="h5 m-0"> 
                
                <?php 
                // Property Name.
                echo $property->name;

                // Archived badge is helpful to show what properties
                // aren't being scanned.
                if($property->status == 'archived')
                    echo '<span class="badge text-bg-secondary ms-4" aria-label="status">Archived</span>';
                ?>

                <a class="icon-link float-end" style="--bs-link-hover-color-rgb: 25, 135, 84;" href="index.php?view=property_settings&id=<?php echo $property->id;?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                    </svg>
                    <span class="visually-hidden">Edit Property</span>
                </a>
            </p>
        </div>

        <?php
        // End properties query.
        endforeach; 
        else: echo '<p class="lead">No properties exist.</p>';
        endif;
        ?>

        <a class="btn btn-primary mt-3" href="index.php?view=property_settings">Add Property</a>
    </section>
    <section class="mt-4">
        <h2>Integrations</h2>
        <div class="row row-cols-4 g-4 pb-4">

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
                            // Generate Status Badge
                            if($status == 'Disabled'){
                                $badge_class = 'bg-secondary';
                                $badge_text = 'Disabled';
                            }elseif($status == 'Active'){
                                $badge_class = 'bg-success';
                                $badge_text = 'Active';
                            }
                            ?>
                            
                            <span class="badge <?php echo $badge_class;?>" aria-label="Integration Status"><?php echo $badge_text;?></span>
                        </div>
                        <p class="card-text">

                            <?php
                            // Description.
                            echo $meta['description'];
                            ?>

                        </p>

                        <?php
                        // Activation button.
                        if($status == 'Active')
                            echo '<button data-uri="'.$integration['uri'].'" class="btn btn-outline-danger integration-btn" aria-label="Disable Integration">Disable</button>';
                        if($status == 'Disabled')
                            echo '<button data-uri="'.$integration['uri'].'" class="btn btn-primary integration-btn" aria-label="Activate Integration">Activate</button>';    
                        ?>

                    </div>
                </div>
            </div>

            <?php
            // End integrations list.
            endforeach;
            ?>
            
            <div id="status" aria-live="assertive" class="visually-hidden"></div>
        </div>
        <h3 class="h5">
            Need another integration?
        </h3>
        <p>
            Request new integrations via <a href="https://github.com/bbertucc/equalify/issues" target="_blank">Equalify's GitHub repo</a>.
        </p>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.integration-btn');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const uri = this.getAttribute('data-uri');
            const isDisabled = this.classList.contains('btn-outline-danger');
            const newStatus = isDisabled ? 'Activate' : 'Disable';
            const newClass = isDisabled ? 'btn btn-primary integration-btn' : 'btn btn-outline-danger integration-btn';
            const newAriaLabel = isDisabled ? 'Activate Integration' : 'Disable Integration';
            const newBadgeClass = isDisabled ? 'badge bg-secondary' : 'badge bg-success';
            const newBadgeText = isDisabled ? 'Disabled' : 'Active';

            // Change button content and disable it
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading';
            this.disabled = true;

            // Update badge
            const badge = this.parentElement.querySelector('.badge');
            if (badge) {
                badge.className = newBadgeClass;
                badge.childNodes[0].nodeValue = newBadgeText + ' ';
            } else {
                console.error('Badge element not found!');
            }

            // AJAX request to the PHP file
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `actions/toggle_integration_status.php?uri=${encodeURIComponent(uri)}&old_status=${encodeURIComponent(isDisabled ? 'Active' : 'Disabled')}`, true);
            xhr.onreadystatechange = function () {
                if (this.readyState === 4) {
                    // Re-enable button, update text, class, and aria-label
                    button.disabled = false;
                    button.innerHTML = newStatus;
                    button.className = newClass;
                    button.setAttribute('aria-label', newAriaLabel);

                    // Update status for screen readers
                    document.getElementById('status').textContent = `Integration is now ${newStatus.toLowerCase()}.`;

                    if (this.status !== 200) {
                        console.error('Error:', this.responseText);
                    } else {
                        console.log('Success:', this.responseText);
                    }
                }
            };
            xhr.send();
        });
    });
});
</script>