<?php
include('../classes/updatecheck/updatecheck.php');
include ('../config.php');
include ('../classes/core.php');

$_SESSION['producten'] .= strval($_GET['code']) . ",";
echo $_SESSION['producten'];
?>
