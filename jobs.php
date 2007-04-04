<?PHP
   // Übergebene Variablen einlesen..
   if (isset($HTTP_GET_VARS['jobcenter_pwd'])) $jobcenter_pwd = $HTTP_GET_VARS['jobcenter_pwd'];       // Passwort für den Bereich
	 
	 // Passwort prüfen...
	 $pwd_ok = ($jobcenter_pwd == $real_jobcenter_pwd);
?>



<h2>FoodCoop Kreuzberg-Neukölln</h2>

  <?PHP
	 
	    // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
			if (!isset($jobcenter_pwd) || !$pwd_ok) {
	?>
				 <form action="index.php">
				    <input type="hidden" name="area" value="jobs">
				    <b>Bitte Zungangspasswort angeben:</b><br>
						<input type="password" size="12" name="jobcenter_pwd"><input type="submit" value="ok">						
				 </form>
	<?PHP
			} else	{
  ?>

<h3>=> Aufgabenverteilung<=</h3>
<br>
Bisher muß ich euch noch per Hand eintragen... Deswegen einfach eine <b>Mail an <a href="mailto:job_center@gmx.de">job_center[at]gmx.de</a></b> mit dem Job den ihr
übernehmt.<br>
Falls ihr den Job sehr kurzfirstig und als einziger übernehmt (z.B.: es is Di und ihr meldet euch für die Sortiergruppe am Do)<br>
Schreibt bitte auch ne kurze mail an die Liste, damit die Bestellgruppe weiß, dass sie unbesorgt bestellen kann.<br>
Um euch untereinander abzusprechen, kann man die Namen in der Liste anklicken und sieht dann ggf. Kommentare und die Mail-Adressen der anderen!<br> <br> 

Hier noch eine kurze "Anleitung/Fettnäpfchenliste" für die <a href="bestellgruppe-howto.html">Bestellgruppe.</a><br> 
<br><br>
<table border="1">
   <tr bgcolor="#BBBBBB">
	        <td rowspan="2" valign="bottom">Datum</td>
      		<td>Mittwochs</td>
		<td>Donnerstags</td>
	 </tr>
   <tr bgcolor="#DDDDDD">
		<td>Bestellgruppe </td>
		<td>Sortiergruppe </td>
	 </tr>
   <tr>
		<td valign="top" bgcolor="#DDDDDD">Woche 19.7. - 25.7.</td>
		<td><a href="javascript:alert('Kommentar:\n - \n\n Mail:\n harte-zeiten@gmx.de ');" class="smalfont">Ossi</a><br>
		<a href="javascript:alert('Kommentar:\n - \n\n Mail:\n Fcjeti@aol.com ');" class="smalfont">Jens </a></td>
		
		<td><a href="javascript:alert('Kommentar:\n -  \n\n Mail:\n katawutz@yahoo.de');" class="smalfont">Katha</a><br>
		<a href="javascript:alert('Kommentar:\n 12:00Uhr - 14:00Uhr \n\n Mail:\n Fcjeti@aol.com ');" class="smalfont">Jens </a></span></td>
			   
         </tr>
   <tr>
		<td valign="top" bgcolor="#DDDDDD">Woche 26.7. - 1.8.</td>
		<td><span style="color:#CC0000"><br>dringend!</span></td>
		<td><a href="javascript:alert('Kommentar:\n  \n\n Mail:\n ms_sophie22@hotmail.com ');" class="smalfont">Sophie</a></span> <br> Jana</td>
         </tr>
   <tr>
		<td valign="top" bgcolor="#DDDDDD">Woche 2.8. - 15.8.</td>
         </tr>
   <tr>
		<td valign="top" bgcolor="#DDDDDD">Woche 16.8. - 22.8.</td>
         </tr>
   <tr>
		<td valign="top" bgcolor="#DDDDDD">Woche 23.8. - 29.8.</td>
			
</table>
<br> <br> 
Einige wichtige Aufgaben, werden zur Zeit über mehrer Wochen von den gleichen Leuten gemacht:<br>
Finanzgruppe:<br>
Max und Jens aus der Regensburger -> maxov[at]web.de, dajense[at]gmx.net<br><br> 
Annahme:<br>
macht zur Zeit immer Max oder jemand anders aus der Manteuffelstraße. -> fernbedienung[at]fernsehsofa.de<br><br> 
Produktpallettengruppe:<br> 
Benni und Kathrin von Dresder27 -> benni[at]dresdener27.de<br> <br> 

Bei all diesen Gruppen ist es notwendig, dass sich nach und nach mehr Leute<br> 
die entsprechenden Skills aneignen um später als Ersatzmann/frau oder Ablösung einspringen zu können!<br>  
Dieses einlernen funktioniert am besten durch mit machen, also einfach mal ne mail schreiben an die <br>  
Gruppe deiner Wahl und fragen wann man mal mitmachen kann.<br>  
<br> 
<a href="liste.jpg">Kontakt</a>
<?PHP
    }
?>
