if( 0 ) { // noch ausser betrieb
  ?>
	 <form name="skip" action="showGroupTransaktions.php">
	    <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="start_pos" value="<?PHP echo $start_pos; ?>">
			<?PHP 
			   $downButtonScript = "";
			   if ($start_pos > 0 && $start_pos > $size)
				    $downButtonScript="document.forms['skip'].start_pos.value=".($start_pos-$size).";";
				 else if ($start_pos > 0)
				    $downButtonScript="document.forms['skip'].start_pos.value=0;";
						
				 if ($downButtonScript != "")
				    echo "<input type=button value='<' onClick=\"".$downButtonScript." ;document.forms['skip'].submit();\">";

			   $upButtonScript = "";
			   if ($num_rows == $size)
				    $upButtonScript="document.forms['skip'].start_pos.value=".($start_pos+$size).";";

				 if ($upButtonScript != "") echo "<input type=button value='>' onClick=\"".$upButtonScript.";document.forms['skip'].submit()\"";
			?>
	 </form>
   <?
}

