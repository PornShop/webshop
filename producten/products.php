<?php

include('../classes/updatecheck/updatecheck.php');
include ('../config.php');
include ('../classes/core.php');

if(loggedin())
{
try {

$result = Database::GetArray('producten', array("naam","product_code"));

} catch (Exception $e) {
    echo $e->getMessage();
}    
}
else{
   die("U Bent niet ingelogd");
}


?>

<html>
     
    <head>
        <link rel="stylesheet" type="text/css" href="../style.css">        
        
<script type="text/javascript">
function add(code)
{
alert(code);
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
    document.getElementById("result").innerHTML=xmlhttp.responseText;
    
    }
  }
xmlhttp.open("GET","addCart.php?code="+code,true);
xmlhttp.send();
}

function test()
{
    alert("test");
}
        
    </script>
    </head>
    
    <body>
        <table id="tableProducten">
            <th>Code</th>
            <th>Product</th>
            <th>Voorraad</th>
            
                <?php
                    foreach ($result as $row) {
                        
                    $voorraad = Database::GetSingle('voorraad', array('product_code' => $row["product_code"]) , 'voorraad');
                    $productcode = $row["product_code"];
                    
                    echo "<tr>";
                    
                    echo "<td>{$row["product_code"]}</td>";
                    echo "<td>{$row["naam"]}</td>";
                    echo "<td>{$voorraad["voorraad"]}</td>";
                    echo "<td><input type='button' onClick='add(\"$productcode\")' value='Toevoegen'></button></td>";
                   
                    echo "</tr>";
                    
                    }
                ?>
         
            
        </table>
        
        <div id="result"></div>
    
        <a href="../winkelwagen/checkOut.php">Checkout</a>
        
    </body>
    
    
</html>
