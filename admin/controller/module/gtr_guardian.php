<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Module;
/**
 * Class GtrGuardian
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Module
 */
class GtrGuardian extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/gtr_guardian/module/gtr_guardian');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/gtr_guardian/module/gtr_guardian', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/gtr_guardian/module/gtr_guardian.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['dashboard'] = $this->url->link('extension/gtr_guardian/dashboard/dashboard', 'user_token=' . $this->session->data['user_token']);
		$data['other'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=other');

		$data['module_gtr_guardian_status'] = $this->config->get('module_gtr_guardian_status');

		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		$data['submodules'] = [];

		foreach ($this->model_extension_gtr_guardian_module_gtr_guardian->getSubmoduleCodes() as $code) {
			$this->load->language('extension/gtr_guardian/other/' . $code, $code);

			$installed = $this->model_extension_gtr_guardian_module_gtr_guardian->isSubmoduleInstalled($code);

			$data['submodules'][] = [
				'code'      => $code,
				'name'      => $this->language->get($code . '_heading_title'),
				'installed' => $installed,
				'edit'      => $installed ? $this->url->link('extension/gtr_guardian/other/' . $code, 'user_token=' . $this->session->data['user_token']) : '',
				'install'   => $this->url->link('extension/other.install', 'user_token=' . $this->session->data['user_token'] . '&extension=gtr_guardian&code=' . $code),
				'uninstall' => $this->url->link('extension/other.uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=gtr_guardian&code=' . $code)
			];
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_submodules'] = $this->language->get('text_submodules');
		$data['text_submodules_help'] = $this->language->get('text_submodules_help');
		$data['text_installed'] = $this->language->get('text_installed');
		$data['text_not_installed'] = $this->language->get('text_not_installed');
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_install'] = $this->language->get('button_install');
		$data['button_uninstall'] = $this->language->get('button_uninstall');
		$data['button_edit'] = $this->language->get('button_edit');

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/module/gtr_guardian', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/gtr_guardian/module/gtr_guardian');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/gtr_guardian/module/gtr_guardian')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_gtr_guardian', $this->request->post);

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
		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		$this->model_extension_gtr_guardian_module_gtr_guardian->install();

		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();

		foreach ([
			'extension/gtr_guardian/dashboard/dashboard',
		] as $route) {
			$this->model_user_user_group->addPermission($group_id, 'access', $route);
			$this->model_user_user_group->addPermission($group_id, 'modify', $route);
		}
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		$this->model_extension_gtr_guardian_module_gtr_guardian->uninstall();
	}
}
