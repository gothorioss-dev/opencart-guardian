<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class Dashboard
 *
 * Guardian assembly point. Polls every enabled domain through the
 * SubmoduleProvider contract and returns normalised report rows.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian
 */
class Dashboard extends \Opencart\System\Engine\Model {
	/**
	 * Collect a normalised report from every enabled Guardian domain.
	 *
	 * A failing provider is isolated: it is logged and returned as an error
	 * row, never allowed to break the page.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function collectReports(): array {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$reports = [];

		foreach ($this->model_extension_gtr_guardian_other_gtr_guardian->getEnabledDomainCodes() as $code) {
			try {
				$this->load->model('extension/gtr_guardian/guardian/domain/' . $code);

				$report = $this->{'model_extension_gtr_guardian_guardian_domain_' . $code}->report();

				if (!$report instanceof SubmoduleReport) {
					$report = SubmoduleReport::error($code);
				}
			} catch (\Throwable $e) {
				$this->log->write('OpenCart Guardian: domain "' . $code . '" report failed - ' . $e->getMessage());

				$report = SubmoduleReport::error($code);
			}

			$reports[] = $report->toArray();
		}

		return $reports;
	}
}
