<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Other;
/**
 * Class GtrGuardian
 *
 * Guardian core model: install/uninstall, domain discovery, enable state and
 * the upgrade reconcile (sync).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Other
 */
class GtrGuardian extends \Opencart\System\Engine\Model {
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
	 * An unknown key resolves to enabled, so a domain added by an update is not
	 * silently hidden — disabling is always an explicit admin action.
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
	 * Reconcile shipped domains with what was recorded on the last run.
	 *
	 * Runs on core install and, for file-only deployments, from the
	 * gtr_guardian_reconcile event. Cheap guard exits before any DB write when
	 * neither the package version nor the domain set has changed.
	 *
	 * @return void
	 */
	public function sync(): void {
		$shipped    = $this->getPackageVersion();
		$discovered = $this->getDomainCodes();

		$version  = (string)$this->config->get('gtr_guardian_version');
		$manifest = $this->config->get('gtr_guardian_manifest');
		$manifest = is_array($manifest) ? $manifest : [];

		$known = array_keys($manifest);
		sort($known);

		if ($version === $shipped && $known === $discovered) {
			return;
		}

		$this->load->model('setting/setting');
		$this->load->model('setting/cron');

		$added   = array_values(array_diff($discovered, $known));
		$removed = array_values(array_diff($known, $discovered));

		$new_manifest = [];

		foreach ($discovered as $code) {
			// Schema + cron are idempotent — safe to re-assert on every bump.
			$this->assertDomainArtefacts($code);

			// Permissions are not idempotent — grant only for new domains.
			if (in_array($code, $added, true)) {
				$this->addDomainPermissions($code);
			}

			$new_manifest[$code] = $this->domainSnapshot($code);
		}

		foreach ($removed as $code) {
			$snapshot = isset($manifest[$code]) && is_array($manifest[$code]) ? $manifest[$code] : ['tables' => [], 'cron' => []];

			$this->rollbackDomain($code, $snapshot);
		}

		$this->syncDomainFlags($added, $removed);

		$this->model_setting_setting->editSetting('gtr_guardian', [
			'gtr_guardian_version'  => $shipped,
			'gtr_guardian_manifest' => $new_manifest
		]);
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
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

		$this->model_setting_event->deleteEventByCode('gtr_guardian_reconcile');

		$this->model_setting_event->addEvent([
			'code'        => 'gtr_guardian_reconcile',
			'description' => 'Reconcile OpenCart Guardian domains after a file-only update',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/gtr_guardian/events.reconcile',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();

		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/gtr_guardian/guardian/dashboard');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/gtr_guardian/guardian/dashboard');

		// Fresh install: nothing recorded yet, so this runs a full reconcile
		// (registers every domain's artefacts and permissions).
		$this->sync();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('setting/event');
		$this->load->model('setting/setting');
		$this->load->model('setting/cron');

		$this->model_setting_event->deleteEventByCode('gtr_guardian_column_left');
		$this->model_setting_event->deleteEventByCode('gtr_guardian_reconcile');

		$manifest = $this->config->get('gtr_guardian_manifest');

		if (is_array($manifest)) {
			foreach ($manifest as $code => $snapshot) {
				$this->rollbackDomain((string)$code, is_array($snapshot) ? $snapshot : ['tables' => [], 'cron' => []]);
			}
		}

		$this->model_setting_setting->deleteSettingsByCode('gtr_guardian');
		$this->model_setting_setting->deleteSettingsByCode('other_gtr_guardian');
	}

	/**
	 * Package version from install.json (statically cached per request).
	 *
	 * @return string
	 */
	private function getPackageVersion(): string {
		static $version = null;

		if ($version === null) {
			$version = '';

			$file = DIR_EXTENSION . 'gtr_guardian/install.json';

			if (is_file($file)) {
				$info = json_decode((string)file_get_contents($file), true);

				$version = isset($info['version']) ? (string)$info['version'] : '';
			}
		}

		return $version;
	}

	/**
	 * @param string $code
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleProvider
	 */
	private function loadProvider(string $code) {
		$this->load->model('extension/gtr_guardian/guardian/domain/' . $code);

		return $this->{'model_extension_gtr_guardian_guardian_domain_' . $code};
	}

	/**
	 * Idempotently create the domain's tables and cron jobs.
	 *
	 * @param string $code
	 *
	 * @return void
	 */
	private function assertDomainArtefacts(string $code): void {
		$provider = $this->loadProvider($code);

		foreach ($provider->schema() as $sql) {
			$this->db->query($sql);
		}

		foreach ($provider->cronJobs() as $job) {
			if (!$this->model_setting_cron->getCronByCode($job['code'])) {
				$this->model_setting_cron->addCron(
					$job['code'],
					$job['description'] ?? '',
					$job['cycle'] ?? 'week',
					$job['action'] ?? '',
					(bool)($job['status'] ?? true)
				);
			}
		}
	}

	/**
	 * Snapshot of what a domain owns, stored in the manifest for later rollback.
	 *
	 * @param string $code
	 *
	 * @return array{tables: array<int, string>, cron: array<int, string>}
	 */
	private function domainSnapshot(string $code): array {
		$provider = $this->loadProvider($code);

		return [
			'tables' => array_keys($provider->schema()),
			'cron'   => array_column($provider->cronJobs(), 'code')
		];
	}

	/**
	 * @param string                                                          $code
	 * @param array{tables?: array<int, string>, cron?: array<int, string>}    $snapshot
	 *
	 * @return void
	 */
	private function rollbackDomain(string $code, array $snapshot): void {
		foreach ($snapshot['tables'] ?? [] as $table) {
			$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . $table . "`");
		}

		foreach ($snapshot['cron'] ?? [] as $cron_code) {
			$this->model_setting_cron->deleteCronByCode($cron_code);
		}

		$this->removeDomainPermissions($code);
	}

	/**
	 * @param string $code
	 *
	 * @return void
	 */
	private function addDomainPermissions(string $code): void {
		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();
		$route = 'extension/gtr_guardian/guardian/' . $code;

		$this->model_user_user_group->addPermission($group_id, 'access', $route);
		$this->model_user_user_group->addPermission($group_id, 'modify', $route);
	}

	/**
	 * @param string $code
	 *
	 * @return void
	 */
	private function removeDomainPermissions(string $code): void {
		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();
		$route = 'extension/gtr_guardian/guardian/' . $code;

		$this->model_user_user_group->removePermission($group_id, 'access', $route);
		$this->model_user_user_group->removePermission($group_id, 'modify', $route);
	}

	/**
	 * Default a new domain's enable flag to on; drop the flag of a removed one.
	 *
	 * @param array<int, string> $added
	 * @param array<int, string> $removed
	 *
	 * @return void
	 */
	private function syncDomainFlags(array $added, array $removed): void {
		$settings = $this->model_setting_setting->getSetting('other_gtr_guardian');

		$changed = false;

		foreach ($added as $code) {
			$key = 'other_gtr_guardian_domain_' . $code;

			if (!isset($settings[$key])) {
				$settings[$key] = '1';
				$changed = true;
			}
		}

		foreach ($removed as $code) {
			$key = 'other_gtr_guardian_domain_' . $code;

			if (isset($settings[$key])) {
				unset($settings[$key]);
				$changed = true;
			}
		}

		if ($changed) {
			$this->model_setting_setting->editSetting('other_gtr_guardian', $settings);
		}
	}
}
