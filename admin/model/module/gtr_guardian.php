<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Module;
/**
 * Class GtrGuardian
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Module
 */
class GtrGuardian extends \Opencart\System\Engine\Model {
	/**
	 * Known Guardian other-submodule codes shipped in this package.
	 *
	 * @return array<int, string>
	 */
	public function getSubmoduleCodes(): array {
		$codes = [];

		$files = glob(DIR_EXTENSION . 'gtr_guardian/admin/controller/other/*.php');

		if ($files) {
			foreach ($files as $file) {
				$codes[] = basename($file, '.php');
			}
		}

		sort($codes);

		return $codes;
	}

	/**
	 * Whether Guardian core module is installed.
	 *
	 * @return bool
	 */
	public function isCoreInstalled(): bool {
		$this->load->model('setting/extension');

		return !empty($this->model_setting_extension->getExtensionByCode('module', 'gtr_guardian'));
	}

	/**
	 * Whether a Guardian other-submodule is installed.
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	public function isSubmoduleInstalled(string $code): bool {
		$this->load->model('setting/extension');

		$extension = $this->model_setting_extension->getExtensionByCode('other', $code);

		return !empty($extension['extension']) && $extension['extension'] === 'gtr_guardian';
	}

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
		$this->model_setting_event->deleteEventByCode('gtr_guardian_column_left');

		$this->model_setting_event->addEvent([
			'code'        => 'gtr_guardian_column_left',
			'description' => 'Add OpenCart Guardian menu to Column Left',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/gtr_guardian/events.addColumnLeftMenu',
			'status'      => 1,
			'sort_order'  => 1
		]);
	}

	/**
	 * Uninstall core and cascade-remove package other-submodules.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('setting/event');
		$this->load->model('setting/extension');

		$this->model_setting_event->deleteEventByCode('gtr_guardian_column_left');

		foreach ($this->getSubmoduleCodes() as $code) {
			if ($this->isSubmoduleInstalled($code)) {
				$this->load->controller('extension/gtr_guardian/other/' . $code . '.uninstall');
				$this->model_setting_extension->uninstall('other', $code);
			}
		}
	}
}
