<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Dashboard;
/**
 * Class Dashboard
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Dashboard
 */
class Dashboard extends \Opencart\System\Engine\Controller {
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
		$this->load->language('extension/gtr_guardian/dashboard/dashboard');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_guardian'),
			'href' => $this->url->link('extension/gtr_guardian/dashboard/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/gtr_guardian/dashboard/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_description'] = $this->language->get('text_description');
		$data['settings'] = $this->url->link('extension/gtr_guardian/module/gtr_guardian', 'user_token=' . $this->session->data['user_token']);
		$data['text_settings'] = $this->language->get('text_settings');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/dashboard/dashboard', $data));
	}
}
