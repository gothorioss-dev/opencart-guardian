<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class Commerce
 *
 * Guardian domain B: commerce and operations (ORDERS, CUSTOMERS, MULTI-L10N).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class Commerce extends DomainBase {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'commerce';
	}

	/**
	 * @return array<int, string>
	 */
	public function categories(): array {
		return ['orders', 'customers', 'localisation'];
	}
}
