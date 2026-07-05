<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Module;
/**
 * Class GtrGuardian
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Module
 */
class GtrGuardian extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->model('setting/event');

		/*
		 * Register Events
		 *
		 * The "admin/" prefix is required for the event to fire: admin/controller/startup/event.php only
		 * registers DB events whose trigger starts with "admin/" (prefix stripped before registering) or
		 * "system/" (kept as-is, registers in both areas). Any other prefix is silently never registered.
		 */
		$this->model_setting_event->addEvent([
			'code'        => 'gtr_guardian_column_left',
			'description' => 'Add Item to Column Left',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/gtr_guardian/events.addColumnLeftMenu',
			'status'      => 1,
			'sort_order'  => 1
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('gtr_guardian_column_left');
	}
}
