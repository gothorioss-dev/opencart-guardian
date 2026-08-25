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

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/gtr_guardian/module/gtr_guardian', 'user_token=' . $this->session->data['user_token'])
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/gtr_guardian/module/gtr_guardian', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'])
			];
		}

		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_hello_world'] = $this->language->get('text_hello_world');

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/module/gtr_guardian', $data));
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
			'extension/gtr_guardian/health_monitor/health_monitor',
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
