<?php    
/*
* e107 website system
*
* Copyright (C) 2008-2013 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*
* e107 JM Menus Plugin
*
* #######################################
* #     e107 website system plugin      #
* #     by Jimako                    	 #
* #     https://www.e107sk.com          #
* #######################################
*/ 

if (!defined('e107_INIT')) { exit; }

//v2.x Standard for extending menu configuration within Menu Manager. (replacement for v1.x config.php)
	
class jmmenus_menu
{
	function __construct()
	{
		// e107::lan('jm_shortcode','menu',true); // English_menu.php or {LANGUAGE}_menu.php
	}

	/**
	 * Configuration Fields.
	 * @return array
	 */
	public function config($menu='')
	{

		$fields = array();

    	switch($menu)
		{
			case "shortcode":
		
				$fields['shortcode_menuCaption']      = array('title'=> "Caption", 'type'=>'text', 'multilan'=>true, 'writeParms'=>array('size'=>'xxlarge'));
				$fields['shortcode_menuCode']         = array('title'=> "Shortcode code", 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge'));
				$fields['shortcode_menuTableStyle']   = array('title'=> "ID/Mode for tablestyle", 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge')); 
				return $fields;
      
        	case "frontpage_hero":
                $templates = e107::getLayouts('hero', 'hero', 'front', null, false, false);
                $fields['shortcode_menuCaption']      = array('title'=> LAN_CAPTION, 'type'=>'text', 'multilan'=>true, 'writeParms'=>array('size'=>'xxlarge'));	
                $fields['shortcode_menuTableStyle']   = array('title'=> "ID/Mode for tablestyle" , 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge')); 
                $fields['template']     = array('title'=> LAN_TEMPLATE,  'type'=>'dropdown', 'writeParms'=>array('optArray'=>$templates, 'default'=>'blank'), 'help'=>'');
				 
            return $fields;

			case "frontpage_featurebox":
                $templates = e107::getLayouts('featurebox', 'featurebox_category', 'front', null, false, false);
                $fields['shortcode_menuCaption']      = array('title'=> LAN_CAPTION, 'type'=>'text', 'multilan'=>true, 'writeParms'=>array('size'=>'xxlarge'));	
                $fields['shortcode_menuTableStyle']   = array('title'=> "ID/Mode for tablestyle" , 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge')); 
                $fields['template']     = array('title'=> LAN_TEMPLATE,  'type'=>'dropdown', 'writeParms'=>array('optArray'=>$templates, 'default'=>'blank'), 'help'=>'');
				 
            return $fields;

			case "frontpage_wmessage":
              //  $templates = e107::getLayouts('featurebox', 'featurebox', 'front', null, false, false);
                $fields['shortcode_menuCaption']      = array('title'=> LAN_CAPTION, 'type'=>'text', 'multilan'=>true, 'writeParms'=>array('size'=>'xxlarge'));	
                $fields['shortcode_menuTableStyle']   = array('title'=> "ID/Mode for tablestyle" , 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge')); 
               // $fields['template']     = array('title'=> LAN_TEMPLATE,  'type'=>'dropdown', 'writeParms'=>array('optArray'=>$templates, 'default'=>'blank'), 'help'=>'');
				 
            return $fields;
            
        	case "block_code":
            
				$fields['block_title']        = array('title'=> "Caption", 'type'=>'text', 'multilan'=>true, 'writeParms'=>array('size'=>'xxlarge'));
				$fields['block_content']      = array('title'=> "HTML code", 'type'=>'textarea', 'writeParms'=>array('size'=>'xxlarge'));
				$fields['block_style']        = array('title'=> "Style code [theme support]", 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge' ));              
				$fields['block_tablestyle']   = array('title'=> "ID/Mode for tablestyle [theme support]", 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge' ));  
            return $fields;
   		}	 
	}
}
 