<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class Storefront
 *
 * Guardian domain E: storefront / external surface (FRONTEND).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class Storefront extends DomainBase {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'storefront';
	}

	/**
	 * @return array<int, string>
	 */
	public function categories(): array {
		return ['frontend'];
	}
}
