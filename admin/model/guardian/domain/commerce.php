<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleProvider;
use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class Commerce
 *
 * Guardian domain B: commerce and operations (ORDERS, CUSTOMERS, MULTI-L10N).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class Commerce extends \Opencart\System\Engine\Model implements SubmoduleProvider {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'commerce';
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
