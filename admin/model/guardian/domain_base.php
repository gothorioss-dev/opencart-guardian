<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleProvider;
use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class DomainBase
 *
 * Shared SubmoduleProvider implementation: the report is served from the
 * gtr_guardian_summary setting the runner maintains, so the dashboard never
 * touches the run tables.
 *
 * Lives outside admin/model/guardian/domain/ on purpose — that folder is
 * globbed for domain discovery.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian
 */
abstract class DomainBase extends \Opencart\System\Engine\Model implements SubmoduleProvider {
	/**
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport
	 */
	public function report(): SubmoduleReport {
		$code = $this->getCode();

		$summary = $this->config->get('gtr_guardian_summary');

		if (!is_array($summary) || empty($summary[$code])) {
			$this->load->model('extension/gtr_guardian/guardian/runner');

			return SubmoduleReport::pending($code, $this->model_extension_gtr_guardian_guardian_runner->getChecksTotal($this->categories()));
		}

		$row = $summary[$code];

		return new SubmoduleReport(
			$code,
			(string)$row['status'],
			'',
			(array)$row['counts'],
			(int)$row['last_run'],
			(int)$row['checks_total']
		);
	}
}
