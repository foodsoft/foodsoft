
<h1>Mein Konto</h1>
<?PHP
  //error_reporting(E_ALL); // alle Fehler anzeigen
  require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php");
   
 $gruppen_pwd = 'obsolet';
     if( ! $angemeldet ) {
       echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>";
       return;
     } else	 {
				 
				if(isset($_REQUEST['amount']) && isset($_REQUEST['gruppen_id'])&& $_REQUEST['amount']>0 ){
					sqlGroupTransaction(0, $_REQUEST['gruppen_id'],$_REQUEST['amount']);
				}
				 $meinKonto = True;
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
