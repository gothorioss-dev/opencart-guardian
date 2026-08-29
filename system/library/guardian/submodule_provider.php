<?php
namespace Opencart\System\Library\Extension\GtrGuardian\Guardian;
/**
 * Interface SubmoduleProvider
 *
 * Implemented by every Guardian domain model
 * (admin/model/guardian/domain/<code>.php). This is the only surface the core
 * (aggregator, menu, sync) uses to talk to a domain — domains never reference
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

	/**
	 * Tables the domain owns, as [table_name_without_prefix => CREATE TABLE SQL].
	 *
	 * Consumed by core sync(): applied with CREATE TABLE IF NOT EXISTS on
	 * upgrade, dropped by name when the domain is removed. Return [] if none.
	 *
	 * @return array<string, string>
	 */
	public function schema(): array;

	/**
	 * Cron jobs the domain owns, as a list of rows for model_setting_cron->add()
	 * (keys: code, description, cycle, action, status). Return [] if none.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function cronJobs(): array;
}
