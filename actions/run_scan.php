<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script runs the scanner.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// The scan runs in the background.
shell_exec(
    $GLOBALS['PHP_PATH'].
    ' cli/scan.php > /dev/null 2>/dev/null &'
);