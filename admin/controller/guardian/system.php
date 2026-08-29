<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;
/**
 * Class System
 *
 * Guardian domain A screen: system data quality.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
class System extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		if (
			!$this->model_extension_gtr_guardian_other_gtr_guardian->isCoreInstalled()
			|| !$this->model_extension_gtr_guardian_other_gtr_guardian->isDomainEnabled('system')
		) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/gtr_guardian/other/gtr_guardian');
		$this->load->language('extension/gtr_guardian/guardian/system');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_guardian'),
			'href' => $this->url->link('extension/gtr_guardian/guardian/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/gtr_guardian/guardian/system', 'user_token=' . $this->session->data['user_token'])
		];

		$data['back'] = $this->url->link('extension/gtr_guardian/guardian/dashboard', 'user_token=' . $this->session->data['user_token']);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_description'] = $this->language->get('text_description');
		$data['text_scaffold'] = $this->language->get('text_scaffold');
		$data['button_back'] = $this->language->get('button_back');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/guardian/system', $data));
	}
}
