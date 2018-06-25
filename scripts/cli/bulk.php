<?php

require_once('../../include/config.php');
require_once(BASE . '/include/db.php');
global $dbhr, $dbhm;
require_once(BASE . '/include/utils.php');
require_once(BASE . '/include/misc/Entity.php');
require_once(BASE . '/include/misc/Image.php');
require_once(BASE . '/include/Sign.php');

$opts = getopt('f:');

if (count($opts) < 1) {
    echo "Usage: php bulk.php -f <folder>\n";
} else {
    $folder = $opts['f'];
    if ($handle = opendir($folder)) {
        while (false !== ($file = readdir($handle))) {
            error_log("...$file");
            $fn = "$folder/$file";
            $data = file_get_contents($fn);

            $a = new Sign($dbhr, $dbhm, NULL);
            $id = $a->create(NULL, NULL, $data);
            if ($id) {
                error_log("...$id");
            }
        }
        closedir($handle);
    } else {
        error_log("Couldn't open $fn");
    }
}

