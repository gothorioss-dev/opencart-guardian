<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleProvider;
use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class DomainBase
 *
 * Shared SubmoduleProvider implementation: the report is built from the
 * domain's latest finished run.
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

		$this->load->model('extension/gtr_guardian/guardian/result');

		$run = $this->model_extension_gtr_guardian_guardian_result->getLatestRun($code);

		if (!$run) {
			$this->load->model('extension/gtr_guardian/guardian/runner');

			return SubmoduleReport::pending($code, $this->model_extension_gtr_guardian_guardian_runner->getChecksTotal($this->categories()));
		}

		$counts = [
			'critical' => (int)$run['critical'],
			'warning'  => (int)$run['warning'],
			'info'     => (int)$run['info'],
			'errors'   => (int)$run['errors']
		];

		if ($counts['critical']) {
			$status = SubmoduleReport::STATUS_CRITICAL;
		} elseif ($counts['warning']) {
			$status = SubmoduleReport::STATUS_WARNING;
		} elseif ($counts['errors']) {
			$status = SubmoduleReport::STATUS_ERROR;
		} else {
			$status = SubmoduleReport::STATUS_OK;
		}

		return new SubmoduleReport(
			$code,
			$status,
			'',
			$counts,
			strtotime($run['date_finished']),
			(int)$run['checks_total']
		);
	}
}
