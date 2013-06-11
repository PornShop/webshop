<?php
require '../classes/core.php';
require '../classes/updatecheck/updatecheck.php';
require '../config.php';

if(loggedin())
{
    echo "U bent ingelogd , click <a href='logout.php'>hier</a> om uit te loggen.";
}
else{
    
    exit(header('Location: loginForm.php'));
}
?>
