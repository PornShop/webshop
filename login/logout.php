<?php
include '../classes/core.php';
session_destroy();
exit(header('Location: login.php'));
?>
