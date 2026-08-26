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
	 * view/common/column_left/before
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $code
	 * @param mixed                $output
	 *
	 * @return void
	 */
	public function addColumnLeftMenu(string &$route, array &$data, string &$code, &$output = null): void {
		$this->load->model('extension/gtr_guardian/module/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_module_gtr_guardian->isCoreInstalled()) {
			return;
		}

		$this->load->language('extension/gtr_guardian/module/gtr_guardian');

		$guardian = [];
		$token = 'user_token=' . $this->session->data['user_token'];

		if ($this->user->hasPermission('access', 'extension/gtr_guardian/dashboard/dashboard')) {
			$guardian[] = [
				'name'     => $this->language->get('text_dashboard'),
				'href'     => $this->url->link('extension/gtr_guardian/dashboard/dashboard', $token),
				'children' => []
			];
		}

		if (
			$this->model_extension_gtr_guardian_module_gtr_guardian->isSubmoduleInstalled('health_monitor')
			&& $this->user->hasPermission('access', 'extension/gtr_guardian/other/health_monitor')
		) {
			$guardian[] = [
				'name'     => $this->language->get('text_health_monitor'),
				'href'     => $this->url->link('extension/gtr_guardian/other/health_monitor', $token),
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
