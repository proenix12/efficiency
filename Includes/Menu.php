<?php

namespace Includes;

use Includes\UserResultsTable;

class Menu
{
	public function __construct()
	{
		add_action('admin_menu', [$this, 'addMenus']);
	}

	public function initMenus(): array
	{
		return [
			'efficiency' => [
				'page_title' => 'efficiency-plugin',
				'menu_title' => 'Efficiency',
				'menu_slug'  => plugin_url,
				'capability' => 'administrator',
				'function'   => [$this, 'adminDashboard'],
				'icon_url'   => '',
				'priority'   => 99
			]
		];
	}

	public function initSubMenus(): array
	{
		return [
			'efficiency-subpage' => [
				'parent_slug' => plugin_url,
				'page_title'  => 'Math',
				'menu_title'  => 'Math',
				'capability'  => 'administrator',
				'menu_slug'   => plugin_url . '-subpage',
				'function'    => [$this, 'adminSubpage'],
			]
		];
	}

	public function initAdminUsersResults(): array
	{
		return [
			'efficiency-subpage' => [
				'parent_slug' => plugin_url,
				'page_title'  => 'Users Results',
				'menu_title'  => 'Users Results',
				'capability'  => 'administrator',
				'menu_slug'   => plugin_url . '-users-results',
				'function'    => [$this, 'adminUsersResults'],
			]
		];
	}

	public function adminDashboard()
	{
		return require_once(efficiency_plugin_dir . "/pages/home.php");
	}

	public function adminSubpage()
	{
		return require_once(efficiency_plugin_dir . "/pages/subpage.php");
	}

	public function adminUsersResults()
	{
		return require_once(efficiency_plugin_dir . "/pages/user_results.php");
	}

	public function addMenus(): void
	{
		foreach ($this->initMenus() as $slug => $menu) {
			add_menu_page(
				$menu['page_title'],
				$menu['menu_title'],
				$menu['capability'],
				$menu['menu_slug'],
				$menu['function'],
				$menu['icon_url'],
				$menu['priority']
			);
		}

		foreach ($this->initSubMenus() as $slug => $submenu) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}


		foreach ($this->initAdminUsersResults() as $slug => $submenu) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}
	}
}