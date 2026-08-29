<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;
/**
 * Class Dashboard
 *
 * Guardian dashboard: one-row-per-domain health summary built by polling
 * every installed domain.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
class Dashboard extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_other_gtr_guardian->isCoreInstalled()) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/gtr_guardian/other/gtr_guardian');
		$this->load->language('extension/gtr_guardian/guardian/dashboard');

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
			'href' => $this->url->link('extension/gtr_guardian/guardian/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['settings'] = $this->url->link('extension/gtr_guardian/other/gtr_guardian', 'user_token=' . $this->session->data['user_token']);

		$data['domains'] = $this->getReports();

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_overview'] = $this->language->get('text_overview');
		$data['text_no_domains'] = $this->language->get('text_no_domains');
		$data['text_no_data'] = $this->language->get('text_no_data');
		$data['text_never'] = $this->language->get('text_never');
		$data['text_status_ok'] = $this->language->get('text_status_ok');
		$data['text_status_warning'] = $this->language->get('text_status_warning');
		$data['text_status_critical'] = $this->language->get('text_status_critical');
		$data['text_status_unknown'] = $this->language->get('text_status_unknown');
		$data['text_status_error'] = $this->language->get('text_status_error');
		$data['column_domain'] = $this->language->get('column_domain');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_summary'] = $this->language->get('column_summary');
		$data['column_findings'] = $this->language->get('column_findings');
		$data['column_last_run'] = $this->language->get('column_last_run');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_open'] = $this->language->get('button_open');

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/guardian/dashboard', $data));
	}

	/**
	 * Build the per-domain rows for the summary table.
	 *
	 * Enriches each raw report with a localised name and an admin link — the
	 * two things a domain provider deliberately does not know about.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function getReports(): array {
		$this->load->model('extension/gtr_guardian/guardian/dashboard');

		$rows = [];

		foreach ($this->model_extension_gtr_guardian_guardian_dashboard->collectReports() as $report) {
			$code = $report['code'];

			$this->load->language('extension/gtr_guardian/guardian/' . $code, $code);

			$rows[] = $report + [
				'name'          => $this->language->get($code . '_heading_title'),
				'href'          => $this->url->link('extension/gtr_guardian/guardian/' . $code, 'user_token=' . $this->session->data['user_token']),
				'last_run_text' => $report['last_run'] ? date('Y-m-d H:i', $report['last_run']) : $this->language->get('text_never')
			];
		}

		return $rows;
	}
}
