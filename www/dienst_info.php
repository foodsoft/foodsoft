<?
  $result = sql_get_dienst_group($login_gruppen_id ,"Akzeptiert");
  $baldigerdienst=FALSE;
  $critical_date = in_two_weeks();
  $show_dienste = array();
  while($row = mysql_fetch_array($result)){
       if(compare_date2($row["Lieferdatum"], $critical_date)) {
                 $baldigerdienst = TRUE;
		 $show_dienste[] = $row["Lieferdatum"];
       }
  }
  if($baldigerdienst){
    get_http_var( 'dienst_rueckbestaetigen', 'u', 0 );
    if( $dienst_rueckbestaetigen ) {
        foreach($show_dienste as $datum){
          sql_dienst_bestaetigen($datum);
    }
  } else {
     ?> <h2> Du hast bald Dienste: </h2> <?
     foreach($show_dienste as $datum){
        echo "<h3>.$datum.":</h3>";
        $current_dienst = "Initial";
        foreach( sql_get_dienste( "Lieferdatum = $datum" ) as $row ) {
          if($current_dienst != $row["Dienst"]){
            $current_dienst = $row["Dienst"];
            echo "<h4> Dienst $current_dienst</h4>";
          }
          dienst_view($row, $login_gruppen_id, FALSE);
        }
     }
     echo fc_action( 'text=OK', 'dienst_rueckbestaetigen=1' );

     exit();
     }
  }

?>
