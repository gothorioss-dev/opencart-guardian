<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Other;
/**
 * Class GtrGuardian
 *
 * Guardian core — an admin-only diagnostics tool, registered as an "other"
 * extension (it has no storefront/layout presence, so "module" does not fit).
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Other
 */
class GtrGuardian extends \Opencart\System\Engine\Controller {
	/**
	 * Retention presets offered in the settings dropdowns, per limit.
	 */
	private const RETENTION_PRESETS = [
		'runs' => [10, 30, 50, 100],
		'days' => [30, 60, 90]
	];

	/**
	 * Upper bounds for a custom retention value, per limit.
	 */
	private const RETENTION_MAX = [
		'runs' => 1000,
		'days' => 3650
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/gtr_guardian/other/gtr_guardian');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=other')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/gtr_guardian/other/gtr_guardian', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/gtr_guardian/other/gtr_guardian.save', 'user_token=' . $this->session->data['user_token']);
		$data['clear'] = $this->url->link('extension/gtr_guardian/other/gtr_guardian.clear', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=other');
		$data['dashboard'] = $this->url->link('extension/gtr_guardian/guardian/dashboard', 'user_token=' . $this->session->data['user_token']);

		$data['other_gtr_guardian_status'] = $this->config->get('other_gtr_guardian_status');

		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$data['domains'] = [];

		foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getDomainCodes() as $code) {
			$this->load->language('extension/gtr_guardian/guardian/' . $code, $code);

			$data['domains'][] = [
				'code'    => $code,
				'name'    => $this->language->get($code . '_heading_title'),
				'enabled' => $this->model_extension_gtr_guardian_other_gtr_guardian->isDomainEnabled($code),
				'edit'    => $this->url->link('extension/gtr_guardian/guardian/' . $code, 'user_token=' . $this->session->data['user_token'])
			];
		}

		// Matrix columns: the dashboard plus the domains listed above.
		$data['permission_routes'] = [['code' => 'dashboard', 'name' => $this->language->get('text_dashboard')]];

		foreach ($data['domains'] as $domain) {
			$data['permission_routes'][] = ['code' => $domain['code'], 'name' => $domain['name']];
		}

		$routes = $this->model_extension_gtr_guardian_other_gtr_guardian->getPermissionRoutes();

		$data['permission_groups'] = [];

		foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getManagedUserGroups() as $group) {
			$permission = $group['permission'];

			$cells = [];

			foreach ($routes as $code => $route) {
				$cells[$code] = [
					'access' => in_array($route, $permission['access'] ?? [], true),
					'modify' => in_array($route, $permission['modify'] ?? [], true)
				];
			}

			$data['permission_groups'][] = [
				'user_group_id' => (int)$group['user_group_id'],
				'name'          => $group['name'],
				'cells'         => $cells
			];
		}

		$retention = $this->model_extension_gtr_guardian_other_gtr_guardian->getRetention();

		$data['retention'] = [];

		foreach ($retention as $limit => $value) {
			$data['retention'][$limit] = [
				'value'   => $value,
				'presets' => self::RETENTION_PRESETS[$limit],
				'custom'  => $value && !in_array($value, self::RETENTION_PRESETS[$limit], true),
				'entry'   => $this->language->get('entry_retention_' . $limit),
				'help'    => $this->language->get('help_retention_' . $limit),
				'unit'    => $this->language->get('text_' . $limit)
			];
		}

		$data['text_retention_current'] = $this->getRetentionText($retention);

		$this->load->model('extension/gtr_guardian/guardian/result');

		$data['text_history_total'] = sprintf($this->language->get('text_history_total'), $this->model_extension_gtr_guardian_guardian_result->getTotalRuns());

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/other/gtr_guardian', $data));
	}

	/**
	 * Save
	 *
	 * Writes the core status and the per-domain enable flags — both live in the
	 * "other_gtr_guardian" group.
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/gtr_guardian/other/gtr_guardian');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/gtr_guardian/other/gtr_guardian')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$retention = $this->model_extension_gtr_guardian_other_gtr_guardian->getRetention();

		foreach (['runs', 'days'] as $limit) {
			$value = $this->resolveRetention($limit, $retention[$limit]);

			if ($value === null) {
				$json['error']['retention_' . $limit] = sprintf($this->language->get('error_retention'), self::RETENTION_MAX[$limit]);
			} else {
				$retention[$limit] = $value;
			}
		}

		if (!$json) {
			$this->load->model('setting/setting');

			// Only known keys are stored, each cast to what it is: the raw
			// post must never reach oc_setting. A flag that was not submitted
			// keeps its value.
			$settings = $this->model_setting_setting->getSetting('other_gtr_guardian');

			foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getFlagKeys() as $flag) {
				if (isset($this->request->post[$flag])) {
					$settings[$flag] = (int)!empty($this->request->post[$flag]);
				}
			}

			foreach ($retention as $limit => $value) {
				$settings['other_gtr_guardian_retention_' . $limit] = $value;
			}

			$this->model_setting_setting->editSetting('other_gtr_guardian', $settings);

			// The full form always posts the marker; it guards against a partial
			// request revoking every group's access because "permission" is
			// simply absent. With the marker, absent cells are revocations.
			if (isset($this->request->post['permission_matrix'])) {
				$permission = $this->request->post['permission'] ?? [];

				$this->model_extension_gtr_guardian_other_gtr_guardian->savePermissions(is_array($permission) ? $permission : []);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Clear
	 *
	 * Deletes the whole run history of every domain.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->load->language('extension/gtr_guardian/other/gtr_guardian');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/gtr_guardian/other/gtr_guardian')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/gtr_guardian/guardian/result');
			$this->load->model('extension/gtr_guardian/guardian/runner');

			$this->model_extension_gtr_guardian_guardian_result->clear();
			$this->model_extension_gtr_guardian_guardian_runner->clearSummary();

			$json['success'] = $this->language->get('text_clear_success');
			$json['total'] = sprintf($this->language->get('text_history_total'), 0);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$this->model_extension_gtr_guardian_other_gtr_guardian->install();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$this->model_extension_gtr_guardian_other_gtr_guardian->uninstall();
	}

	/**
	 * Resolve a posted retention limit (preset select + custom input).
	 *
	 * @param string $limit   "runs" or "days"
	 * @param int    $current value kept when the field was not submitted
	 *
	 * @return int|null null when the submitted value is invalid
	 */
	private function resolveRetention(string $limit, int $current): ?int {
		$select = $this->request->post['other_gtr_guardian_retention_' . $limit . '_select'] ?? null;

		if ($select === null) {
			return $current;
		}

		if ($select === 'custom') {
			$select = $this->request->post['other_gtr_guardian_retention_' . $limit . '_custom'] ?? '';
		}

		// Digits only: rejects arrays, signs, exponents and anything non-numeric.
		if (!is_string($select) || !preg_match('/^[0-9]{1,10}$/', $select)) {
			return null;
		}

		$value = (int)$select;

		return $value <= self::RETENTION_MAX[$limit] ? $value : null;
	}

	/**
	 * Human-readable description of the effective retention rule.
	 *
	 * @param array{runs: int, days: int} $retention
	 *
	 * @return string
	 */
	private function getRetentionText(array $retention): string {
		if ($retention['runs'] && $retention['days']) {
			return sprintf($this->language->get('text_retention_current_both'), $retention['runs'], $retention['days']);
		}

		if ($retention['runs']) {
			return sprintf($this->language->get('text_retention_current_runs'), $retention['runs']);
		}

		if ($retention['days']) {
			return sprintf($this->language->get('text_retention_current_days'), $retention['days']);
		}

		return $this->language->get('text_retention_current_none');
	}
}
