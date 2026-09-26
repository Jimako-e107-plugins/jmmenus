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
	if(!is_array($parms))
	{
		$parms = array();
	}
	
	
 
$caption = varset($parms['shortcode_menuCaption']);
if(isset($caption[e_LANGUAGE]))
{
	$caption = $caption[e_LANGUAGE];
}

// supported parms count, template
$supportedkeys = array(  'template' );
$parameters = '';     
foreach ($parms as $key=>$value) {
 
  if($value  && in_array($key,$supportedkeys)) {
	   $parameters .=  "&{$key}={$value}";
	}
}

$parameters  =  substr($parameters,1);
if($parameters) {
  $code = "{HERO:  {$parameters}}";
}
else {
  $code = "{HERO}";
}
 
$text =  e107::getParser()->parseTemplate($code);
$style =  e107::getParser()->parseTemplate(varset($parms['shortcode_menuTableStyle']));     
 
$heroVisibility = e107::pref('hero', 'visibility', e_UC_NOBODY);
if(true) {
	e107::library('load', 'animate.css');
	e107::css('hero', 'css/hero.css');
}
 
e107::getRender()->tablerender($caption, $text, $style );






?>