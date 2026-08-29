<?php
namespace Opencart\System\Library\Extension\GtrGuardian\Guardian;
/**
 * Class SubmoduleReport
 *
 * Unified status DTO returned by every submodule. Carries domain data only —
 * the localised label and the admin URL are added by the aggregator.
 *
 * @package Opencart\System\Library\Extension\GtrGuardian\Guardian
 */
class SubmoduleReport {
	public const STATUS_OK       = 'ok';
	public const STATUS_WARNING  = 'warning';
	public const STATUS_CRITICAL = 'critical';
	public const STATUS_UNKNOWN  = 'unknown';
	public const STATUS_ERROR    = 'error';

	/**
	 * Canonical severity order used for the counts map.
	 *
	 * @var array<int, string>
	 */
	public const SEVERITIES = ['critical', 'warning', 'info'];

	/**
	 * @param string             $code
	 * @param string             $status       one of the STATUS_* constants
	 * @param string             $summary      short plain-text summary
	 * @param array<string, int> $counts       findings per severity
	 * @param int|null           $last_run     unix timestamp of the last run, null if never
	 * @param int                $checks_total number of checks the domain ships
	 */
	public function __construct(
		public string $code,
		public string $status = self::STATUS_UNKNOWN,
		public string $summary = '',
		public array $counts = ['critical' => 0, 'warning' => 0, 'info' => 0],
		public ?int $last_run = null,
		public int $checks_total = 0
	) {
	}

	/**
	 * Report for a submodule that has never run (scaffold stage included).
	 *
	 * @param string $code
	 * @param int    $checks_total
	 *
	 * @return self
	 */
	public static function pending(string $code, int $checks_total = 0): self {
		return new self($code, self::STATUS_UNKNOWN, '', ['critical' => 0, 'warning' => 0, 'info' => 0], null, $checks_total);
	}

	/**
	 * Report for a submodule whose provider failed.
	 *
	 * @param string $code
	 * @param string $summary
	 *
	 * @return self
	 */
	public static function error(string $code, string $summary = ''): self {
		return new self($code, self::STATUS_ERROR, $summary);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'code'         => $this->code,
			'status'       => $this->status,
			'summary'      => $this->summary,
			'counts'       => $this->counts,
			'last_run'     => $this->last_run,
			'checks_total' => $this->checks_total
		];
	}
}
