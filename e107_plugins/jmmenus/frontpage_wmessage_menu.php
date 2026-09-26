<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2016 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * jm_shortcode menu file.
 *
 */


if (!defined('e107_INIT')) { exit; }
 
$text = "";

if(is_string($parm))
	{
		parse_str($parm, $parms);
	}
	else
	{
		$parms = $parm;
	}
	
	
 
if(isset($parms['shortcode_menuCaption'][e_LANGUAGE]))
{
	$caption = $parms['shortcode_menuCaption'][e_LANGUAGE];
}
else $caption = $parms['shortcode_menuCaption']; 

// supported parms count, template
/*
$supportedkeys = array(  'template' );
$parameters = '';     
foreach ($parms as $key=>$value) {
 
  if($value  && in_array($key,$supportedkeys)) {
	   $parameters .=  "&{$key}={$value}";
	}
}
if($parameters) {
  $code = "{WMESSAGE:  {$parameters}}";
}
else {
  $code = "{WMESSAGE=force}";
}
*/ 
$code = "{WMESSAGE=force}";
$text =  e107::getParser()->parseTemplate($code);
$style =  e107::getParser()->parseTemplate($parm['shortcode_menuTableStyle']);    
 
e107::getRender()->tablerender($caption, $text, $style );






?>