<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Bootstrap application requirements on install.
 * 
**********************************************************/

require_once './config.php';

if ($GLOBALS['a11ywatch_uri'] == "http://localhost:3280") {
    $npm_exist = shell_exec("npm --help");

    // install npm on machine automatically
    if (!$npm_exist) {
        throw new Exception('Node installation is required for the backend.');
        exit(1);
    }
    // if not installed start sidecar
    $install_a11ywatch = file_exists("./node_modules/@a11ywatch/a11ywatch");
    // install a11ywatch
    if (!$install_a11ywatch) {
        $npm_exist = shell_exec("npm i @a11ywatch/a11ywatch --save");
    }
    system("NODE_ENV=production node ./node_modules/@a11ywatch/a11ywatch/server.js");
}