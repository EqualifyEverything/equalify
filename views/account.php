<div class="mb-3 pb-4 border-bottom">
    <h1>Account Settings</h1>
</div>

<?php
// Set Variables
$account_info = get_account($db, USER_ID);
$usage = $account_info->usage;
?>

<form action="actions/update_account.php" method="post">
    <button class="btn btn-primary my-3" type="submit">Save Settings</button>
</form>