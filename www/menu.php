   <h1>Start ....</h1>
	 
   <table class="menu">
	   <tr>
		    <td><input type="button" value="Mein Konto" class="bigbutton" onClick="self.location.href='index.php?area=meinkonto'"></td>
				<td valign="middle" class="smalfont">Hier können die einzelnen Gruppen ihre Kontoauszüge einsehen....</td>
		 </tr>
	   <tr>
		    <td><input type="button" value="Bestellen" class="bigbutton" onClick="self.location.href='index.php?area=bestellen'"></td>
				<td valign="middle" class="smalfont">Hier können die einzelnen Gruppen an den aktuellen Bestellung Teilnehmen....</td>
		 </tr>
	   <? if($hat_dienst_I or $hat_dienst_IV){?>
	   <tr>
		    <td><input type="button" value="Lieferschein" class="bigbutton"
		    onClick="self.location.href='index.php?area=lieferschein'"></td>
				<td valign="middle" class="smalfont">Hier kann der Lieferschein abgeglichen werden...</td>
		 </tr>		 		 
	   <tr>
		    <td><input type="button" value="Verteilung" class="bigbutton"
		    onClick="self.location.href='index.php?area=bestellt_produkte'"></td>
				<td valign="middle" class="smalfont">Hier kann die Verteilung eingesehen und angepasst werden...</td>
		 </tr>		 		 
		 <?}
			if($hat_dienst_IV){
		 ?>
			<tr>
		    <td><input type="button" value="Bestellschein" class="bigbutton" onClick="self.location.href='index.php?area=bestellschein'"></td>
				<td valign="middle" class="smalfont">FAX mit Bestellung, Gesamtbestellung ansehen</td>
		 </tr>
			<tr>
		    <td><input type="button" value="Produktdatenbank" class="bigbutton" onClick="self.location.href='index.php?area=produkte'"></td>
				<td valign="middle" class="smalfont">Neue Produkte eingeben ... Preise verwalten ... Bestellung online stellen</td>
		 </tr>
		 <?} ?>
			<tr>
		    <td><input type="button" value="Gruppenverwaltung" class="bigbutton" onClick="self.location.href='index.php?area=gruppen'"></td>
				<td valign="middle" class="smalfont">Hier kann man die Bestellgruppen und deren Konten verwalten...</td>
		 </tr>		 
		 <?
			if($$hat_dienst_IV){
		 ?>
			<tr>
		    <td><input type="button" value="LieferantInnen" class="bigbutton" onClick="self.location.href='index.php?area=lieferanten'"></td>
				<td valign="middle" class="smalfont">Hier kann man die LieferantInnen verwalten...</td>
		 </tr>		 
		 <?} ?>

			<tr>
		    <td><input type="button" value="Dienstkontrollblatt" class="bigbutton" onClick="self.location.href='index.php?area=dienstkontrollblatt'"></td>
				<td valign="middle" class="smalfont">Hier kann man das Dientkontrollblatt einsehen...</td>
		 </tr>		 
		 <?
			if($$hat_dienst_IV or $$hat_dienst_III or $$hat_dienst_I){
		 ?>
			<tr>
		    <td><input type="button" value="Up/Download" class="bigbutton" onClick="self.location.href='index.php?area=updownload'"></td>
				<td valign="middle" class="smalfont">Hier kann die Datenbank hoch und runter geladen werden...</td>
		 </tr>		 		 
		 <?} ?>
	 </table>
