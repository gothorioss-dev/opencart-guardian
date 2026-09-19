<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class Security
 *
 * Guardian domain D: security and resilience (SECURITY, BACKUP).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class Security extends DomainBase {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'security';
	}

	/**
	 * @return array<int, string>
	 */
	public function categories(): array {
		return ['security', 'backup'];
	}
}
