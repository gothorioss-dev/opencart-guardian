<?php
namespace Opencart\System\Library\Extension\GtrGuardian\Guardian;
/**
 * Class CheckResult
 *
 * Outcome of a single check. Carries data only — the localised title/hint
 * are resolved by the screen from the check code.
 *
 * @package Opencart\System\Library\Extension\GtrGuardian\Guardian
 */
class CheckResult {
	public const STATUS_OK    = 'ok';
	public const STATUS_FOUND = 'found';
	public const STATUS_ERROR = 'error';

	public const SEVERITY_CRITICAL = 'critical';
	public const SEVERITY_WARNING  = 'warning';
	public const SEVERITY_INFO     = 'info';

	/**
	 * @param string                            $code     check code, unique within its category
	 * @param string                            $severity one of the SEVERITY_* constants
	 * @param string                            $status   one of the STATUS_* constants
	 * @param int                               $count    number of findings (may exceed count($items) when items are capped)
	 * @param array<int, array<string, mixed>>  $items    sample of findings, e.g. [['id' => 1, 'name' => '...']]
	 * @param string                            $message  technical detail for STATUS_ERROR, empty otherwise
	 */
	public function __construct(
		public string $code,
		public string $severity,
		public string $status = self::STATUS_OK,
		public int $count = 0,
		public array $items = [],
		public string $message = ''
	) {
	}

	/**
	 * @param string $code
	 * @param string $severity
	 *
	 * @return self
	 */
	public static function ok(string $code, string $severity): self {
		return new self($code, $severity);
	}

	/**
	 * @param string                           $code
	 * @param string                           $severity
	 * @param int                              $count
	 * @param array<int, array<string, mixed>> $items
	 *
	 * @return self
	 */
	public static function found(string $code, string $severity, int $count, array $items = []): self {
		return new self($code, $severity, $count > 0 ? self::STATUS_FOUND : self::STATUS_OK, $count, $items);
	}

	/**
	 * @param string $code
	 * @param string $severity
	 * @param string $message
	 *
	 * @return self
	 */
	public static function error(string $code, string $severity, string $message = ''): self {
		return new self($code, $severity, self::STATUS_ERROR, 0, [], $message);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'code'     => $this->code,
			'severity' => $this->severity,
			'status'   => $this->status,
			'count'    => $this->count,
			'items'    => $this->items,
			'message'  => $this->message
		];
	}
}
