<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class System
 *
 * Guardian domain C: system and infrastructure (CONF, EXT, PERF, LOGS, CRON, INTEGRITY).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class System extends DomainBase {
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
}
