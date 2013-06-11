<?php
    include('../classes/updatecheck/updatecheck.php');
    include('../config.php');
    
        
    
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if($_POST['wachtwoord'] == $_POST['wachtwoord2'] )
    {
         Database::Insert('login', array('username' => $_POST['gebruikersnaam'], 'password' => $_POST['wachtwoord']));
         echo 'gebruiker is toegevoegd';
         exit(header('Location: login.php'));
        }
    } else {
        echo 'wachtwoord is niet het zelfde';
    }
    
    
    
    

?>
<html>
    <body>
        
       
        
        <form method="POST">
                
                Gebruikersnaam:<br> <input type="text" name="gebruikersnaam"><br>
                Wachtwoord:<br> <input type="password" name="wachtwoord"><br>
                Herhaal wachtwoord:<br> <input type="password" name="wachtwoord2"><br>
                <input type="submit" name="opslaan" value="Registreer">
        </form>
    </body>
</html>