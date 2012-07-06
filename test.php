<form method="post" action="">
    <input type="checkbox" name="id1">
    <input type="checkbox" name="id2">
    <input type="submit">
</form>
<?php
if ($_POST) {
    var_dump($_POST);
}
?>