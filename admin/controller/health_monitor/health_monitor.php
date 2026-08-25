<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\HealthMonitor;
/**
 * Class HealthMonitor
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\HealthMonitor
 */
class HealthMonitor extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/gtr_guardian/module/gtr_guardian');
		$this->load->language('extension/gtr_guardian/health_monitor/health_monitor');

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
			'href' => $this->url->link('extension/gtr_guardian/health_monitor/health_monitor', 'user_token=' . $this->session->data['user_token'])
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_description'] = $this->language->get('text_description');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/health_monitor/health_monitor', $data));
	}
}
