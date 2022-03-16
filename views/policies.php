<h1>All Policies</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">Policies are updated.</div>'
?>

<table class="table">
    <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Last Tested</th>
            <th scope="col">Next Test</th>
            <th scope="col"></th>
        </tr>
    </thead>
    <tbody>

    <?php
    $records = get_all_policies($db);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>
        <tr>
            <td><?php echo $record->name; ?></td>
            <td><?php echo $record->tested; ?></td>
            <td>

            <?php 
            // Display Next Test time
            if($record->frequency == 'once'){
              echo 'N/A';
            }elseif($record->frequency == 'daily'){
              $next_test = strtotime($record->tested);
              $next_test = strtotime("+1 day", $next_test);
              echo date('Y-m-d H:i:s', $next_test);
            }elseif($record->frequency == 'weekly'){
              $next_test = strtotime($record->tested);
              $next_test = strtotime("+7 day", $next_test);
              echo date('Y-m-d H:i:s', $next_test);
            }elseif($record->frequency == 'monthly'){
              $next_test = strtotime($record->tested);
              $next_test = strtotime("+1 month", $next_test);
              echo date('Y-m-d H:i:s', $next_test);
            }elseif($record->frequency == 'annually'){
              $next_test = strtotime($record->tested);
              $next_test = strtotime("+1 year", $next_test);
              echo date('Y-m-d H:i:s', $next_test);
            }else{
              throw new Exception('No frequency is recordered for policy with the ID: '.$record->id);
            }
            ?>

            </td>
            <td><a href="?view=policy_details&id=<?php echo $record->id;?>">Edit Policy</a></td>
        </tr>

    <?php 
        endforeach;
    else:
    ?>

        <tr>
            <td colspan="4">No policies are added.</td>
        </tr>

    <?php 
    endif;
    ?>

    </tbody>
</table>
<a href="?view=policy_details" class="btn btn-primary">New Policy</a>