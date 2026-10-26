<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Base
 *
 * One subclass per check category. Each check is a method whose name is the
 * camelCase form of its code, described in $checks; the registry is that
 * array, so metadata and implementation live side by side.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
abstract class Base extends \Opencart\System\Engine\Model {
	/**
	 * Findings are sampled: this many rows are stored, the count is exact.
	 */
	public const ITEMS_LIMIT = 50;

	/**
	 * Registry: check code => ['severity' => CheckResult::SEVERITY_*, 'source' => 'sql'|'fs'|'http'|'log'|'conf'],
	 * plus an optional 'edit' finding column that overrides $edit for that check.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected array $checks = [];

	/**
	 * Finding column holding the id of the record the report's edit button
	 * opens; empty for no button.
	 */
	protected string $edit = '';

	/**
	 * @return array<string, array<string, string>>
	 */
	public function getChecks(): array {
		return $this->checks;
	}

	/**
	 * @param string $code
	 *
	 * @return string finding column for the edit button, empty for none
	 */
	public function getEditKey(string $code): string {
		return $this->checks[$code]['edit'] ?? $this->edit;
	}

	/**
	 * Execute one check by code. Exceptions propagate — the runner isolates them.
	 *
	 * @param string $code
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	public function run(string $code): CheckResult {
		$method = lcfirst(str_replace('_', '', ucwords($code, '_')));

		if (!isset($this->checks[$code]) || !method_exists($this, $method)) {
			return CheckResult::error($code, $this->checks[$code]['severity'] ?? CheckResult::SEVERITY_INFO, 'Check method not found: ' . $method);
		}

		return $this->{$method}();
	}

	/**
	 * Run a finding query: exact count plus a sample of ITEMS_LIMIT rows.
	 *
	 * @param string $code
	 * @param string $sql SELECT producing one row per finding, without LIMIT
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function collect(string $code, string $sql): CheckResult {
		$count = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM (" . $sql . ") AS `t`")->row['total'];

		$items = $count ? $this->db->query($sql . " LIMIT " . self::ITEMS_LIMIT)->rows : [];

		return CheckResult::found($code, $this->checks[$code]['severity'], $count, $items);
	}
}
