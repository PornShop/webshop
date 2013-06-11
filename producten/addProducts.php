<?php
    include('../classes/updatecheck/updatecheck.php');
    include('../config.php');
    include('../classes/core.php');
    
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
    Database::Insert('producten',array ('naam' => $_POST['product'],'product_type'=> $_POST['product_type'], 'product_code' => $_POST['product_code'], 'prijs' => $_POST['prijs'] )); 
    Database::Insert('voorraad', array ('product_code' => $_POST['product_code'], 'voorraad' => $_POST['voorraad']));
    }  else {
$output = TRUE;        
}

?>
<html>

    <a href="../winkelwagen/checkOut.php">Checkout</a>

    <body>
                <form method="POST">
                
                Productnaam:<br> <input type="text" name="product"><br>
                Product code:<br> <input type="text" name="product_code"><br>
                Product type:<br> <input type="text" name="product_type"><br>
                Prijs:<br> <input type="text" name="prijs"><br>
                Voorraad:<br><select name="voorraad" style="width: 155px">
                    
                    
  <option value="1">1</option>
  <option value="2">2</option>
  <option value="3">3</option>
  <option value="4">4</option>
  <option value="5">5</option>
  <option value="6">6</option>
  <option value="7">7</option>
  <option value="8">8</option>
  <option value="9">9</option>
  <option value="10">10</option>
  <option value="11">11</option>
  <option value="12">12</option>
  <option value="13">13</option>
  <option value="14">14</option>
  <option value="15">15</option>
  <option value="16">16</option>
  <option value="17">17</option>
  <option value="18">18</option>
  <option value="19">19</option>
  <option value="20">20</option>
</select>
                <br>
                <input type="submit" name="opslaan" value="voeg toe" style="width: 155px">
                
                </form>
    </body>
</html>