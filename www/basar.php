<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
	include('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	include('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	include('code/connect_MySQL.php');
	include("code/zuordnen.php");

  require_once( 'code/login.php' );
 
		include ('head.php');
// um die bestellungen nach produkten sortiert zu sehen ....


// Übergebene Variablen einlesen...
//   if (isset($_REQUEST['gruppen_id'])) $gruppen_id = $_REQUEST['gruppen_id'];       // Passwort für den Bereich
    if (isset($_REQUEST['gruppen_pwd'])) $gruppen_pwd = $_REQUEST['gruppen_pwd'];       // Passwort für den Bereich
    if (isset($_REQUEST['bestgr_pwd'])) $bestgr_pwd = $_REQUEST['bestgr_pwd'];       // Passwort für den Bereich
    if (isset($_REQUEST['bestellungs_id'])) $bestell_id = $_REQUEST['bestellungs_id'];
    if (isset($_REQUEST['allGroupsArray'])) $allGroupsArray = $_REQUEST['allGroupsArray'];
    if (isset($_REQUEST['sortierfolge'])) $sortierfolge = $_REQUEST['sortierfolge'];
    if (isset($_REQUEST['nichtGeliefert'])) $nichtGeliefert = $_REQUEST['nichtGeliefert'];

    $pwd_ok = false;
    $bestgrup_view = false;

	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

         //infos zur gesamtbestellung auslesen 
         $sql = "SELECT *
                  FROM gesamtbestellungen
                  WHERE id = ".$bestell_id."";
         $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
         $row_gesamtbestellung = mysql_fetch_array($result);               
?>
<h1>Basar</h1>
         <table class="info">
               <tr>
                   <th> Bestellung: </th>
                     <td style="font-size:1.2em;font-weight:bold"><?PHP echo $row_gesamtbestellung['name']; ?></td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellstart']; ?></td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellende']; ?></td>
                </tr>                
            </table>
      <br>
      <br>
         <form action="basar.php" method="post">
         <table style="width: 600px;" >
	      <tr>
		 <td colspan=3> Gruppe: 
		 <select name="gruppe">
		 <?
		 //Auswahl der Gruppennamen
	           $gruppen=sql_gruppen();
		   while($gruppe = mysql_fetch_array($gruppen)){
		   	echo "<option value=\"".$gruppe['id']."\">".
				$gruppe['name']."</option>\n";
		   }
		   ?>
	   	</select> </td>
	      </tr>
            <tr class="legende">
               <td>Produkt</td>
               <td>Menge in Basar</td>
               <td>Menge</td>
    </tr>
                            
<?php          
      if(isset($_REQUEST['gruppe'])){
      	  $gruppe = $_REQUEST['gruppe'];
	  //echo "Gruppe gesetzt: ".$gruppe."<br>\n";
      }
      //Den Basar erstellen
      $result1 = sql_basar();

      while  ($basar_row = mysql_fetch_array($result1)) {
	       $fieldName = "menge_".$basar_row['produkt_id'];
	       $menge=$basar_row['basar'];
	       if(isset($_REQUEST[$fieldName]) && $_REQUEST[$fieldName]!=0 && isset($gruppe)){
	                $gruppen_menge=$_REQUEST[$fieldName];
	       		$menge-=$gruppen_menge;
			sql_basar2group($gruppe,
				$basar_row['produkt_id'], 
				$gruppen_menge);
		        if($menge==0) continue;
	       }
	       echo "
	      <tr>
		 <td>".$basar_row['name']."</td>
		 <td><b>".$menge."</b> </td>
		 <td><input name=\"".$fieldName."\" type=\"text\" size=\"3\" /></td>
	      </tr>";

	       
		     
	 } //end while gruppen array
?>
	 
     <tr style='border:none'>
		 <td colspan='4' style='border:none'></td>
	      </tr>
     <tr>
     	<td colspan=4 >
		Glasrückgabe zu 16 Cent (Anzahl eintragen):	<input name="menge_glas" type="text" size="3" />
		<?
			if(isset($_REQUEST['menge_glas']) && $_REQUEST['menge_glas']!=0 && isset($gruppe)){
	                $menge=$_REQUEST['menge_glas'];
			sql_groupGlass($gruppe, $menge);
	       }

		?>
	</td>
     </tr>
   

	<tr style='border:none'>
		 <td colspan='4' style='border:none'></td>
	      </tr>
   
   <tr style='border:none'>
	<td colspan='4' style='border:none'>
	   <input type="hidden" name="area" value="bestellt_produkte">			
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="submit" value=" Neu laden / Basareintrag übertragen ">
	   <input type="reset" value=" Änderungen zurücknehmen">
	</td>
   </tr>
   </table>                   
   </form>

   <form action="index.php" method="post">
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="hidden" name="area" value="bestellt">			
	   <input type="submit" value="Zurück ">
   </form>
