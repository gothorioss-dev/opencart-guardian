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
	 * Retention presets offered in the settings dropdowns.
	 */
	private const RETENTION_RUNS_PRESETS = [10, 30, 50, 100];
	private const RETENTION_DAYS_PRESETS = [30, 60, 90];

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

		$retention = $this->model_extension_gtr_guardian_other_gtr_guardian->getRetention();

		$data['retention_runs'] = $retention['runs'];
		$data['retention_runs_presets'] = self::RETENTION_RUNS_PRESETS;
		$data['retention_runs_custom'] = $retention['runs'] && !in_array($retention['runs'], self::RETENTION_RUNS_PRESETS, true);

		$data['retention_days'] = $retention['days'];
		$data['retention_days_presets'] = self::RETENTION_DAYS_PRESETS;
		$data['retention_days_custom'] = $retention['days'] && !in_array($retention['days'], self::RETENTION_DAYS_PRESETS, true);

		$data['text_retention_current'] = $this->getRetentionText($retention);

		$this->load->model('extension/gtr_guardian/guardian/result');

		$data['text_history_total'] = sprintf($this->language->get('text_history_total'), $this->model_extension_gtr_guardian_guardian_result->getTotalRuns());

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_history'] = $this->language->get('text_history');
		$data['text_history_help'] = $this->language->get('text_history_help');
		$data['text_retention_off'] = $this->language->get('text_retention_off');
		$data['text_retention_custom'] = $this->language->get('text_retention_custom');
		$data['text_runs'] = $this->language->get('text_runs');
		$data['text_days'] = $this->language->get('text_days');
		$data['text_clear_confirm'] = $this->language->get('text_clear_confirm');
		$data['entry_retention_runs'] = $this->language->get('entry_retention_runs');
		$data['entry_retention_days'] = $this->language->get('entry_retention_days');
		$data['help_retention_runs'] = $this->language->get('help_retention_runs');
		$data['help_retention_days'] = $this->language->get('help_retention_days');
		$data['text_domains'] = $this->language->get('text_domains');
		$data['text_domains_help'] = $this->language->get('text_domains_help');
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['column_domain'] = $this->language->get('column_domain');
		$data['column_enabled'] = $this->language->get('column_enabled');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_clear'] = $this->language->get('button_clear');

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
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$settings = $this->model_setting_setting->getSetting('other_gtr_guardian');

			foreach ($this->request->post as $key => $value) {
				if (str_starts_with($key, 'other_gtr_guardian') && !str_ends_with($key, '_select') && !str_ends_with($key, '_custom')) {
					$settings[$key] = $value;
				}
			}

			// Each retention limit is posted as a preset select plus a custom
			// number input; only the resolved integer is stored.
			foreach (['runs', 'days'] as $limit) {
				$settings['other_gtr_guardian_retention_' . $limit] = $this->resolveRetention($limit);
			}

			$this->model_setting_setting->editSetting('other_gtr_guardian', $settings);

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
	 * Resolve a posted retention limit (select + custom input) to an integer.
	 *
	 * @param string $limit "runs" or "days"
	 *
	 * @return int
	 */
	private function resolveRetention(string $limit): int {
		$select = (string)($this->request->post['other_gtr_guardian_retention_' . $limit . '_select'] ?? '');

		if ($select === 'custom') {
			$value = (int)($this->request->post['other_gtr_guardian_retention_' . $limit . '_custom'] ?? 0);
		} else {
			$value = (int)$select;
		}

		return max(0, $value);
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
