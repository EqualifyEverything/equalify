<?php
// Get URL parameters.
$uri = $_GET['uri'];
if(empty($uri))
    throw new Exception('You have not supplied a URI');

// Check if integration exists.
$integration = get_integration_meta('integrations/'.$uri.'/functions.php');
if(empty($integration) == 1)
    throw new Exception('There is no integration with the URI "'.$uri.'"');

// Set variables now to minimize chases of multiple queries.
$integration_fields = get_integration_fields($uri);
$settings = $integration_fields['settings'];

?>

<div class="mb-3 pb-3 border-bottom">

    <h1>
    
        <?php 
        // Name
        echo $integration['name'];
        ?>

    </h1>

</div>

<?php
// Meta settings.
if(!empty($settings['meta'])):
?>

<section id="meta_settings" class="mb-3 pb-3 border-bottom">
    <h2>
        Settings
    </h2>

    <form action="actions/update_meta.php" method="post" class="my-3">
        <input type="hidden" name="last_view" value="integration_settings&uri=<?php echo $uri;?>" />

        <?php
        // Get meta info
        $meta = get_meta($db);
        
        // Begin settings.
        $settings = $settings['meta'];
        foreach($settings as $setting):
            $name = $setting['name'];
            $label = $setting['label'];
            $type = $setting['type'];
        ?>

        <div class="mb-3">
            <label for="<?php echo $name?>_field" class="form-label">
                <?php echo $label;?>
            </label>
            <input 
                id="<?php echo $name?>_field" 
                name="<?php echo $name?>"
                type="<?php echo $type?>"
                value="<?php
                    // This requires a fallback if no key was entered.
                    if(!empty($meta->$name))
                        echo $meta->$name
                    ?>"
                class="form-control"
            >
        </div>

        <?php
        // End settings.
        endforeach;
        ?>



        <button class="btn btn-primary my-3" type="submit">Save Settings</button>
    </form>

</section>

<?php
// End meta settings.
endif;

// Begin page settings.
if(!empty($settings['page'])):
?>


<section id="page_settings" class="mb-3 pb-3 border-bottom">
    <h2>
        Page Settings
    </h2>

    <form action="actions/update_page.php" method="post" class="my-3">
    
        <?php
        // Settings
        ?>

        <button class="btn btn-primary my-3" type="submit">Save Settings</button>
    </form>

</section>

<?php
// End page settings.
endif;
?>