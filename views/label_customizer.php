<pre>
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
$status = '';
$type   = '';
$guidelines   = '';
$tags   = '';

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
        if($label['name'] == 'type') 
            $type = $label['value'];
        if($label['name'] == 'status') 
            $status = $label['value'];
        if($label['name'] == 'guideline'){
            $raw_guidelines = $label['value'];
            $guidelines = '';
            $count = 0;
            foreach($raw_guidelines as $guideline){
                $guidelines.= $guideline['value'];
                $count++;
                if($count != count($raw_guidelines))
                    $guidelines.= ', ';
            }
        } 
        if($label['name'] == 'tag'){
            $raw_tags = $label['value'];
            $tags = '';
            $count = 0;
            foreach($raw_tags as $tag){
                $tags.= $tag['value'];
                $count++;
                if($count != count($raw_tags))
                    $tags.= ', ';
            }
        } 
    }

}
?>

</pre>

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
        <div class="mb-3">
            <label for="statuSelect" class="form-label fw-semibold">Status</label>
            <select id="typeSelect" class="form-select" name="status">
                <option value="">Any</option>

                <?php 
                // Set status as array so we can simplify
                // the logic to build the option html.
                $status_options = array(
                    'active', 'ignored', 'equalified'
                );
                
                // Build options.
                foreach ($status_options as $option){

                    // A source may already be saved. 
                    if($option == $status){
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
            <label for="guidelinesInput" class="form-label fw-semibold">WCAG Guidelines</label>            
            <input type="text" id="guidelinesInput" class="form-control" value="<?php echo $guidelines;?>" name="guidelines" aria-describedby="guidelinesHelp" placeholder="0.3, 1.1, 3.3">
            <div id="guidelinesHelp" class="form-text">Use a <a href="https://www.w3.org/TR/WCAG21/" target="_blank">WCAG 2.1</a> guideline number. Multiple guidlines must be seperated by commas.</div>
        </div>
        <div class="mb-3">
            <label for="tagsInput" class="form-label fw-semibold">HTML Tags</label>
            <input type="text" id="tagsInput" class="form-control" value="<?php echo $tags;?>" name="tags" aria-describedby="tagsHelp" placeholder="a, strong, div">
            <div id="tagsHelp" class="form-text">Add any <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element" target="_blank">HTML tag</a>. Multiple tags must be seperated by commas.</div>
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