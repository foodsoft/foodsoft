<?PHP
   
      /* wichtige variablen:
      array: $liste[ ] enthält die produkt_id s für die bestellung
      
      */   
   
    
    $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
    $errStr = "";
    
    // Verbindung zur Datenbank herstellen
    require_once('../code/config.php');
    require_once('../code/views.php');
    require_once('../code/err_functions.php');
    require_once('../code/login.php');
    require_once('../code/zuordnen.php');
    
    // zur Sicherheit das Passwort prüfen..
    nur_fuer_dienst_IV();
    
   get_http_var("startzeit_tag");
   get_http_var("startzeit_monat");
   get_http_var("startzeit_jahr");
   get_http_var("startzeit_stunde");
   get_http_var("startzeit_minute");
   get_http_var("endzeit_tag");
   get_http_var("endzeit_monat");
   get_http_var("endzeit_jahr");
   get_http_var("endzeit_stunde");
   get_http_var("endzeit_minute");
   get_http_var("lieferung_tag");
   get_http_var("lieferung_monat");
   get_http_var("lieferung_jahr");
   get_http_var("bestellname");
   var_dump($_REQUEST);
   var_dump($bestellname);
   get_http_var("bestelliste");
    
    // ggf. die neue bestellung einfügen 
    if (isset($startzeit_tag)) {
         $startzeit = $startzeit_jahr."-".
	              $startzeit_monat."-".
		      $startzeit_tag." ".
		      $startzeit_stunde.":".
		      $startzeit_minute.":00";
         $endzeit = $endzeit_jahr."-".
	            $endzeit_monat."-".
		    $endzeit_tag." ".
		    $endzeit_stunde.":".
		    $endzeit_minute.":00";
         $lieferung = $lieferung_jahr."-".
	            $lieferung_monat."-".
		    $lieferung_tag;
         
         $bestellname_repl = str_replace("'", "", str_replace('"',"'",$bestellname));
         var_dump($bestellname_repl);
         
         if ($bestellname_repl == "") $errStr.= "Die Bestellung muß einen Namen bekommen!<br>";
         if (!isset($bestelliste)) $errStr.= "Die Bestellung enthält keine Produkte!<br>";

         
         // Wenn keine Fehler, dann einfügen...
         if ($errStr == "") {
             sql_insert_bestellung($bestellname_repl, $startzeit, $endzeit, $lieferung);
             $gesamtbestellung_id = mysql_insert_id();
                                    
             for ($i = 0; $i < count($bestelliste); $i++) {
                        // preis, gebinde, und bestellnummer auslesen
		  $result = sql_aktuelle_produktpreise($bestelliste[$i]);
                  $produkt_row = mysql_fetch_array($result);  // alles in ein array schreiben
                          // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben
		  sql_insert_bestellvorschlaege($bestelliste[$i],$gesamtbestellung_id,$produkt_row['id']);
                     
             } //end for - bestellvorschläge füllen
      
             $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
                     
         } //end if -wenn keine fehler ....
            
    } else {
       $startzeit_tag       = date("j");
       $startzeit_monat  = date("n");
       $startzeit_jahr      = date("Y");   
       $startzeit_stunde  = date("G");
       $startzeit_minute  = date("i");
       
       $endzeit_tag       = date("j");
       $endzeit_monat  = date("n");
       $endzeit_jahr      = date("Y");
       $endzeit_stunde  = "22";
       
       $lieferung_tag       = date("j");
       $lieferung_monat  = date("n");
       $lieferung_jahr      = date("Y");

    }    
     
    
?>

<html>
<head>
   <meta http-equiv="Content-Type" content="ISO-8859-15">
   <title>neue Bestellung</title>
    <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">


<h3>neue Bestellung</h3>
    <form name="reload_form" action="insertBestellung.php">
      <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
      <input type="hidden" name="action" value="">
      
      <?PHP
         if (isset($HTTP_GET_VARS['bestelliste'])) {
             $bestelliste = $HTTP_GET_VARS['bestelliste'];
               
               while (list($key, $value) = each($bestelliste)) {
                  echo "<input type='hidden' name='bestelliste[]' value='".$value."'>\n";
               }
          }
      ?>
      
      <table class="menu" style="width:370px;">
          <tr>
               <td><b>Name</b></td>
               <td><input type="text" name="bestellname" size="35"></td>
          </tr>      
         <tr>
             <td valign="top"><b>Startzeit</b></td>
               <td>
               
                <table border=0>
                  <tr>
                     <td>Datum</td>
                      <td>
         <?date_selector("startzeit_tag", $startzeit_tag,"startzeit_monat", $startzeit_monat, "startzeit_jahr", $startzeit_jahr)?>
                     </td>
                   
                   </tr><tr>

                 <td>Zeit</td>
                         <td>
         <?time_selector("startzeit_stunde", $startzeit_stunde,"startzeit_minute", $startzeit_minute)?>
                         </td>
                     </tr>
                  </table>   
                   
               </td>
          </tr>
         <tr>
             <td valign="top"><b>Ende</b></td>
               <td>
               
                <table border=0>
                  <tr>
                     <td>Datum</td>
                      <td>
         <?date_selector("endzeit_tag", $endzeit_tag,"endzeit_monat", $endzeit_monat, "endzeit_jahr", $endzeit_jahr)?>
                        </td>
                   
                   </tr><tr>

                 <td>Zeit</td>
                         <td>
         <?time_selector("endzeit_stunde", $endzeit_stunde,"endzeit_minute", $endzeit_minute)?>
                         </td>
                     </tr>
                  </table>   
                   
               </td>
          </tr>
             <td valign="top"><b>Lieferung</b></td>
               <td>
               
                <table border=0>
                  <tr>
                     <td>Datum</td>
                      <td>
         <?date_selector("lieferung_tag", $lieferung_tag,"lieferung_monat", $lieferung_monat, "lieferung_jahr", $lieferung_jahr)?>
                        </td>
                   
                   </tr>
                  </table>   
                   
               </td>
          </tr>
          <tr>
             <td colspan="2" align="middle"><input type="submit" value="Bestellung einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
          </tr>
      </table>
    </form>
    <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
