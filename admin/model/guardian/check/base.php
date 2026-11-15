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

	/**
	 * SQL condition: a stored (HTML-escaped) description is empty. An editor's
	 * empty markup (&lt;p&gt;&lt;br&gt;&lt;/p&gt;, &amp;nbsp;) counts as empty;
	 * media tags are kept, so an image-only description is not empty.
	 * REGEXP_REPLACE needs MySQL 8.0+ / MariaDB 10.0.5+; (?s) lets a tag span lines.
	 *
	 * @param string $column SQL expression of the description column
	 *
	 * @return string SQL condition
	 */
	protected function descriptionEmpty(string $column): string {
		return "REGEXP_REPLACE(REGEXP_REPLACE(" . $column . ", '(?s)&lt;(?!(img|iframe|video|embed|object)[[:space:]&/]).*?&gt;', ''), '&amp;nbsp;|[[:space:]]', '') = ''";
	}

	/**
	 * Variant family of every product whose master exists: the product's own
	 * id for a master, the master's id for a variant, the master's master for
	 * a variant of a variant. Products with a missing master are left out.
	 *
	 * @param string $columns extra select list over `p2` (product) and `m2` (its master)
	 *
	 * @return string SELECT product_id, family ... with an open WHERE that callers may extend with AND
	 */
	protected function variantFamilies(string $columns = ''): string {
		return "SELECT `p2`.`product_id`, IF(`p2`.`master_id` = '0', `p2`.`product_id`, IF(`m2`.`master_id` != '0', `m2`.`master_id`, `p2`.`master_id`)) AS `family`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "product` `p2` LEFT JOIN `" . DB_PREFIX . "product` `m2` ON (`m2`.`product_id` = `p2`.`master_id`) WHERE (`p2`.`master_id` = '0' OR `m2`.`product_id` IS NOT NULL)";
	}
}
