<?php
echo "File di root bisa diakses!<br>";
echo "Cek modules: ";
if (is_dir('modules')) {
    echo "Folder modules ADA.<br>";
    if (is_dir('modules/admin')) {
        echo "Folder modules/admin ADA.<br>";
        if (file_exists('modules/admin/user_management.php')) {
            echo "File user_management.php ADA.";
        } else {
            echo "File user_management.php TIDAK ADA.";
        }
    } else {
        echo "Folder modules/admin TIDAK ADA.";
    }
} else {
    echo "Folder modules TIDAK ADA.";
}
?>