<h1>Start ....</h1>  
<?
open_table( 'menu' );
  foreach(possible_areas() as $menu_area){
    areas_in_menu($menu_area);
  }
close_table();
?>
