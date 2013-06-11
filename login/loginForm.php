<?php

    include('../classes/updatecheck/updatecheck.php');
    include('../config.php');
    include('../classes/core.php');
        
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $output = Database::GetSingle('login', array('username' => $_POST['gebruikersnaam'], 'password' => $_POST['wachtwoord'] ), 'id');
        if ($output) {
            $_SESSION['login'] = 1;
            $_SESSION['producten'] = "";
            exit(header('Location: ../producten/products.php'));
        }
    } else  $output = TRUE;
  
    
    
?>

<html>
    <body>
        
        <?php if (!$output) echo 'Verkeerde gegevens'; ?>
        
        <form method="POST">
                
                Gebruikersnaam:<br> <input type="text" name="gebruikersnaam"><br>
                Wachtwoord:<br> <input type="password" name="wachtwoord"><br>
                <input type="submit" name="opslaan" value="Log in">
        </form>
    </body>
</html>
