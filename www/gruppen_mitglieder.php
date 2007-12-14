
<?PHP
  
assert( $angemeldet ) or exit();
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

need_http_var('gruppen_id','u');
          //set variables for submit-button
          $self_fields['gruppen_id'] = $gruppen_id;

          //predefine all edit modes as false
	  $edit_names = FALSE;
	  $edit_dienst_einteilung=FALSE;


echo "<h1>Gruppenmitglieder f&uuml;r Gruppe $gruppen_id </h1> ";
// ggf. Aktionen durchführen (z.B. Gruppe löschen...)

global $login_gruppen_id ;

//FIXME: remove trim when http_get_var correctly returns integer
if( trim($login_gruppen_id) == $gruppen_id and ! $readonly){
	  $edit_names = TRUE;
}
  if( $hat_dienst_V and ! $readonly ) {
	  $edit_names = TRUE;
	  $edit_dienst_einteilung=TRUE;
  }

if(get_http_var('action','w')){
	if( $action == 'delete' ) {
	     fail_if_readonly();
	     nur_fuer_dienst(5);
	     need_http_var('person_id','u');
	     sql_delete_group_member($person_id, $gruppen_id);

	}
}

// Änderungen von Gruppenmitgliedern speichern

	get_http_var("nVorname[]", 'M');
	get_http_var("nEmail[]", 'M');
	get_http_var("nTelefon[]", 'M');
	get_http_var("newDienst[]", 'M');
	if(get_http_var("nName[]", 'M')){
	
	   foreach($nName as $change_id => $name){
		fail_if_readonly();
		if($edit_names!= TRUE){
		    echo " <div class='warn'>Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!</div> ";
		} else {

		    $record = sql_gruppen_members($gruppen_id, $change_id);
		    if($record['name']!= $nName[$change_id] or
		       $record['vorname']!= $nVorname[$change_id] or
		       $record['email']!= $nEmail[$change_id] or
		       $record['telefon']!= $nTelefon[$change_id] or
	               $record['diensteinteilung']!=$newDienst[$change_id]){
			       //nur dienst 5 darf diensteinteilung ändern
			       if($edit_dienst_einteilung==TRUE){
				       sql_update_gruppen_member($change_id, $nName[$change_id] , $nVorname[$change_id] , $nEmail[$change_id] , $nTelefon[$change_id], $newDienst[$change_id]);
			       } else {
				       sql_update_gruppen_member($change_id, $nName[$change_id] , $nVorname[$change_id] , $nEmail[$change_id] , $nTelefon[$change_id], $record['diensteinteilung']);
			       }
		    }
		    



		}

   	   }
	}

  // Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können
  ?>
    <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
      <? echo self_post(); ?>
      <input type='hidden' name='person_id' value=''>
      <input type='hidden' name='action' value=''>
    </form>
  <?
  if( $edit_dienst_einteilung ) {
    ?>
    <div id='transaction_button' style='padding-bottom:1em;'>
    <span class='button'
      onclick="document.getElementById('transaction_form').style.display='block';
               document.getElementById('transaction_button').style.display='none';"
      >Neue Gruppenmitglieder...</span>
    </div>

    <div id='transaction_form' style='display:none;padding-bottom:1em;'>
      <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <fieldset>
      <legend>
        <img src='img/close_black_trans.gif' class='button'
        onclick="document.getElementById('transaction_button').style.display='block';
                 document.getElementById('transaction_form').style.display='none';">
	Neue Gruppenmitglieder
      </legend>
      <?
	  // Schade, geht nicht, da "supplied argument is not a valid MySQL result resource in"
	  //$new_mem=array('id'=>"", 'vorname'=>"", 'name'=>"", 'email'=>"", 'telefon'=>"", 'diensteinteilung'=>"");
	  //membertable_view($new_mem, true, true);
      ?>
      Vorname: <input type="text" size="12" name="newVorname"/>
      Name: <input type="text" size="12" name="newName"/>
      Mail: <input type="text" size="12" name="newMail"/>
      Telefon: <input type="text" size="12" name="newTelefon"/>
      Diensteinteilung: <?dienst_selector(""); ?>
      <input type="submit" value="Anlegen"/>
      </fieldset>
      </form>
    </div>
    <?

	  //Einfügen des neuen Datensatzes
	  if(get_http_var('newVorname', 'w')){
		  get_http_var('newName', 'w');
		  get_http_var('newMail', 'R');
		  get_http_var('newTelefon', 'R');
		  get_http_var('newDienst[]', 'R');
		  sql_insert_group_member($gruppen_id, $newVorname, $newName, $newMail, $newTelefon, $newDienst[0]);
	  }
  }


  ?>
    </table>

    <br><br>

	<?  membertable_view(sql_gruppen_members($gruppen_id), $edit_names , $edit_dienst_einteilung); ?>


