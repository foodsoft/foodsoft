



<? 
error_reporting(E_ALL);
	// Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 
	function show_form($value="/tmp/foodsoft_dump.gz") {  
	   ?>
		
        <form enctype="multipart/form-data" action="upload.php" method="post">
		<input type="hidden" name="MAX_FILE_SIZE" value="3000000">
		Welche Datei: <input name="userfile" type="file">
		<input type="submit" value="Send File">
		</form>

	  <?
         }  // close function   

	if(isset($_FILES['userfile']['tmp_name'])) {
	
	$uploaddir = '/tmp/';
	$filename  = $uploaddir . $_FILES['userfile']['name'];

print "<pre>";
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {
   print "File is valid, and was successfully uploaded.  Here's some more debugging info:\n";
} else {
   print "Error while uploading file!Error:\n";
   print($_FILES['userfile']['error']."\n");
   print("<a href=http://de.php.net/manual/de/features.file-upload.errors.php target=_blank> error codes</a>");
}
print "</pre>";

$command = "gzip -dc " .$filename.
			" | mysql -h ".$db_server.
		    " -u ".$db_user.
		    " -p".$db_pwd." ".$db_name;
		system($command, $return);
		if($return!=0){
			echo "Error, return value of: ".$return."<br>";
		} else {
	
			
			echo "<h3> Datenbank hochgeladen</h3>";

		}
        
 
	}
        else {
        
        show_form();
        
        }
	
	
	?>


