
<h1>Mein Konto</h1>
<?PHP
   //error_reporting(E_ALL); // alle Fehler anzeigen
   require_once('code/zuordnen.php');
   
/*   // Übergebene Variablen einlesen...
 *   if (isset($HTTP_GET_VARS['gruppen_id'])) $gruppen_id = $HTTP_GET_VARS['gruppen_id'];       // Passwort für den Bereich
 *	 if (isset($HTTP_GET_VARS['gruppen_pwd'])) $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];       // Passwort für den Bereich
 *		 
 *	 
 *	 // Passwort prüfen, Bestellgrupendaten einlesen...
 *	 if (isset($gruppen_id) && isset($gruppen_pwd) && $gruppen_id != "") 
 *	 {
 *      $result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
 *	    $bestellgruppen_row = mysql_fetch_array($result);
 *			
 *			$pwd_ok = ($bestellgruppen_row['passwort'] == crypt($gruppen_pwd,35464));
 *			
 *			
 *	 }
 *	 
 *
 *	 
 *	    // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
 *			if (!isset($gruppen_pwd) || !$pwd_ok) {
 *	?>
 *	
 *	 <form action="index.php">
 *				    <input type="hidden" name="area" value="meinkonto">
 *						
 *						<table class="menu">
 *						   <tr class="tableHeader1">
 *							    <th colspan="2" >Bitte einloggen</th>
 *							 </tr>
 *						   <tr>
 *						      <td>Bestellgruppenname:</td>
 *						      <td>
 *									   <select name="gruppen_id">
 *										    <option value="">[auswählen]</option>
 *										    <?PHP
 *                           $result = mysql_query("SELECT id,name FROM bestellgruppen ORDER BY name") or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
 *	                         while ($row = mysql_fetch_array($result)) echo "<option value='".$row['id']."'>".$row['name']."</option>\n";
 *												?>
 *						         </select>
 *									</td>
 *							 </tr>
 *							 <tr>
 *							    <td>Bitte Zugangspasswort angeben:</td>
 *									<td><input type="password" size="12" name="gruppen_pwd"></td>
 *							 </tr>
 *							 <tr>
 *							    <td colspan="2" align="middle"><input type="submit" value="einloggen"><input type="button" value="abbrechen" onClick="self.location.href='index.php'"></td>
 *							 </tr>
 *						</table>
 *						
 *				 </form> 
 *				 <?PHP } else {
 */
 $gruppen_pwd = 'obsolet';
     if( ! $angemeldet ) {
       echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>";
       return;
     } else	 {
				 
				if(isset($_REQUEST['amount']) && isset($_REQUEST['gruppen_id'])&& $_REQUEST['amount']>0 ){
					sqlGroupTransaction(0, $_REQUEST['gruppen_id'],$_REQUEST['amount']);
				}
				 $meinKonto = True;
?>
<h2>Aktueller Kontostand 
<?
   echo round(kontostand($login_gruppen_id),2)." Euro </h2>";

				 include('windows/showGroupTransaktions.php') ?>
<h2>Überweisung eintragen</h2>
<form action="index.php" method="post">
<input type="hidden" name="area" value="meinkonto">
<input type="hidden" name="gruppen_id" value="<?echo $gruppen_id?>"/>
<input type="hidden" name="gruppen_pwd" value="<?echo $_REQUEST['gruppen_pwd']?>"/>
Ich habe heute 
<input type="text" size="12" name="amount"/>
Euro <input type="submit" value="überwiesen"/>
</form>
				 
Hier soll noch rein...
<ul>
<li>persönliche daten ändern ...</li>
<li>abbonieren der verschieden mailverteiler</li>
<li>vielleicht auch sowas wie mein desktop, also die startseite der software...</li>
<li>andere ideen ?</li>
</ul>
<?PHP } ?>
