<?php
$zip = new ZipArchive;
if ($zip->open('H5power.zip') === TRUE) {
    $zip->extractTo(dirname(__FILE__));
    $zip->close();
    echo 'ok';
} else {
    echo 'failed';
}
?>