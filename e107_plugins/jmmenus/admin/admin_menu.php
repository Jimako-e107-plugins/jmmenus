<?php

if (!defined('e107_INIT')) { exit; }

class jmmenus_adminArea extends e_admin_dispatcher
{
	protected $modes = array(
		'menus' => array(
			'controller' => 'menus_ui',
			'path'       => null,
			'ui'         => 'menus_form_ui',
			'uipath'     => null,
			'perm'       => 'P',
		),
	);

	protected $adminMenu = array(
		'menus/list' => array('caption' => LAN_MANAGE, 'perm' => 'P'),
	);

	protected $adminMenuAliases = array(
		'menus/edit'  => 'menus/list',
		'menus/clean' => 'menus/list',
	);

	protected $pageTitles = array(
		'menus/clean' => LAN_JMMENUS_CLEAN_BUTTON,
	);

	protected $menuTitle = LAN_JMMENUS_NAME;
}
