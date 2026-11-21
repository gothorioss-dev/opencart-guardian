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
		'garbage_orphan_description'       => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'garbage_description_bad_language' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'garbage_orphan_link'              => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql']
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
	 * Names and descriptions of an existing record that belong to a language
	 * which has been deleted, one finding row per description row. All 20 core
	 * `*_description` tables are covered. The admin deletes a language together
	 * with its texts (deleteLanguage cleans 19 tables, but not
	 * `subscription_plan_description`, so those rows also come from normal
	 * admin use); the rest comes from imports or direct SQL. The storefront
	 * reads texts only in installed languages. Saving the record in the admin
	 * re-writes its descriptions for installed languages only (editProduct and
	 * every other edit*()), which drops these rows. Descriptions of deleted
	 * records belong to garbage_orphan_description.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageDescriptionBadLanguage(): CheckResult {
		$parts = [];

		foreach ($this->descriptionTables() as $type => $name) {
			$parts[] = "SELECT '" . $type . "' AS `type`, `d`.`" . $type . "_id` AS `record_id`, `d`.`" . $name . "` AS `name`, `d`.`language_id` FROM `" . DB_PREFIX . $type . "_description` `d` WHERE EXISTS (SELECT 1 FROM `" . DB_PREFIX . $type . "` `p` WHERE `p`.`" . $type . "_id` = `d`.`" . $type . "_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "language` `l` WHERE `l`.`language_id` = `d`.`language_id`)";
		}

		return $this->collect('garbage_description_bad_language', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `bad` ORDER BY `type`, `record_id`, `language_id`");
	}

	/**
	 * Links whose owner record has been deleted, one finding row per link type
	 * and deleted owner (`links` counts the link rows). Covers the store, layout,
	 * category, download, filter and path links of products, categories and
	 * pages, the links and history of coupons, and the child rows of tax rates,
	 * geo zones, custom fields, options, banners, articles, campaigns and
	 * downloads. The admin delete functions remove them with the owner, so these
	 * come from imports or direct SQL, with two exceptions from normal use: the
	 * store delete does not clean the store and layout links of articles and
	 * topics, and the topic delete looks for its layout links by the article
	 * column, so topic layout links survive a topic delete. Nothing on the
	 * storefront or in the admin reads them. Links of an existing owner to a
	 * deleted target belong to garbage_link_deleted_target; children of guarded parents
	 * (filters of a filter group, attributes of an attribute group, zones of a
	 * country) are left out.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageOrphanLink(): CheckResult {
		$parts = [];

		foreach ($this->linkTables() as $type => [$owner, $column]) {
			$parts[] = "SELECT '" . $type . "' AS `type`, `t`.`" . $column . "` AS `record_id`, COUNT(*) AS `links` FROM `" . DB_PREFIX . $type . "` `t` WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $owner . "` `o` WHERE `o`.`" . $column . "` = `t`.`" . $column . "`) GROUP BY `t`.`" . $column . "`";
		}

		return $this->collect('garbage_orphan_link', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `orphan` ORDER BY `type`, `record_id`");
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

	/**
	 * Link tables checked for a deleted owner; the owner column has the same
	 * name in the link table and in the owner table.
	 *
	 * @return array<string, array{0: string, 1: string}> link table => [owner table, owner column]
	 */
	private function linkTables(): array {
		return [
			'product_to_category' => ['product', 'product_id'],
			'product_to_store' => ['product', 'product_id'],
			'product_to_download' => ['product', 'product_id'],
			'product_to_layout' => ['product', 'product_id'],
			'category_to_store' => ['category', 'category_id'],
			'category_to_layout' => ['category', 'category_id'],
			'category_filter' => ['category', 'category_id'],
			'category_path' => ['category', 'category_id'],
			'information_to_store' => ['information', 'information_id'],
			'information_to_layout' => ['information', 'information_id'],
			'manufacturer_to_store' => ['manufacturer', 'manufacturer_id'],
			'manufacturer_to_layout' => ['manufacturer', 'manufacturer_id'],
			'article_to_store' => ['article', 'article_id'],
			'article_to_layout' => ['article', 'article_id'],
			'topic_to_store' => ['topic', 'topic_id'],
			'topic_to_layout' => ['topic', 'topic_id'],
			'coupon_product' => ['coupon', 'coupon_id'],
			'coupon_category' => ['coupon', 'coupon_id'],
			'coupon_history' => ['coupon', 'coupon_id'],
			'tax_rate_to_customer_group' => ['tax_rate', 'tax_rate_id'],
			'zone_to_geo_zone' => ['geo_zone', 'geo_zone_id'],
			'custom_field_customer_group' => ['custom_field', 'custom_field_id'],
			'custom_field_value' => ['custom_field', 'custom_field_id'],
			'option_value' => ['option', 'option_id'],
			'banner_image' => ['banner', 'banner_id'],
			'article_comment' => ['article', 'article_id'],
			'marketing_report' => ['marketing', 'marketing_id'],
			'download_report' => ['download', 'download_id']
		];
	}
}
