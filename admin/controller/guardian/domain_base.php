<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;
/**
 * Class DomainBase
 *
 * Shared domain screen: page chrome plus the report block. Subclasses only
 * name their domain; every HTTP entry point stays on the domain route so the
 * per-domain access/modify permissions apply.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
abstract class DomainBase extends \Opencart\System\Engine\Controller {
	/**
	 * Domain code, matches the provider model filename.
	 */
	protected const DOMAIN = '';

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		if (!$this->isAvailable()) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->language('extension/gtr_guardian/other/gtr_guardian');
		$this->load->language('extension/gtr_guardian/guardian/' . static::DOMAIN);

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
			'href' => $this->url->link('extension/gtr_guardian/guardian/' . static::DOMAIN, 'user_token=' . $this->session->data['user_token'])
		];

		$data['back'] = $this->url->link('extension/gtr_guardian/guardian/dashboard', 'user_token=' . $this->session->data['user_token']);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_description'] = $this->language->get('text_description');
		$data['button_back'] = $this->language->get('button_back');

		$data['report'] = $this->load->controller('extension/gtr_guardian/guardian/report', static::DOMAIN);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/gtr_guardian/guardian/domain', $data));
	}

	/**
	 * Report block only — reloaded by AJAX after a run.
	 *
	 * @return void
	 */
	public function report(): void {
		if (!$this->isAvailable()) {
			return;
		}

		$this->response->setOutput($this->load->controller('extension/gtr_guardian/guardian/report', static::DOMAIN));
	}

	/**
	 * Run
	 *
	 * @return void
	 */
	public function run(): void {
		if (!$this->isAvailable()) {
			return;
		}

		$this->load->controller('extension/gtr_guardian/guardian/report.run', static::DOMAIN);
	}

	/**
	 * @return bool
	 */
	private function isAvailable(): bool {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		return $this->model_extension_gtr_guardian_other_gtr_guardian->isCoreInstalled() && $this->model_extension_gtr_guardian_other_gtr_guardian->isDomainEnabled(static::DOMAIN);
	}
}
