<div class="container">
    <h1 class="display-5 my-4">
        Equalify Account
    </h1>
    <div class="card bg-white p-4 my-2">
        <form action="" method="post" id="site_form">
            <div class="row mb-4">
                <div class="col">
                  <label for="user_email" class="form-label h4">Email Address</label>
                  <input id="user_email"  name="user_email" type="email" value="<?php echo $user_email;?>" class="form-control form-control-lg" disabled required>
                </div>
                <div class="col">
                  <label for="user_account" class="form-label h4">Active Account</label>
                  <select id="user_account" class="form-select form-select-lg mb-3" aria-label="Active Account" disabled>

                    <?php 
                    foreach ($session->user['equalify_databases'] as $key=>$item){
                        if($key == 0){
                            echo '<option selected>'; // highlight the active (hardcoded) database
                        }else{
                            echo '<option value="' . $item. '">';
                        }
                        echo $item;
                        echo '</option>';
                    }
                    ?>

                  </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" id="submit" class="btn btn-lg btn-primary">
                    Update Account
                </button>
                <button type="button" id="delete_property" class="btn btn-lg btn-danger" data-bs-toggle="modal" data-bs-target="#deletionModal" disabled>
                    Delete Account
                </button>                
            </div>
        </form> 
    </div>
</div>
<div class="modal fade" id="deletionModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="modalLabel">Are you sure you want to delete the property?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Deleting an account will remove all data associated with the account. You cannot undo a deletion.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="actions/delete_property.php" class="btn btn-danger">Yes, Delete Account</a>
      </div>
    </div>
  </div>
</div>

