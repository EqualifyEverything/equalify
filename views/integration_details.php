<?php
// Set Property ID with optional fallboack.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if($id == false)
    throw new Exception('Format of integration ID "'.$id.'" is invalid');

// Set Variabls
$integration = get_integration($db, $id);
$account_info = get_account($db, USER_ID);

if(empty($integration) == 1)
    throw new Exception('There is no record of integration "'.$id.'" does not exist');
?>

<div class="mb-3 pb-4 border-bottom">

    <h1>
    
        <?php 
        // Name
        echo $integration->name;
        ?>

        <span class="float-end">
        
        <?php
        // Badge
        the_integration_status($db, $integration);
        ?>

        </span>
    </h1>

</div>
<section id="settings" class="mb-3 pb-4">
    <h2>
        Settings
    </h2>

    <form action="actions/update_account.php" method="post" class="my-3">
    
        <?php
        // Get Settings Related to Integration
        require_once('integrations/'.$integration->uri.'/view.php');
        ?>

        <input name="last_view" type="hidden" value="<?php echo 'integration_details&id='.$id;?>">
        <button class="btn btn-primary my-3" type="submit">Save Settings</button>
    </form>

</section>