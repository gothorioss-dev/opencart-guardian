<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Other;
/**
 * Class HealthMonitor
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Other
 */
class HealthMonitor extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_module_gtr_guardian->isCoreInstalled()) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/gtr_guardian/module/gtr_guardian');
		$this->load->language('extension/gtr_guardian/other/health_monitor');

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
			'text' => $this->language->get('text_guardian'),
			'href' => $this->url->link('extension/gtr_guardian/dashboard/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/gtr_guardian/other/health_monitor', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/gtr_guardian/other/health_monitor.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=other');

		$data['other_health_monitor_status'] = $this->config->get('other_health_monitor_status');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_description'] = $this->language->get('text_description');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_back'] = $this->language->get('button_back');

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/other/health_monitor', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/gtr_guardian/other/health_monitor');

		$json = [];

		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_module_gtr_guardian->isCoreInstalled()) {
			$json['error'] = $this->language->get('error_core_required');
		}

		if (!$this->user->hasPermission('modify', 'extension/gtr_guardian/other/health_monitor')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('other_health_monitor', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * Requires Guardian core. If missing, rolls back the oc_extension row
	 * that Extensions → Other already created before calling this method.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->language('extension/gtr_guardian/other/health_monitor');
		$this->load->model('setting/extension');
		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_module_gtr_guardian->isCoreInstalled()) {
			$this->model_setting_extension->uninstall('other', 'health_monitor');

			$this->load->model('user/user_group');

			$group_id = $this->user->getGroupId();

			$this->model_user_user_group->removePermission($group_id, 'access', 'extension/gtr_guardian/other/health_monitor');
			$this->model_user_user_group->removePermission($group_id, 'modify', 'extension/gtr_guardian/other/health_monitor');

			$this->log->write('OpenCart Guardian: Health Monitor install blocked — core module is not installed.');

			return;
		}

		$this->load->model('extension/gtr_guardian/other/health_monitor');

		$this->model_extension_gtr_guardian_other_health_monitor->install();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('extension/gtr_guardian/other/health_monitor');

		$this->model_extension_gtr_guardian_other_health_monitor->uninstall();
	}
}
