<?php
    $file_name = $_FILES['file']['name'];
    $tmp_name = $_files['file']['tmp_name'];
    $dile_up_name = time().$file_name;
    move_uploaded_file($tmp_name, "files/", $file_up_name);
?>
