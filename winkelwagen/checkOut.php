<?php

include ('../classes/updatecheck/updatecheck.php');
include ('../config.php');

include ('../classes/core.php');

$single = explode(',',$_SESSION['producten']);

global $totaal;

foreach($single as $value)
{

    $result = Database::GetArray('producten', $select = '*', array('product_code' => $value));
    
    foreach($result as $value){
        echo $value['product_code'];
        echo " ";
        echo $value['prijs'];
        echo "</br>";
        
        $totaal = $totaal + $value['prijs'];
    }
    
}

echo "Prijs : " . $totaal;
?>

