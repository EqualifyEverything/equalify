<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the label customerizer view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Let's setup the variables that we're going to be using
// in this document.
$id   = '';
$name = 'Untitled';
$integration = '';
$type   = '';
$source = '';

// We use this view to customize labels if a id is 
// providded.
if(!empty($_GET['id'])){
    $id = $_GET['id'];
    
    // Let's load in predefined variables for the label.
    $existing_label = unserialize(
        DataAccess::get_meta_value('label_'.$id)
    );
    $name =  $existing_label['name'];
    $integration = $existing_label['integration'];
    $type   = $existing_label['type'];
    $source = $existing_label['source'];

}

?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>
            "<span id="labelName"><?php echo $name;?></span>" Label
        </h1>
    </div>
    <form action="actions/save_label.php" method="post">

        <?php
        // Show active integrations so we know what can possibly
        // be added to the label.
        $active_integrations = unserialize(
            DataAccess::get_meta_value(
                'active_integrations'
            )
        );

        // List active integrations.
        if(!empty($active_integrations)):
        ?>

        <div class="mb-3">
            <label for="integrationSelect" class="form-label fw-semibold">Integration</label>
            <select id="integrationSelect" class="form-select" name="integration_uri">
                <option value="" >Any</option>

                <?php
                // Display an option for each active 
                // integration.
                foreach($active_integrations as $option)
                {

                    // An integration may already be saved. 
                    if($option == $integration){
                        $selected_attribute = 'selected';
                    }else{
                        $selected_attribute = '';
                    }

                    // Now we can build the option.
                    echo '<option value="'.$option.'" '
                    .$selected_attribute.' >'
                    .ucwords(str_replace('_', ' ', $option))
                    .'</option>';

                }
                ?>

            </select>
        </div>

        <?php
        // End active integrations
        endif;
        ?>
            
        <div class="mb-3">
            <label for="typeSelect" class="form-label fw-semibold">Alert Type</label>
            <select id="typeSelect" class="form-select" name="type">
                <option value="">Any</option>
                
                <?php 
                // Set types as array so we can simplify the logic
                // when building the option html.
                $type_options = array(
                    'error', 'warning', 'notice'
                );
            
                // Build options.
                foreach ($type_options as $option){

                    // A type may already be saved. 
                    if($option == $type){
                        $selected_attribute = 'selected';
                    }else{
                        $selected_attribute = '';
                    }
                    
                    // Build option
                    echo '<option value="'.$option.'" '
                    .$selected_attribute.' >'
                    .ucwords($option).'</option>';

                }
                ?>
        
            </select>
        </div>
        <div class="mb-3">
            <label for="sourceSelect" class="form-label fw-semibold">Alert Source</label>
            <select id="sourceSelect" class="form-select" name="source">
                <option value="">Any</option>
                
                <?php 

                // Set sources as array so we can simplify
                // the logic to build the option html.
                $source_options = array(
                    'page', 'system'
                );
                
                // Build options.
                foreach ($source_options as $option){

                    // A source may already be saved. 
                    if($option == $source){
                        $selected_attribute = 'selected';
                    }else{
                        $selected_attribute = '';
                    }

                    // Build option.
                    echo '<option value="'.$option.'" '
                    .$selected_attribute.'>'
                    .ucwords($option).'</option>';

                }
                ?>

                </select>
            </div>
            <hr>
            <div class="mb-3">
                <label for="labelNameInput" class="form-label fw-semibold">Tab Name</label>
                <input type="text" id="labelNameInput" class="form-control" aria-describedby="metaFilter1Help" value="<?php echo $name;?>" name="name" required>
            </div>
            
            <?php
            // New labels can't be deleted
            if(!empty($id))
                echo '<a href="actions/delete_label.php?id='.$id.'" class="btn btn-outline-danger">Delete Tab</a>';
            ?>

            <button type="submit" class="btn btn-primary" id="saveButton">Save Label</button>
            <input type="hidden" name="id" value="<?php echo $id;?>" id="alertTabID">
    </form>
</section>
<script>
// Change label name text as you type.
const labelName = document.getElementById('labelName');
const labelNameInput = document.getElementById('labelNameInput');
const changelabelName = function(e) {
    labelName.innerHTML = e.target.value;
}
labelNameInput.addEventListener('input', changelabelName);
labelNameInput.addEventListener('propertychange', changelabelName);

</script>