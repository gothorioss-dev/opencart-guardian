<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;
/**
 * Class Cleanup
 *
 * Deletes the leftover data a check has found: a confirmation modal and the
 * delete action. Needs access and modify on its own route, so viewing reports
 * does not allow deleting.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
class Cleanup extends \Opencart\System\Engine\Controller {
	/**
	 * Confirmation modal (HTML) with the dry-run count.
	 *
	 * @return void
	 */
	public function confirm(): void {
		$this->load->language('extension/gtr_guardian/guardian/cleanup');

		$domain = (string)($this->request->get['domain'] ?? '');
		$category = (string)($this->request->get['category'] ?? '');
		$code = (string)($this->request->get['code'] ?? '');

		$model = $this->getModel($domain, $category, $code);

		if (!$model) {
			return;
		}

		$this->load->language('extension/gtr_guardian/guardian/check/' . $category, $category);

		$data['heading'] = sprintf($this->language->get('heading_title'), $this->language->get($category . '_check_' . $code . '_title'));

		$data['count'] = $model->cleanCount($code);

		$data['text_count_line'] = sprintf($this->language->get('text_count'), $data['count']);

		$data['domain'] = $domain;
		$data['category'] = $category;
		$data['code'] = $code;

		$data['action'] = $this->url->link('extension/gtr_guardian/guardian/cleanup.execute', 'user_token=' . $this->session->data['user_token']);
		$data['reload_url'] = $this->url->link('extension/gtr_guardian/guardian/' . $domain . '.report', 'user_token=' . $this->session->data['user_token']);

		$this->response->setOutput($this->load->view('extension/gtr_guardian/guardian/cleanup', $data));
	}

	/**
	 * Delete the findings of one check (POST), journal it and run the
	 * domain's checks again.
	 *
	 * @return void
	 */
	public function execute(): void {
		$this->load->language('extension/gtr_guardian/guardian/cleanup');

		$json = [];

		$domain = (string)($this->request->post['domain'] ?? '');
		$category = (string)($this->request->post['category'] ?? '');
		$code = (string)($this->request->post['code'] ?? '');

		if ($this->request->server['REQUEST_METHOD'] != 'POST' || !$this->isAllowed($domain)) {
			$json['error'] = $this->language->get('error_permission');
		}

		$model = null;

		if (!$json) {
			$model = $this->getModel($domain, $category, $code);

			if (!$model) {
				$json['error'] = $this->language->get('error_check');
			}
		}

		if (!$json && empty($this->request->post['backup'])) {
			$json['error']['backup'] = $this->language->get('error_backup');
		}

		if (!$json) {
			$this->load->model('extension/gtr_guardian/guardian/cleanup');
			$this->load->model('extension/gtr_guardian/guardian/runner');
			$this->load->model('extension/gtr_guardian/other/gtr_guardian');

			try {
				$deleted = $model->clean($code);
			} catch (\Throwable $e) {
				$this->log->write('OpenCart Guardian: cleanup "' . $category . '/' . $code . '" failed - ' . get_class($e) . ': ' . $e->getMessage());

				$json['error'] = $this->language->get('error_clean');
			}
		}

		if (!$json) {
			$this->model_extension_gtr_guardian_guardian_cleanup->addCleanup($domain, $category, $code, (int)($this->request->post['expected'] ?? 0), $deleted);

			$this->model_extension_gtr_guardian_guardian_cleanup->prune($this->model_extension_gtr_guardian_other_gtr_guardian->getRetention()['days']);

			// The data is already deleted and committed: a failing re-run must not be reported as a failed cleanup.
			try {
				$this->model_extension_gtr_guardian_guardian_runner->run($domain, \Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Runner::ORIGIN_CLEANUP);
			} catch (\Throwable $e) {
				$this->log->write('OpenCart Guardian: run after cleanup "' . $category . '/' . $code . '" failed - ' . get_class($e) . ': ' . $e->getMessage());
			}

			$json['success'] = sprintf($this->language->get('text_success'), $deleted);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Whether the current user may clean up in a domain: Guardian is on, the
	 * domain exists and is enabled, access to the domain, access and modify
	 * on the cleanup route.
	 *
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isAllowed(string $domain): bool {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$model = $this->model_extension_gtr_guardian_other_gtr_guardian;

		return $model->isActive() && $model->isKnownDomain($domain) && $model->isDomainEnabled($domain) && $this->user->hasPermission('access', 'extension/gtr_guardian/guardian/' . $domain) && $this->user->hasPermission('access', 'extension/gtr_guardian/guardian/cleanup') && $this->user->hasPermission('modify', 'extension/gtr_guardian/guardian/cleanup');
	}

	/**
	 * The check model of a cleanable check, null when the request is not
	 * allowed or names something that cannot be cleaned.
	 *
	 * @param string $domain
	 * @param string $category
	 * @param string $code
	 *
	 * @return ?\Opencart\System\Engine\Proxy
	 */
	private function getModel(string $domain, string $category, string $code): ?\Opencart\System\Engine\Proxy {
		if (!$this->isAllowed($domain)) {
			return null;
		}

		$this->load->model('extension/gtr_guardian/guardian/domain/' . $domain);
		$this->load->model('extension/gtr_guardian/guardian/runner');

		$provider = $this->{'model_extension_gtr_guardian_guardian_domain_' . $domain};

		if (!in_array($category, $provider->categories(), true)) {
			return null;
		}

		$models = $this->model_extension_gtr_guardian_guardian_runner->getCategoryModels([$category]);

		if (!isset($models[$category]) || !isset($models[$category]->getChecks()[$code]) || !$models[$category]->canClean($code)) {
			return null;
		}

		return $models[$category];
	}
}
