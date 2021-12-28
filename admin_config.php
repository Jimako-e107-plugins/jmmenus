<?php

// Generated e107 Plugin Admin Area 

require_once('../../class2.php');
if (!getperms('P')) 
{
	e107::redirect('admin');
	exit;
}

// e107::lan('jmmenus',true);


// core is using something else, not minified and only on frontend
// credit for this notification system belongs Spinnet Planet and it is used for 0.7
// Sorry, but I wasn't able to find a way how to do it in 2.3

e107::js('footer', e_PLUGIN . "jmmenus/admin/js/bootstrap-notify.min.js", 'jquery');
e107::js('footer', e_PLUGIN . "jmmenus/admin/js/jmmenus_admin.js", 'jquery');
    

class jmmenus_adminArea extends e_admin_dispatcher
{

	protected $modes = array(	
	
		'menus' => array(
			'controller' => 'menus_ui',
			'path' => null,
			'ui' => 'menus_form_ui',
			'uipath' => null,
		),
		

	);	
	
	
	protected $adminMenu = array(

		'menus/list'			=> array('caption'=> LAN_MANAGE, 'perm' => 'P'),
 
	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	protected $menuTitle = 'JM Menus';
}




				
class menus_ui extends e_admin_ui
{
			
		protected $pluginTitle		= 'JM Menus';
		protected $pluginName		= 'jmmenus';
	//	protected $eventName		= 'jmmenus-menus'; // remove comment to enable event triggers in admin. 		
		protected $table			= 'menus';
		protected $pid				= 'menu_id';
		protected $perPage			= 50; 
		protected $batchDelete		= true;
		protected $batchExport     = true;
		protected $batchCopy		= true;

	//	protected $sortField		= 'somefield_order';
	//	protected $sortParent      = 'somefield_parent';
	//	protected $treePrefix      = 'somefield_title';

	//	protected $tabs				= array('Tabl 1','Tab 2'); // Use 'tab'=>0  OR 'tab'=>1 in the $fields below to enable. 
		
	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.
	
		protected $listOrder		= 'menu_id DESC';
	
		protected $fields 		= array (
			'checkboxes'              => array (  'title' => '',  'type' => null,  'data' => null,  'width' => '5%',  'thclass' => 'center',  'forced' => 'value',  'class' => 'center',  'toggle' => 'e-multiselect',  'readParms' =>  array (),  'writeParms' =>  array (),),
    		'menu_id' => array('title' => LAN_ID, 'data' => 'int', 'width' => '5%', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    		'menu_name' => array('title' => LAN_TITLE, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'inline' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    		'menu_layout' => array('title' => 'Layout', 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'filter' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'batch' => false),
    		'menu_location' => array('title' => 'Location', 'type' => 'text', 'data' => 'str',
    			'width' => 'auto', 'filter' => true, 'inline' => false, 'help' => '',
    			'readParms' => array(), 'writeParms' => array(), 'class' => 'left',
    			'thclass' => 'left', 'batch' => false),
    		'menu_order' => array('title' => LAN_ORDER, 'type' => 'number', 'data' => 'int', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    		'menu_class' => array('title' => LAN_USERCLASS, 'type' => 'userclass', 'data' => 'str', 'width' => 'auto', 'batch' => true, 'filter' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    		'menu_pages' => array('title' => 'Pages', 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    		'menu_path' => array('title' => 'Path', 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
    
    		'menu_parms' => array('title' => 'Parms', 'type' => 'textarea', 'data' => 'str', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'filter' => false, 'batch' => false),
    		'options' => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null,
			'width' => '10%', 'thclass' => 'center last', 'class' => 'center last',
			'forced' => true, 'readParms' => array('editClass' => e_UC_NOBODY), 'writeParms' => array('noedit' => true)),
			'options'                 => array (  'title' => LAN_OPTIONS,  'type' => null,  'data' => null,  'width' => '10%',  'thclass' => 'center last',  'class' => 'center last',  'forced' => 'value',  'readParms' =>  array (),  'writeParms' =>  array (),),
		);		
		
		protected $fieldpref = array('menu_name', 'menu_location', 'menu_class', 'menu_layout');
		

	//	protected $preftabs        = array('General', 'Other' );
		protected $prefs = array(
		); 

	
		public function init()
		{
 
		      $this->postFilterMarkup = $this->DeleteMenusButton();
	
		}

		
		// ------- Customize Create --------
		
		public function beforeCreate($new_data,$old_data)
		{
			return $new_data;
		}
	
		public function afterCreate($new_data, $old_data, $id)
		{
			// do something
		}

		public function onCreateError($new_data, $old_data)
		{
			// do something		
		}		
		
		
		// ------- Customize Update --------
		
		public function beforeUpdate($new_data, $old_data, $id)
		{
			return $new_data;
		}

		public function afterUpdate($new_data, $old_data, $id)
		{
			// do something	
		}
		
		public function onUpdateError($new_data, $old_data, $id)
		{
			// do something		
		}		
		
		// left-panel help menu area. (replaces e_help.php used in old plugins)
		public function renderHelp()
		{
			$caption = LAN_HELP;
			$text = 'This option is for cleaning menus tables without using PHPMyAdmin. All available / needed menus are created again by using Menu Manager. Menus added to any layout are untouched with using Delete button. ';

			return array('caption'=>$caption,'text'=> $text);

		}
			
	public function DeleteMenusButton()
	{
		$text = "</fieldset></form><div class='e-container'>
			<table id='table_delete_notusedmenus' style='" . ADMIN_WIDTH . "' class='table adminlist table-striped'>";
		$text .=
			"<button id='delete_notusedmenus' type='button' table='menus'
			idName='notused' class='btn btn-danger'>Delete All Not Used Menus</button></div>";
		$text .= "</td></tr></table></div><form><fieldset>";
		return $text;
	}	
}
				


class menus_form_ui extends e_admin_form_ui
{

}		
		
		
new jmmenus_adminArea();

require_once(e_ADMIN."auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN."footer.php");
exit;

