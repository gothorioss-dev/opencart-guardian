<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Other;
/**
 * Class GtrGuardian
 *
 * Guardian core model: install/uninstall, domain discovery and enable state.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Other
 */
class GtrGuardian extends \Opencart\System\Engine\Model {
	public const RETENTION_RUNS_DEFAULT = 30;
	public const RETENTION_DAYS_DEFAULT = 30;

	/**
	 * Whether the Guardian core extension is installed.
	 *
	 * @return bool
	 */
	public function isCoreInstalled(): bool {
		$this->load->model('setting/extension');

		return !empty($this->model_setting_extension->getExtensionByCode('other', 'gtr_guardian'));
	}

	/**
	 * Whether Guardian is installed and switched on. Every Guardian screen and
	 * the menu are gated on this; the settings screen itself is not.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->isCoreInstalled() && (bool)$this->config->get('other_gtr_guardian_status');
	}

	/**
	 * Domain codes shipped in this package (one provider model per domain).
	 *
	 * @return array<int, string>
	 */
	public function getDomainCodes(): array {
		$codes = [];

		foreach ((array)glob(DIR_EXTENSION . 'gtr_guardian/admin/model/guardian/domain/*.php') as $file) {
			$codes[] = basename($file, '.php');
		}

		sort($codes);

		return $codes;
	}

	/**
	 * Whether a domain is enabled.
	 *
	 * An unknown key resolves to enabled — disabling is always an explicit
	 * admin action.
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	public function isDomainEnabled(string $code): bool {
		$value = $this->config->get('other_gtr_guardian_domain_' . $code);

		return $value === null ? true : (bool)$value;
	}

	/**
	 * @return array<int, string>
	 */
	public function getEnabledDomainCodes(): array {
		return array_values(array_filter($this->getDomainCodes(), function (string $code): bool {
			return $this->isDomainEnabled($code);
		}));
	}

	/**
	 * Functional Guardian screens managed by the permissions matrix, keyed by
	 * a short code. The settings screen itself is deliberately absent: who may
	 * edit Guardian settings (and thus these permissions) is decided in
	 * System > Users > User Groups like for any other extension.
	 *
	 * @return array<string, string>
	 */
	public function getPermissionRoutes(): array {
		$routes = [
			'dashboard' => 'extension/gtr_guardian/guardian/dashboard'
		];

		foreach ($this->getDomainCodes() as $code) {
			$routes[$code] = 'extension/gtr_guardian/guardian/' . $code;
		}

		return $routes;
	}

	/**
	 * Apply the permissions matrix: $permission[user_group_id][code][access|modify] = 1.
	 *
	 * Groups or cells absent from the array are revoked.
	 *
	 * @param array<int|string, array<string, array<string, mixed>>> $permission
	 *
	 * @return void
	 */
	public function savePermissions(array $permission): void {
		$this->load->model('user/user_group');

		$routes = $this->getPermissionRoutes();

		foreach ($this->model_user_user_group->getUserGroups() as $group) {
			$group_id = (int)$group['user_group_id'];

			$current = $group['permission'] ? (array)json_decode($group['permission'], true) : [];

			foreach ($routes as $code => $route) {
				foreach (['access', 'modify'] as $type) {
					$granted = !empty($permission[$group_id][$code][$type]);

					$has = in_array($route, $current[$type] ?? [], true);

					if ($granted && !$has) {
						$this->model_user_user_group->addPermission($group_id, $type, $route);
					} elseif (!$granted && $has) {
						$this->model_user_user_group->removePermission($group_id, $type, $route);
					}
				}
			}
		}
	}

	/**
	 * Run-history retention limits: newest runs to keep per domain and max
	 * age in days. 0 disables that limit.
	 *
	 * @return array{runs: int, days: int}
	 */
	public function getRetention(): array {
		$runs = $this->config->get('other_gtr_guardian_retention_runs');
		$days = $this->config->get('other_gtr_guardian_retention_days');

		return [
			'runs' => $runs === null ? self::RETENTION_RUNS_DEFAULT : max(0, (int)$runs),
			'days' => $days === null ? self::RETENTION_DAYS_DEFAULT : max(0, (int)$days)
		];
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->model('extension/gtr_guardian/guardian/result');

		$this->model_extension_gtr_guardian_guardian_result->createTables();

		$this->load->model('setting/event');

		/*
		 * The "admin/" trigger prefix is required: admin/controller/startup/event.php
		 * only registers DB events whose trigger starts with "admin/" (prefix stripped)
		 * or "system/". Any other prefix is silently never registered.
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

		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();

		foreach ($this->getRoutes() as $route) {
			$this->model_user_user_group->addPermission($group_id, 'access', $route);
			$this->model_user_user_group->addPermission($group_id, 'modify', $route);
		}

		// Everything is on out of the box; the admin opts out explicitly.
		$settings = ['other_gtr_guardian_status' => 1];

		foreach ($this->getDomainCodes() as $code) {
			$settings['other_gtr_guardian_domain_' . $code] = 1;
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('other_gtr_guardian', $settings);
	}

	/**
	 * Uninstall
	 *
	 * Removes everything the package created, including all stored data.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('setting/event');
		$this->load->model('setting/setting');
		$this->load->model('user/user_group');

		$this->model_setting_event->deleteEventByCode('gtr_guardian_column_left');

		$group_id = $this->user->getGroupId();

		foreach ($this->getRoutes() as $route) {
			$this->model_user_user_group->removePermission($group_id, 'access', $route);
			$this->model_user_user_group->removePermission($group_id, 'modify', $route);
		}

		$this->model_setting_setting->deleteSettingsByCode('other_gtr_guardian');
		$this->model_setting_setting->deleteSettingsByCode('gtr_guardian');

		$this->load->model('extension/gtr_guardian/guardian/result');

		$this->model_extension_gtr_guardian_guardian_result->dropTables();
	}

	/**
	 * Admin routes the package guards with access/modify permissions.
	 *
	 * @return array<int, string>
	 */
	private function getRoutes(): array {
		$routes = ['extension/gtr_guardian/guardian/dashboard'];

		foreach ($this->getDomainCodes() as $code) {
			$routes[] = 'extension/gtr_guardian/guardian/' . $code;
		}

		return $routes;
	}
}
