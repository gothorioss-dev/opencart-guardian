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

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
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
	 * "other_gtr_guardian" group. The internal "gtr_guardian" state group
	 * (sync version + manifest) is never touched here.
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
				if (str_starts_with($key, 'other_gtr_guardian')) {
					$settings[$key] = $value;
				}
			}

			$this->model_setting_setting->editSetting('other_gtr_guardian', $settings);

			$json['success'] = $this->language->get('text_success');
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
}
