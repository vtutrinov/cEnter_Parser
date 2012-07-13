<?php
    echo CHtml::form("", "post", array("enctype" => "multipart/form-data", "class" => "form-horizontal"));
    ?>
<fieldset>
    <div class="control-group">
        <label class="control-label">Прайс</label>
        <div class="controls">
            <?php
            echo CHtml::fileField("price", "", array("class" => "input-xlarge"));
            ?>
        </div>
    </div>    
    <div class="form-actions">
        <?php
        echo CHtml::submitButton("Load", array("class" => "btn btn-primary"));
        ?>
    </div>
</fieldset>
    <?php
    echo CHtml::endForm();
?>
