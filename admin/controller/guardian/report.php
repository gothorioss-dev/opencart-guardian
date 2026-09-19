<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;
/**
 * Class Report
 *
 * Shared check-results block and run action used by every domain screen.
 * Never dispatched by route: domain controllers call it through
 * $this->load->controller(...) so permissions stay on the domain routes.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
class Report extends \Opencart\System\Engine\Controller {
	/**
	 * Render the latest run of a domain as an HTML block.
	 *
	 * @param string $domain
	 *
	 * @return string
	 */
	public function index(string $domain = ''): string {
		if (!$this->isKnownDomain($domain)) {
			return '';
		}

		$this->load->language('extension/gtr_guardian/guardian/report');

		$this->load->model('extension/gtr_guardian/guardian/domain/' . $domain);
		$this->load->model('extension/gtr_guardian/guardian/runner');
		$this->load->model('extension/gtr_guardian/guardian/result');

		$provider = $this->{'model_extension_gtr_guardian_guardian_domain_' . $domain};

		$run = $this->model_extension_gtr_guardian_guardian_result->getLatestRun($domain);

		$data['run'] = [];

		$results = [];

		if ($run) {
			$data['run'] = [
				'date_finished' => date($this->language->get('datetime_format'), strtotime($run['date_finished'])),
				'origin'        => $this->language->get('text_origin_' . $run['origin']),
				'checks_total'  => (int)$run['checks_total'],
				'critical'      => (int)$run['critical'],
				'warning'       => (int)$run['warning'],
				'info'          => (int)$run['info'],
				'errors'        => (int)$run['errors']
			];

			foreach ($this->model_extension_gtr_guardian_guardian_result->getResults((int)$run['run_id']) as $result) {
				$results[$result['category']][$result['code']] = $result;
			}
		}

		// Technical failure detail (may quote SQL) is for Guardian admins only.
		$show_message = $this->user->hasPermission('access', 'extension/gtr_guardian/other/gtr_guardian');

		$data['categories'] = [];

		foreach ($this->model_extension_gtr_guardian_guardian_runner->getCategoryModels($provider->categories()) as $category => $model) {
			$this->load->language('extension/gtr_guardian/guardian/check/' . $category, $category);

			$checks = [];

			foreach ($model->getChecks() as $code => $meta) {
				$result = $results[$category][$code] ?? [];

				$checks[] = [
					'code'     => $code,
					'title'    => $this->language->get($category . '_check_' . $code . '_title'),
					'desc'     => $this->language->get($category . '_check_' . $code . '_desc'),
					'hint'     => $this->language->get($category . '_check_' . $code . '_hint'),
					'severity' => $meta['severity'],
					'status'   => $result ? $result['status'] : 'pending',
					'count'    => $result ? (int)$result['count'] : 0,
					'items'    => $result ? $this->escapeItems($result['items']) : [],
					'message'  => $result && $result['status'] === 'error' ? ($show_message ? htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') : $this->language->get('text_error_hidden')) : ''
				];
			}

			$data['categories'][] = [
				'code'   => $category,
				'name'   => $this->language->get($category . '_heading_title'),
				'checks' => $checks
			];
		}

		$data['items_limit'] = \Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check\Base::ITEMS_LIMIT;

		$data['can_run'] = $this->user->hasPermission('modify', 'extension/gtr_guardian/guardian/' . $domain);

		$data['run_url'] = $this->url->link('extension/gtr_guardian/guardian/' . $domain . '.run', 'user_token=' . $this->session->data['user_token']);
		$data['reload_url'] = $this->url->link('extension/gtr_guardian/guardian/' . $domain . '.report', 'user_token=' . $this->session->data['user_token']);

		$data['text_no_run'] = $this->language->get('text_no_run');
		$data['text_last_run'] = $this->language->get('text_last_run');
		$data['text_checks_total'] = $this->language->get('text_checks_total');
		$data['text_findings'] = $this->language->get('text_findings');
		$data['text_errors'] = $this->language->get('text_errors');
		$data['text_no_categories'] = $this->language->get('text_no_categories');
		$data['text_severity_critical'] = $this->language->get('text_severity_critical');
		$data['text_severity_warning'] = $this->language->get('text_severity_warning');
		$data['text_severity_info'] = $this->language->get('text_severity_info');
		$data['text_status_ok'] = $this->language->get('text_status_ok');
		$data['text_status_found'] = $this->language->get('text_status_found');
		$data['text_status_error'] = $this->language->get('text_status_error');
		$data['text_status_pending'] = $this->language->get('text_status_pending');
		$data['text_hint'] = $this->language->get('text_hint');
		$data['text_sample'] = $this->language->get('text_sample');
		$data['column_check'] = $this->language->get('column_check');
		$data['column_severity'] = $this->language->get('column_severity');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_count'] = $this->language->get('column_count');
		$data['button_run'] = $this->language->get('button_run');
		$data['button_details'] = $this->language->get('button_details');

		return $this->load->view('extension/gtr_guardian/guardian/report', $data);
	}

	/**
	 * Run every check of a domain and answer with JSON.
	 *
	 * @param string $domain
	 *
	 * @return void
	 */
	public function run(string $domain = ''): void {
		$this->load->language('extension/gtr_guardian/guardian/report');

		$json = [];

		if (!$this->isKnownDomain($domain) || !$this->user->hasPermission('modify', 'extension/gtr_guardian/guardian/' . $domain)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/gtr_guardian/guardian/runner');

			$this->model_extension_gtr_guardian_guardian_runner->run($domain);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isKnownDomain(string $domain): bool {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		return in_array($domain, $this->model_extension_gtr_guardian_other_gtr_guardian->getDomainCodes(), true);
	}

	/**
	 * The Twig adaptor does not autoescape. Rows come from the store's own
	 * tables, which are only escaped when data entered through the admin
	 * (Request::clean); imports and API writes bypass that. double_encode=false
	 * keeps already-escaped values intact.
	 *
	 * @param array<int, array<string, mixed>> $items
	 *
	 * @return array<int, array<string, string>>
	 */
	private function escapeItems(array $items): array {
		$escaped = [];

		foreach ($items as $row) {
			$clean = [];

			foreach ((array)$row as $key => $value) {
				$clean[htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8', false)] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', false);
			}

			$escaped[] = $clean;
		}

		return $escaped;
	}
}
