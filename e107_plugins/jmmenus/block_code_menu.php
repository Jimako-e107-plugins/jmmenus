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

$parms = is_array($parm) ? $parm : array();

$text = "";
 
$caption = varset($parms['block_title']);
if(isset($caption[e_LANGUAGE]))
{
	$caption = $caption[e_LANGUAGE];
}
  
$text =  e107::getParser()->toHTML(varset($parms['block_content']));

$styleid =  varset($parms['block_tablestyle']); 
        
$s = varset($parms['block_style']);     
                        
if(is_string($s) && strlen($s) > 0) {
   e107::getRender()->setStyle($s);
}        
                                    
e107::getRender()->tablerender($caption, $text,  $styleid  ) ;

 
?>