<?PHP
   // Übergebene Variablen einlesen..
   if (isset($HTTP_GET_VARS['info_pwd'])) $info_pwd = $HTTP_GET_VARS['info_pwd'];       // Passwort für den Bereich
	 
	 // Passwort prüfen...
	 $pwd_ok = ($info_pwd == $real_info_pwd);
?>



<h2>FoodCoop Kreuzberg-Neukölln</h2>

  <?PHP
	 
	    // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
			if (!isset($info_pwd) || !$pwd_ok) {
	?>
				 <form action="index.php">
				    <input type="hidden" name="area" value="info">
				    <b>Bitte Zungangspasswort angeben:</b><br>
						<input type="password" size="12" name="info_pwd"><input type="submit" value="ok">						
				 </form>
	<?PHP
			} else	{
  ?>

<h3>=> Gruppenübersicht <=</h3>
<BR>
<img src="adrliste.jpg">

<?PHP
    }
?>
