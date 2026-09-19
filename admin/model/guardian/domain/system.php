<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleProvider;
use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class System
 *
 * Guardian domain C: system and infrastructure (CONF, EXT, PERF, LOGS, CRON, INTEGRITY).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class System extends \Opencart\System\Engine\Model implements SubmoduleProvider {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'system';
	}

	/**
	 * @return array<int, string>
	 */
	public function categories(): array {
		return ['config', 'extension', 'performance', 'logs', 'cron', 'integrity'];
	}

	/**
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport
	 */
	public function report(): SubmoduleReport {
		// Scaffold stage: no check runner yet. Later this reads the latest run
		// for the domain from the shared Guardian results store.
		return SubmoduleReport::pending($this->getCode());
	}
}
