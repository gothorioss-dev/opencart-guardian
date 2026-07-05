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
		/* todo: unlock after permissions implement */
		/*if (!$this->user->hasPermission('access', 'extension/gtr_guardian/module/gtr_guardian')) {
			return;
		}*/

		$this->load->language('extension/gtr_guardian/module/gtr_guardian');

		$guardian = [];

		$guardian[] = [
			'name'     => $this->language->get('text_settings'),
			'href'     => $this->url->link('extension/gtr_guardian/module/gtr_guardian', 'user_token=' . $this->session->data['user_token']),
			'children' => []
		];

		$data['menus'][] = [
			'id'       => 'menu-gtr-guardian',
			'icon'     => 'fas fa-shield-halved',
			'name'     => $this->language->get('heading_title'),
			'href'     => '',
			'children' => $guardian
		];
	}
}
