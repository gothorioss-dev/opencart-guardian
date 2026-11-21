<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Garbage
 *
 * Category GARBAGE: records left behind by deleted or outdated data.
 * Findings only; cleanup is a separate step.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Garbage extends Base {
	protected array $checks = [
		'garbage_orphan_description' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql']
	];

	/**
	 * Names and descriptions whose record has been deleted, one finding row per
	 * deleted record (`languages` counts its description rows). All 20 core
	 * `*_description` tables are covered. The admin delete functions remove the
	 * descriptions with the record, so these come from imports or direct SQL;
	 * the admin cannot show or delete them and nothing on the storefront reads
	 * them. Records that exist but miss a description in some language belong
	 * to the content checks (product_incomplete_description).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageOrphanDescription(): CheckResult {
		$parts = [];

		foreach ($this->descriptionTables() as $type => $name) {
			$parts[] = "SELECT '" . $type . "' AS `type`, `d`.`" . $type . "_id` AS `record_id`, COALESCE(MAX(IF(`d`.`language_id` = '" . (int)$this->config->get('config_language_id') . "', `d`.`" . $name . "`, NULL)), MIN(`d`.`" . $name . "`)) AS `name`, COUNT(*) AS `languages` FROM `" . DB_PREFIX . $type . "_description` `d` WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $type . "` `p` WHERE `p`.`" . $type . "_id` = `d`.`" . $type . "_id`) GROUP BY `d`.`" . $type . "_id`";
		}

		return $this->collect('garbage_orphan_description', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `orphan` ORDER BY `type`, `record_id`");
	}

	/**
	 * Tables `<type>_description` with the parent `<type>` and the key
	 * `<type>_id`.
	 *
	 * @return array<string, string> type => name column
	 */
	private function descriptionTables(): array {
		$tables = [];

		foreach (['product', 'category', 'information', 'attribute', 'attribute_group', 'option', 'option_value', 'filter', 'filter_group', 'download', 'article', 'topic', 'customer_group', 'custom_field', 'custom_field_value', 'country', 'zone', 'length_class', 'weight_class', 'subscription_plan'] as $type) {
			$tables[$type] = in_array($type, ['information', 'length_class', 'weight_class']) ? 'title' : 'name';
		}

		return $tables;
	}
}
