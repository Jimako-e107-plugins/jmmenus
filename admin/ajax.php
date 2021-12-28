<?php
 
require_once('../../../class2.php');
if (!defined('e107_INIT')) { exit; }
 
require_once(e_PLUGIN."jmmenus/admin/functions.php");

if ($_POST['func'] == 'DELETE_notusedmenus') {
  
  $query = " WHERE menu_location = '' AND menu_layout = '' ";
  $count = 0;
  $table =  'menus';
  $idName = 'menu_id';
  
  if($records = e107::getDb()->retrieve('menus', 'menu_id', $query, true) ) {  
    foreach($records as $row)  {
      //delete by one record
       $id = $row[$idName];
       $result = delete_record($table, $idName, $id);
       if($result === true) {
        ++$count;
      }
      else echo $result; 
      }
  }
  if($count > 0 ) {
     echo "Deleted {$count} records of table {$table} ";
  }
  else {
    echo "Nothing to delete";
  }
  
	die;
}