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
 
$parms = is_array($parm) ? $parm : array();

$caption = varset($parms['shortcode_menuCaption']);
$caption = isset($caption[e_LANGUAGE]) ? $caption[e_LANGUAGE] : '';
  
 
$text =  e107::getParser()->parseTemplate(varset($parms['shortcode_menuCode']));
$style =  e107::getParser()->parseTemplate(varset($parms['shortcode_menuTableStyle']));   
 
e107::getRender()->tablerender($caption, $text, $style );
 