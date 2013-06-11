<?php
$database = new Database('mysql', 'webshopUser', 'ditiseenwachtwoord', 'webshop', '82.157.27.52:3306');
if (!$database->GetConnectStatus()) {
    exit('Geen database connectie.');
}

?>
