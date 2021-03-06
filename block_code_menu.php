<?php    
/*
* e107 website system
*
* Copyright (C) 2008-2013 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*
* e107 JM Menu Plugin
*
* #######################################
* #     e107 website system plugin      #
* #     by Jimako                    	 #
* #     https://www.e107sk.com          #
* #######################################
*/


if (!defined('e107_INIT')) { exit; }

$text = "";
 
if(isset($parm['block_title'][e_LANGUAGE]))
{
	$caption = $parm['block_title'][e_LANGUAGE];
}
else $caption = $parm['block_title']; 
  
$text =  e107::getParser()->toHTML($parm['block_content']);

$styleid =  $parm['block_tablestyle']; 
        
$s = $parm['block_style'];     
                        
if(is_string($s) && strlen($s) > 0) {
   e107::getRender()->setStyle($s);
}        
                                    
e107::getRender()->tablerender($caption, $text,  $styleid  ) ;

 
?>