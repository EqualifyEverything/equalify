<div class="mb-3 pb-4 border-bottom">
    <h1>Account Settings</h1>
</div>

<?php
// Set Variables
$account_info = get_account($db, USER_ID);
$credits = $account_info->credits;

?>

<form action="actions/update_account.php" method="post">
    <button class="btn btn-primary my-3" type="submit">Save Settings</button>
</form>
<hr>
<p><?php echo $credits;?> Credits Remain - <a href="mailto:blake@edupackd.dev">Request More Credits</a>