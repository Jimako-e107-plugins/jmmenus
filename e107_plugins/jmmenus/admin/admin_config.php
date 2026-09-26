<?php

require_once('../../../class2.php');
if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

e107::lan('jmmenus', true, true);

require_once(e_PLUGIN.'jmmenus/admin/admin_menu.php');

class menus_ui extends e_admin_ui
{
	protected $pluginTitle   = LAN_JMMENUS_NAME;
	protected $pluginName    = 'jmmenus';
	protected $table         = 'menus';
	protected $pid           = 'menu_id';
	protected $perPage       = 50;
	protected $batchDelete   = true;
	protected $batchExport   = true;
	protected $batchCopy     = true;
	protected $listOrder     = 'menu_id DESC';

	protected $fields = array(
		'checkboxes'    => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect', 'readParms' => array(), 'writeParms' => array()),
		'menu_id'       => array('title' => LAN_ID, 'data' => 'int', 'width' => '5%', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_name'     => array('title' => LAN_TITLE, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'inline' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_layout'   => array('title' => LAN_JMMENUS_LAYOUT, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'filter' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'batch' => false),
		'menu_location' => array('title' => LAN_JMMENUS_LOCATION, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'filter' => true, 'inline' => false, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'batch' => false),
		'menu_order'    => array('title' => LAN_ORDER, 'type' => 'number', 'data' => 'int', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_class'    => array('title' => LAN_USERCLASS, 'type' => 'userclass', 'data' => 'str', 'width' => 'auto', 'batch' => true, 'filter' => true, 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_pages'    => array('title' => LAN_JMMENUS_PAGES, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_path'     => array('title' => LAN_JMMENUS_PATH, 'type' => 'text', 'data' => 'str', 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left'),
		'menu_parms'    => array('title' => LAN_JMMENUS_PARMS, 'type' => 'textarea', 'data' => false, 'width' => 'auto', 'help' => '', 'readParms' => array(), 'writeParms' => array(), 'class' => 'left', 'thclass' => 'left', 'filter' => false, 'batch' => false),
		'options'       => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null, 'width' => '10%', 'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value', 'readParms' => array(), 'writeParms' => array()),
	);

	protected $fieldpref = array('menu_name', 'menu_location', 'menu_class', 'menu_layout');

	protected $prefs = array();

	protected $menuParms = null;

	public function init()
	{
		$this->postFilterMarkup = $this->DeleteMenusButton();
	}

	public function beforeCreate($new_data, $old_data)
	{
		return $this->checkMenuParms($new_data, $old_data);
	}

	public function afterCreate($new_data, $old_data, $id)
	{
		$this->saveMenuParms($id);
	}

	public function beforeUpdate($new_data, $old_data, $id)
	{
		return $this->checkMenuParms($new_data, $old_data);
	}

	public function afterUpdate($new_data, $old_data, $id)
	{
		$this->saveMenuParms($id);
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
			e107::getMessage()->addError(LAN_JMMENUS_PARMS_INVALID);
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
			e107::getMessage()->addSuccess(LAN_JMMENUS_PARMS_SAVED);
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

	public function renderHelp()
	{
		return array('caption' => LAN_HELP, 'text' => LAN_JMMENUS_HELP);
	}

	public function DeleteMenusButton()
	{
		$url = e_REQUEST_SELF.'?mode=menus&amp;action=clean';

		return "<a class='btn btn-danger' href='".$url."'>".LAN_JMMENUS_CLEAN_BUTTON."</a>";
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
			e107::getMessage()->addInfo(LAN_JMMENUS_CLEAN_NOTHING);
		}
		else
		{
			e107::getMessage()->addWarning(str_replace('[x]', $count, LAN_JMMENUS_CLEAN_CONFIRM));
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
			e107::getMessage()->addError(LAN_JMMENUS_CLEAN_FAILED, 'default', true);
		}
		elseif($result > 0)
		{
			$message = str_replace('[x]', $result, LAN_JMMENUS_CLEAN_DONE);
			e107::getMessage()->addSuccess($message, 'default', true);
			e107::getLog()->add(LAN_JMMENUS_LOG_CLEAN, $message, E_LOG_INFORMATIVE, 'JMMENUS_01');
		}
		else
		{
			e107::getMessage()->addInfo(LAN_JMMENUS_CLEAN_NOTHING, 'default', true);
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
