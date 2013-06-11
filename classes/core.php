<?php
session_start();

function loggedin()
{
    if(isset($_SESSION['login']) && $_SESSION['login'] == 1)
    {
        return true;
    }
    else{
        return false;
    }
}
?>
