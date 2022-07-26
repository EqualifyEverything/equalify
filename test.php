<pre>

<?php
// For testing purposes.
$sites_output = array(
    'https://edupack.dev'
);

// We'll use the process alert helper
require_once('helpers/process_integrations.php');
require_once('helpers/process_alerts.php');

process_alerts(
    process_integrations($sites_output)
);

die;
?>


</pre>