<?php

// Generated e107 Plugin Admin Area 

require_once('../../../class2.php');
if (!getperms('P')) 
{
	e107::redirect('admin');
	exit;
}

class jmmenus_adminArea extends e_admin_dispatcher
{

	protected $modes = array(	
	
		'menus' => array(
			'controller' => 'menus_ui',
			'path' => null,
			'ui' => 'menus_form_ui',
			'uipath' => null,
			'perm' => 'P',
		),
		

	);	
	
	
	protected $adminMenu = array(

		'menus/list'			=> array('caption'=> LAN_MANAGE, 'perm' => 'P'),
 
	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list',
		'menus/clean' => 'menus/list',
	);

	protected $pageTitles = array(
		'menus/clean' => 'Delete All Not Used Menus',
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

		'menu_parms' => array('title' => 'Parms', 'type' => 'textarea', 'data' => false, 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'filter' => false, 'batch' => false),
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

		
		protected $menuParms = null;

		public function beforeCreate($new_data,$old_data)
		{
			return $this->checkMenuParms($new_data, $old_data);
		}
	
		public function afterCreate($new_data, $old_data, $id)
		{
			$this->saveMenuParms($id);
		}

		public function onCreateError($new_data, $old_data)
		{
		}		
		
		public function beforeUpdate($new_data, $old_data, $id)
		{
			return $this->checkMenuParms($new_data, $old_data);
		}

		public function afterUpdate($new_data, $old_data, $id)
		{
			$this->saveMenuParms($id);
		}
		
		public function onUpdateError($new_data, $old_data, $id)
		{
		}		

		protected function hasMenuConfig($menu_path)
		{
			return file_exists(e_PLUGIN.$menu_path."e_menu.php");
		}

		protected function checkMenuParms($new_data, $old_data)
		{
			$this->menuParms = null;

			if(!isset($new_data['menu_parms']))
			{
				return $new_data;
			}

			$posted = (string) $new_data['menu_parms'];

			if(str_replace("\r\n", "\n", $posted) === str_replace("\r\n", "\n", (string) varset($old_data['menu_parms'])))
			{
				return $new_data;
			}

			if(!$this->hasMenuConfig((string) varset($new_data['menu_path'])))
			{
				$this->menuParms = $posted;
				return $new_data;
			}

			$raw = trim($posted);

			if($raw === '')
			{
				$this->menuParms = array();
				return $new_data;
			}

			$parms = json_decode($raw, true);

			if(!is_array($parms))
			{
				e107::getMessage()->addError('Parms must be a valid JSON object');
				return false;
			}

			$this->menuParms = $parms;

			return $new_data;
		}

		protected function saveMenuParms($id)
		{
			if($this->menuParms === null)
			{
				return;
			}

			$sql = e107::getDb();
			$id = (int) $id;

			$row = $sql->createQueryBuilder()->select('menu_path')->from('menus')->where('menu_id', $id)->fetchRow();

			if(!$row)
			{
				return;
			}

			if($this->hasMenuConfig($row['menu_path']))
			{
				$parms = is_array($this->menuParms) ? $this->menuParms : array();
				$check = e107::getMenu()->updateParms($id, $parms);
			}
			else
			{
				$tp = e107::getParser();
				$parms = $tp->filter((string) $this->menuParms);
				$parms = strip_tags($parms);
				$check = $sql->createQueryBuilder()->update('menus')->setTyped('menu_parms', $parms, 'escape')->where('menu_id', $id)->execute();
			}

			$this->menuParms = null;

			if($check)
			{
				e107::getMessage()->addSuccess('Parms saved');
			}
			elseif($check === false)
			{
				e107::getMessage()->addError(LAN_UPDATED_FAILED);
			}
			else
			{
				e107::getMessage()->addInfo(LAN_NO_CHANGE);
			}
		}
		
		// left-panel help menu area. (replaces e_help.php used in old plugins)
		public function renderHelp()
		{
			$caption = LAN_HELP;
			$text = 'This option is for cleaning menus tables without using PHPMyAdmin. All available menus are created again by using Menu Manager (default core behavior). 
            <br>Menus added to any layout are untouched with using Delete button. ';

			return array('caption'=>$caption,'text'=> $text);

		}
			
	public function DeleteMenusButton()
	{
		$url = e_REQUEST_SELF.'?mode=menus&amp;action=clean';

		return "<a class='btn btn-danger' href='".$url."'>Delete All Not Used Menus</a>";
	}

	public function CleanPage()
	{
		$count = e107::getDb()->createQueryBuilder()
			->from('menus')
			->where('menu_location', '')
			->where('menu_layout', '')
			->count();

		if($count === 0)
		{
			e107::getMessage()->addInfo('Nothing to delete');
		}
		else
		{
			e107::getMessage()->addWarning(str_replace('[x]', $count, 'Delete [x] menus that are not assigned to any layout or area?'));
		}

		$triggers = array('cancel' => array(LAN_CANCEL, 'cancel'));

		if($count > 0)
		{
			$triggers = array('confirm' => array(LAN_CONFDELETE, 'confirm')) + $triggers;
		}

		$forms = array(
			'jmmenus-clean' => array(
				'id'        => 'jmmenus-clean',
				'url'       => e_REQUEST_SELF,
				'query'     => 'mode=menus&action=clean',
				'fieldsets' => array(
					'confirm' => array(
						'triggers' => $triggers,
					),
				),
			),
		);

		return $this->getUI()->renderForm($forms);
	}

	public function CleanConfirmTrigger()
	{
		$result = e107::getDb()->createQueryBuilder()
			->delete('menus')
			->where('menu_location', '')
			->where('menu_layout', '')
			->execute();

		if($result === false)
		{
			e107::getMessage()->addError('Deleting not used menus failed', 'default', true);
		}
		elseif($result > 0)
		{
			e107::getMessage()->addSuccess(str_replace('[x]', $result, 'Deleted [x] records of table menus'), 'default', true);
			e107::getLog()->add('Delete all not used menus', str_replace('[x]', $result, 'Deleted [x] records of table menus'), E_LOG_INFORMATIVE, 'JMMENUS_01');
		}
		else
		{
			e107::getMessage()->addInfo('Nothing to delete', 'default', true);
		}

		$this->redirectAction('list', 'id');
	}

	public function CleanCancelTrigger()
	{
		$this->redirectAction('list', 'id');
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

