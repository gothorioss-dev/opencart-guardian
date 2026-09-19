<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian;
/**
 * Class Events
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian
 */
class Events extends \Opencart\System\Engine\Controller {
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

		if (!$this->model_extension_gtr_guardian_other_gtr_guardian->isActive()) {
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

		foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getVisibleDomainCodes() as $domain_code) {
			$this->load->language('extension/gtr_guardian/guardian/' . $domain_code, $domain_code);

			$guardian[] = [
				'name'     => $this->language->get($domain_code . '_heading_title'),
				'href'     => $this->url->link('extension/gtr_guardian/guardian/' . $domain_code, $token),
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
