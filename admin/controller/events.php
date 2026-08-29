<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian;
/**
 * Class Events
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian
 */
class Events extends \Opencart\System\Engine\Controller {
	/**
	 * Reconcile
	 *
	 * admin/view/common/column_left/before
	 *
	 * Self-heals a file-only package update (added/removed domain) on the next
	 * admin page load. Cheap no-op when nothing changed.
	 *
	 * @return void
	 */
	public function reconcile(): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_other_gtr_guardian->isCoreInstalled()) {
			return;
		}

		$this->model_extension_gtr_guardian_other_gtr_guardian->sync();
	}

	/**
	 * Add Column Left Menu
	 *
	 * admin/view/common/column_left/before
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $code
	 * @param mixed                $output
	 *
	 * @return void
	 */
	public function addColumnLeftMenu(string &$route, array &$data, string &$code, &$output = null): void {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_other_gtr_guardian->isCoreInstalled()) {
			return;
		}

		$this->load->language('extension/gtr_guardian/other/gtr_guardian');

		$guardian = [];
		$token = 'user_token=' . $this->session->data['user_token'];

		if ($this->user->hasPermission('access', 'extension/gtr_guardian/guardian/dashboard')) {
			$guardian[] = [
				'name'     => $this->language->get('text_dashboard'),
				'href'     => $this->url->link('extension/gtr_guardian/guardian/dashboard', $token),
				'children' => []
			];
		}

		foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getEnabledDomainCodes() as $domain_code) {
			$domain_route = 'extension/gtr_guardian/guardian/' . $domain_code;

			if (!$this->user->hasPermission('access', $domain_route)) {
				continue;
			}

			$this->load->language('extension/gtr_guardian/guardian/' . $domain_code, $domain_code);

			$guardian[] = [
				'name'     => $this->language->get($domain_code . '_heading_title'),
				'href'     => $this->url->link($domain_route, $token),
				'children' => []
			];
		}

		if (!$guardian) {
			return;
		}

		$data['menus'][] = [
			'id'       => 'menu-gtr-guardian',
			'icon'     => 'fas fa-shield-halved',
			'name'     => $this->language->get('heading_title'),
			'href'     => '',
			'children' => $guardian
		];
	}
}
