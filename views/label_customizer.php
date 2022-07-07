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
$name   = '';
$title = 'Untitled';
$integration = '';
$type   = '';
$source = '';

// We use this view to customize labels if a id is 
// provided, otherwise we create a new label.
if(!empty($_GET['name'])){
    $name = $_GET['name'];
    
    // Let's load in predefined variables for the label.
    $existing_label = unserialize(
        DataAccess::get_meta_value($name)
    );

    // Let's reformat the meta so we can use it in a
    // more understandable format.
    foreach($existing_label as $label) {
        if($label['name'] == 'title') 
            $title = $label['value'];
        if($label['name'] == 'integration') 
            $integration = $label['value'];
        if($label['name'] == 'type') 
            $type = $label['value'];
        if($label['name'] == 'source') 
            $source = $label['value'];
    }

}

?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>
            <?php
            // Lets add helper text, depending on if we're
            // editing the label.
            if(!empty($name)){
                echo 'Editing ';
            }else{
                echo 'New ';
            }
            ?>
            
            "<span id="labelName"><?php echo $title;?></span>" Label
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
            <select id="integrationSelect" class="form-select" name="inte">
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
                <label for="labelTitleInput" class="form-label fw-semibold">Label Name</label>
                <input type="text" id="labelTitleInput" class="form-control" value="<?php echo $title;?>" name="title" required>
            </div>
            
            <?php
            // New labels can't be deleted.
            if(!empty($name))
                echo '<a href="actions/delete_label.php?name='.$name.'" class="btn btn-outline-danger">Delete Label</a>';
            ?>

            <button type="submit" class="btn btn-primary" id="saveButton">Save Label</button>
            <input type="hidden" name="name" value="<?php echo $name;?>">
    </form>
</section>
<script>
// Change label title text as you type.
const labelName = document.getElementById('labelName');
const labelNameInput = document.getElementById('labelNameInput');
const changelabelName = function(e) {
    labelName.innerHTML = e.target.value;
}
labelNameInput.addEventListener('input', changelabelName);
labelNameInput.addEventListener('propertychange', changelabelName);

</script>