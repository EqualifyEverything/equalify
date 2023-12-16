
<h1 class="visually-hidden">Equalify Account</h1>
<section class="my-2">
    <h2 class="my-4">Account Information</h2>
    <div class="row g-4 gx-4">

        <div class="card" style="width: 32rem;">
        <div class="card-body">
            <h5 class="card-title"><?php echo $user_name; ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?php echo $user_title; ?></h6>
            <table class="table">
            <tr>
                <th scope="row">Nickname</th>
                <td><?php echo $user_nickname; ?></td>
            </tr>
            <tr>
                <th scope="row">Email</th>
                <td><?php echo $user_email; ?></td>
            </tr>
            <tr>
                <th scope="row">Last Updated</th>
                <td><?php echo $user_last_updated; ?></td>
            </tr>
            <tr>
                <th scope="row">Equalify Databases</th>
                <td><?php 
                foreach ($session->user['equalify_databases'] as $key=>$item){
                    if($key == 0){
                        '<span class="badge bg-primary">'; // highlight the active (hardcoded) database
                    }else{
                    echo '<span class="badge bg-light text-dark">';
                    }
                    echo $item;
                    echo '</span>';
                }
                ?></td>
            </tr>
            </table>
        </div>
        </div>
    </div>
</section>