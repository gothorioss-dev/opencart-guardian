<?php
namespace Opencart\System\Library\Extension\GtrGuardian\Guardian;
/**
 * Interface SubmoduleProvider
 *
 * Implemented by every Guardian domain model
 * (admin/model/guardian/domain/<code>.php). This is the only surface the core
 * (aggregator, menu) uses to talk to a domain — domains never reference
 * each other.
 *
 * @package Opencart\System\Library\Extension\GtrGuardian\Guardian
 */
interface SubmoduleProvider {
	/**
	 * Stable domain code, e.g. "catalog". Matches the model filename.
	 *
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * Domain health snapshot for the dashboard.
	 *
	 * Implementations must not throw: on internal failure they return
	 * SubmoduleReport::error() instead.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport
	 */
	public function report(): SubmoduleReport;
}
