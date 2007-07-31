<?PHP
	 // $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
  // Verbindung zur Datenbank herstellen
  require_once('../code/config.php');
  require_once("$foodsoftpath/code/err_functions.php");
  require_once("$foodsoftpath/code/login.php");
  need_http_var('lieferanten_id');

  $msg = '';
  $problems = '';

  $result = mysql_query("SELECT * FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id))
    or $problems = $problems . "<div class='warn'>Konnte Lieferantendaten nicht laden: "
                   . mysql_error() . "</div>";
  $row = mysql_fetch_array($result)
    or $problems = $problems . "<div class='warn'>Konnte Lieferantendaten nicht laden: "
                   . mysql_error() . "</div>";
	 
  $title = "Lieferantendetails";
  $subtitle = "Lieferantendetails";
  require_once("$foodsoftpath/windows/head.php");

  echo "
			<table border='2' style='width:580px;'>
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td>{$row['name']}</td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td>{$row['adresse']}</td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td>{$row['ansprechpartner']}</td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td>{$row['telefon']}</td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td>{$row['fax']}</td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td>{$row['mail']}</td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td>{$row['liefertage']}</td>
				 </tr>				 
			   <tr>
				    <td><b>Bestellmodalit&auml;ten</b></td>
						<td>{$row['bestellmodalitaeten']}</td>
				 </tr>
			   
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td>{$row['kundennummer']}</td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td>
  ";
  if( $row['url'] )
    echo "<a target='_new' href='{$row['url']}'>{$row['url']}</a>";
  else
    echo "-";
  echo "
           </td>
			 	 </tr>	
			</table>
			<br>
  ";
  if( $row['sonstiges'] )
    echo 'Sonstige Infos: <br>' . $row['sonstiges'];

?>

</body>
</html>
