<h2>All Alerts</h2>
<table class="table">
  <thead>
    <tr>
        <th scope="col">Time</th>
        <th scope="col">Site</th>
        <th scope="col">Policy</th>
    </tr>
  </thead>

  <?php
  // Begin Alerts
  $records = get_all_alerts($db);
  if(count($records) > 0 ): foreach($records as $record):    
  ?>

  <tr>
      <td><?php echo $record->time;?></td>
      <td>
        <a href="?view=site_details&id=<?php echo $record->site_id;?>">
          <?php echo get_site_url($db, $record->site_id);?>
        </a>
      </td>
      <td>
        <a href="?view=policy_details&id=<?php echo $record->policy_id;?>">
          <?php echo get_policy_name($db, $record->policy_id);?>
        </a>
      </td>
  </tr>

  <?php 
  // Fallback
  endforeach; else:
  ?>

  <tr>
      <td colspan="3">No alerts found.</td>
  </tr>

  <?php 
  // End Alerts
  endif;
  ?>

</table>
