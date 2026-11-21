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
		'garbage_orphan_description'       => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'clean' => true],
		'garbage_description_bad_language' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'clean' => true],
		'garbage_orphan_link'              => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'clean' => true],
		'garbage_link_deleted_target'      => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'clean' => true],
		'garbage_orphan_product_data'      => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'clean' => true],
		'garbage_expired_coupon'           => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'coupon_id'],
		'garbage_inactive_marketing'       => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'marketing_id'],
		'garbage_disabled_summary'         => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'garbage_expired_discount'         => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'product_id']
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
		$sources = $this->orphanDescriptionParts();

		foreach ($this->descriptionTables() as $type => $name) {
			$part = $sources[$type];

			$parts[] = "SELECT '" . $type . "' AS `type`, `d`.`" . $type . "_id` AS `record_id`, COALESCE(MAX(IF(`d`.`language_id` = '" . (int)$this->config->get('config_language_id') . "', `d`.`" . $name . "`, NULL)), MIN(`d`.`" . $name . "`)) AS `name`, COUNT(*) AS `languages` FROM " . $part['from'] . " WHERE " . $part['where'] . " GROUP BY `d`.`" . $type . "_id`";
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
		$sources = $this->badLanguageParts();

		foreach ($this->descriptionTables() as $type => $name) {
			$part = $sources[$type];

			$parts[] = "SELECT '" . $type . "' AS `type`, `d`.`" . $type . "_id` AS `record_id`, `d`.`" . $name . "` AS `name`, `d`.`language_id` FROM " . $part['from'] . " WHERE " . $part['where'];
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
	 * country) are left out. The cleanup deletes all of these except the
	 * coupon history, which the core coupon report still reads.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageOrphanLink(): CheckResult {
		$parts = [];

		foreach ($this->linkParts() as $part) {
			$parts[] = "SELECT '" . $part['type'] . "' AS `type`, `t`.`" . $part['column'] . "` AS `record_id`, COUNT(*) AS `links` FROM " . $part['from'] . " WHERE " . $part['where'] . " GROUP BY `t`.`" . $part['column'] . "`";
		}

		return $this->collect('garbage_orphan_link', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `orphan` ORDER BY `type`, `record_id`");
	}

	/**
	 * Links of an existing owner to a deleted target, one finding row per link:
	 * a store (store and layout links of products, categories, pages,
	 * manufacturers, articles and topics; the default store 0 is not a row of
	 * the store table), a category (product and coupon categories), a product
	 * (coupon products) or a filter (product filters). The admin forms list
	 * existing stores, categories, products and filters only, and every edit*()
	 * re-writes these links, so saving the record drops them. The store delete
	 * does not clean the store and layout links of articles and topics, so
	 * those also come from normal use; the rest comes from imports or direct
	 * SQL (a filter delete does clean product filters). The storefront ignores
	 * links to missing stores and categories, but a coupon limited to a deleted
	 * category can apply to the whole cart if an old product link to that
	 * category remains (getTotalCategoriesByCategoryId in
	 * catalog/model/catalog/product.php, used by the storefront coupon model,
	 * does not look at the product). Links of a deleted owner belong to
	 * garbage_orphan_link; layouts that were deleted are not checked here.
	 * The cleanup deletes the store, product-category and filter links, but not
	 * the coupon links nor a product link to a category a coupon still uses
	 * (see garbageLinkDeletedTargetCleanup()).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageLinkDeletedTarget(): CheckResult {
		$parts = [];

		foreach ($this->targetParts() as $type => $part) {
			$parts[] = "SELECT '" . $type . "' AS `type`, `t`.`" . $part['owner_column'] . "` AS `record_id`, `t`.`" . $part['target_column'] . "` AS `target_id` FROM " . $part['from'] . " WHERE " . $part['where'];
		}

		return $this->collect('garbage_link_deleted_target', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `dead` ORDER BY `type`, `record_id`, `target_id`");
	}

	/**
	 * Data of a deleted product, one finding row per table and deleted product
	 * (`records` counts the rows): attributes, identifiers, discounts and
	 * specials, filters, images, options and option values, related products,
	 * visit report, reward points, subscription plans, view statistics and
	 * reviews. The admin product delete removes all of these except the view
	 * statistics (product_viewed is not in its cascade, so normal deletes leave
	 * it); the rest comes from imports or direct SQL. The admin guards for deleting an attribute or an option count
	 * product rows without checking that the product exists
	 * (getTotalAttributesByAttributeId and getTotalOptionsByOptionId), so such
	 * leftovers block that deletion.
	 * Links of a deleted product to stores, categories, downloads and layouts
	 * belong to garbage_orphan_link; descriptions to garbage_orphan_description.
	 * The view statistics table exists only while the "Products Viewed" report
	 * extension is installed, so it is checked only when present.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageOrphanProductData(): CheckResult {
		$parts = [];

		foreach ($this->orphanProductDataParts() as $type => $part) {
			$parts[] = "SELECT '" . $type . "' AS `type`, `t`.`product_id` AS `record_id`, COUNT(*) AS `records` FROM " . $part['from'] . " WHERE " . $part['where'] . " GROUP BY `t`.`product_id`";
		}

		return $this->collect('garbage_orphan_product_data', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `orphan` ORDER BY `type`, `record_id`");
	}

	/**
	 * Coupons whose end date has passed (today counts as passed), enabled or
	 * not; a zero end date means no limit and is skipped. The storefront
	 * refuses a coupon after its end date (catalog/model/marketing/coupon.php),
	 * and the admin never removes a coupon by itself, so expired ones stay
	 * until deleted. Findings carry the edit button to the coupon form.
	 * Expired product discounts are checked by garbage_expired_discount.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageExpiredCoupon(): CheckResult {
		return $this->collect('garbage_expired_coupon', "SELECT `c`.`coupon_id`, `c`.`name`, `c`.`code`, `c`.`status`, `c`.`date_end` FROM `" . DB_PREFIX . "coupon` `c` WHERE `c`.`date_end` != '0000-00-00' AND `c`.`date_end` <= CURDATE() ORDER BY `c`.`date_end`, `c`.`coupon_id`");
	}

	/**
	 * Marketing campaigns added more than 90 days ago with no recorded visit
	 * and no order. The storefront writes a visit of a tracking code to the
	 * campaign report (addReport in catalog/model/marketing/marketing.php),
	 * shown on the campaign form's Report tab; the legacy `clicks` counter is
	 * written by nothing in this version, so it is only required to be zero.
	 * The admin never removes a campaign by itself. Orders of any status
	 * count as use (the admin campaign list counts complete orders only, so
	 * this is stricter). Findings carry the edit button to the campaign form.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageInactiveMarketing(): CheckResult {
		return $this->collect('garbage_inactive_marketing', "SELECT `m`.`marketing_id`, `m`.`name`, `m`.`code`, `m`.`date_added` FROM `" . DB_PREFIX . "marketing` `m` WHERE `m`.`clicks` = '0' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "marketing_report` `mr` WHERE `mr`.`marketing_id` = `m`.`marketing_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "order` `o` WHERE `o`.`marketing_id` = `m`.`marketing_id`) AND `m`.`date_added` < DATE_SUB(NOW(), INTERVAL 90 DAY) ORDER BY `m`.`date_added`, `m`.`marketing_id`");
	}

	/**
	 * Disabled products, categories, information pages, banners, articles and
	 * topics: one finding row per kind with its number (`total`), kinds with
	 * none are left out. The check count is the sum of the rows, not the row
	 * count, so it is the number of disabled records; the rows are a summary
	 * and never reach the sample limit. Disabled records are normal (a hidden
	 * product, a draft), so this is a reminder to review old ones, not an
	 * error; it lists no single record.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageDisabledSummary(): CheckResult {
		$parts = [];

		foreach (['product', 'category', 'information', 'banner', 'article', 'topic'] as $type) {
			$parts[] = "SELECT '" . $type . "' AS `type`, COUNT(*) AS `total` FROM `" . DB_PREFIX . $type . "` WHERE `status` = '0'";
		}

		$rows = $this->db->query("SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `disabled` WHERE `total` > 0 ORDER BY `type`")->rows;

		return CheckResult::found('garbage_disabled_summary', $this->checks['garbage_disabled_summary']['severity'], (int)array_sum(array_column($rows, 'total')), $rows);
	}

	/**
	 * Discount and special rows of existing products whose end date has passed
	 * (today counts as passed), one finding row per row. A row whose start is
	 * not before its end is skipped: it can never apply and belongs to
	 * product_discount_dates_inverted. Rows may also appear in
	 * product_discount_not_lower, which ignores dates. The cart and storefront apply a row only
	 * between its dates, so an expired row only clutters the product's
	 * Discount tab. Rows of deleted products belong to
	 * garbage_orphan_product_data. Findings carry the edit button to the
	 * product form.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function garbageExpiredDiscount(): CheckResult {
		return $this->collect('garbage_expired_discount', $this->productSelect($this->discountKind() . " AS `kind`, `dc`.`customer_group_id`, `dc`.`quantity`, `dc`.`type`, `dc`.`price` AS `value`, `dc`.`date_end`") . " INNER JOIN `" . DB_PREFIX . "product_discount` `dc` ON (`dc`.`product_id` = `p`.`product_id`) WHERE `dc`.`date_end` != '0000-00-00' AND `dc`.`date_end` <= CURDATE() AND NOT (`dc`.`date_start` != '0000-00-00' AND `dc`.`date_start` >= `dc`.`date_end`) ORDER BY `p`.`product_id`, `kind`, `dc`.`date_end`");
	}

	/**
	 * Cleanup of garbage_orphan_description: deletes the description rows of
	 * deleted records, all languages, in every `*_description` table. Same
	 * FROM/WHERE as the check, so it deletes every description row behind the
	 * records the check lists.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function garbageOrphanDescriptionCleanup(): array {
		$parts = [];

		foreach ($this->orphanDescriptionParts() as $part) {
			$parts[] = ['alias' => 'd', 'from' => $part['from'], 'where' => $part['where']];
		}

		return $parts;
	}

	/**
	 * Cleanup of garbage_description_bad_language: deletes the description
	 * rows of a deleted language of an existing record. Same FROM/WHERE as the
	 * check, so it deletes exactly the rows the check counts.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function garbageDescriptionBadLanguageCleanup(): array {
		$parts = [];

		foreach ($this->badLanguageParts() as $part) {
			$parts[] = ['alias' => 'd', 'from' => $part['from'], 'where' => $part['where']];
		}

		return $parts;
	}

	/**
	 * Cleanup of garbage_orphan_link: deletes the link rows of deleted owners.
	 * Same FROM/WHERE as the check. The coupon history is reported but kept:
	 * the core coupon report joins it with a LEFT JOIN and still lists the
	 * orders. Deleting option_value or custom_field_value rows leaves their
	 * descriptions behind; they show up in garbage_orphan_description on the
	 * next run and are cleaned there.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function garbageOrphanLinkCleanup(): array {
		$parts = [];

		foreach ($this->linkParts() as $table => $part) {
			if ($table == 'coupon_history') {
				continue;
			}

			$parts[] = ['alias' => 't', 'from' => $part['from'], 'where' => $part['where']];
		}

		return $parts;
	}

	/**
	 * Cleanup of garbage_link_deleted_target: deletes the links of an existing
	 * owner to a deleted store, category or filter. Same FROM/WHERE as the
	 * check, with two differences. The coupon links (coupon_category,
	 * coupon_product) are never deleted: a coupon left without its
	 * restrictions would apply to the whole cart. A product link to a deleted
	 * category is skipped while a coupon still refers to that category: the
	 * storefront's getTotalCategoriesByCategoryId does not look at the product,
	 * so removing the link would change which carts the coupon applies to. So
	 * the cleanup can delete fewer rows than the check lists.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function garbageLinkDeletedTargetCleanup(): array {
		$parts = [];

		foreach ($this->targetParts() as $type => $part) {
			if (in_array($type, ['coupon_product', 'coupon_category'])) {
				continue;
			}

			$where = $part['where'];

			if ($type == 'product_to_category') {
				$where .= " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "coupon_category` `cc` WHERE `cc`.`category_id` = `t`.`category_id`)";
			}

			$parts[] = ['alias' => 't', 'from' => $part['from'], 'where' => $where];
		}

		return $parts;
	}

	/**
	 * Cleanup of garbage_orphan_product_data: deletes the rows of deleted
	 * products in every listed table. Nothing shows this data; only the admin
	 * attribute and option delete guards count it, so the cleanup also unblocks
	 * those deletes. Same FROM/WHERE as the check.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function garbageOrphanProductDataCleanup(): array {
		$parts = [];

		foreach ($this->orphanProductDataParts() as $part) {
			$parts[] = ['alias' => 't', 'from' => $part['from'], 'where' => $part['where']];
		}

		return $parts;
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
	 * name in the link table and in the owner table. An optional third element
	 * is the reported type code, for tables whose name is already a G1 type
	 * with another meaning.
	 *
	 * @return array<string, array{0: string, 1: string, 2?: string}> link table => [owner table, owner column, type code]
	 */
	private function linkTables(): array {
		return [
			'product_to_category'         => ['product', 'product_id'],
			'product_to_store'            => ['product', 'product_id'],
			'product_to_download'         => ['product', 'product_id'],
			'product_to_layout'           => ['product', 'product_id'],
			'category_to_store'           => ['category', 'category_id'],
			'category_to_layout'          => ['category', 'category_id'],
			'category_filter'             => ['category', 'category_id'],
			'category_path'               => ['category', 'category_id'],
			'information_to_store'        => ['information', 'information_id'],
			'information_to_layout'       => ['information', 'information_id'],
			'manufacturer_to_store'       => ['manufacturer', 'manufacturer_id'],
			'manufacturer_to_layout'      => ['manufacturer', 'manufacturer_id'],
			'article_to_store'            => ['article', 'article_id'],
			'article_to_layout'           => ['article', 'article_id'],
			'topic_to_store'              => ['topic', 'topic_id'],
			'topic_to_layout'             => ['topic', 'topic_id'],
			'coupon_product'              => ['coupon', 'coupon_id'],
			'coupon_category'             => ['coupon', 'coupon_id'],
			'coupon_history'              => ['coupon', 'coupon_id'],
			'tax_rate_to_customer_group'  => ['tax_rate', 'tax_rate_id'],
			'zone_to_geo_zone'            => ['geo_zone', 'geo_zone_id'],
			'custom_field_customer_group' => ['custom_field', 'custom_field_id'],
			'custom_field_value'          => ['custom_field', 'custom_field_id', 'custom_field_values'],
			'option_value'                => ['option', 'option_id', 'option_values'],
			'banner_image'                => ['banner', 'banner_id'],
			'article_comment'             => ['article', 'article_id'],
			'marketing_report'            => ['marketing', 'marketing_id'],
			'download_report'             => ['download', 'download_id']
		];
	}

	/**
	 * Link tables checked for a deleted target; the owner and the target
	 * columns have the same name in the link table and in their own tables.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string}> link table => [owner table, owner column, target table, target column]
	 */
	private function targetLinks(): array {
		return [
			'product_to_store'       => ['product', 'product_id', 'store', 'store_id'],
			'product_to_layout'      => ['product', 'product_id', 'store', 'store_id'],
			'category_to_store'      => ['category', 'category_id', 'store', 'store_id'],
			'category_to_layout'     => ['category', 'category_id', 'store', 'store_id'],
			'information_to_store'   => ['information', 'information_id', 'store', 'store_id'],
			'information_to_layout'  => ['information', 'information_id', 'store', 'store_id'],
			'manufacturer_to_store'  => ['manufacturer', 'manufacturer_id', 'store', 'store_id'],
			'manufacturer_to_layout' => ['manufacturer', 'manufacturer_id', 'store', 'store_id'],
			'article_to_store'       => ['article', 'article_id', 'store', 'store_id'],
			'article_to_layout'      => ['article', 'article_id', 'store', 'store_id'],
			'topic_to_store'         => ['topic', 'topic_id', 'store', 'store_id'],
			'topic_to_layout'        => ['topic', 'topic_id', 'store', 'store_id'],
			'product_to_category'    => ['product', 'product_id', 'category', 'category_id'],
			'coupon_category'        => ['coupon', 'coupon_id', 'category', 'category_id'],
			'coupon_product'         => ['coupon', 'coupon_id', 'product', 'product_id'],
			'product_filter'         => ['product', 'product_id', 'filter', 'filter_id']
		];
	}

	/**
	 * FROM and WHERE of the orphan descriptions of each type, shared by the
	 * check and its cleanup (description table aliased `d`).
	 *
	 * @return array<string, array{from: string, where: string}> type => parts
	 */
	private function orphanDescriptionParts(): array {
		$parts = [];

		foreach (array_keys($this->descriptionTables()) as $type) {
			$parts[$type] = [
				'from'  => "`" . DB_PREFIX . $type . "_description` `d`",
				'where' => "NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $type . "` `p` WHERE `p`.`" . $type . "_id` = `d`.`" . $type . "_id`)"
			];
		}

		return $parts;
	}

	/**
	 * FROM and WHERE of the descriptions in a deleted language of an existing
	 * record, shared by the check and its cleanup (description table aliased `d`).
	 *
	 * @return array<string, array{from: string, where: string}> type => parts
	 */
	private function badLanguageParts(): array {
		$parts = [];

		foreach (array_keys($this->descriptionTables()) as $type) {
			$parts[$type] = [
				'from'  => "`" . DB_PREFIX . $type . "_description` `d`",
				'where' => "EXISTS (SELECT 1 FROM `" . DB_PREFIX . $type . "` `p` WHERE `p`.`" . $type . "_id` = `d`.`" . $type . "_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "language` `l` WHERE `l`.`language_id` = `d`.`language_id`)"
			];
		}

		return $parts;
	}

	/**
	 * Type, owner column, FROM and WHERE of the links of deleted owners, shared
	 * by the check and its cleanup (link table aliased `t`).
	 *
	 * @return array<string, array{type: string, column: string, from: string, where: string}> link table => parts
	 */
	private function linkParts(): array {
		$parts = [];

		foreach ($this->linkTables() as $table => $link) {
			[$owner, $column] = $link;

			$parts[$table] = [
				'type'   => $link[2] ?? $table,
				'column' => $column,
				'from'   => "`" . DB_PREFIX . $table . "` `t`",
				'where'  => "NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $owner . "` `o` WHERE `o`.`" . $column . "` = `t`.`" . $column . "`)"
			];
		}

		return $parts;
	}

	/**
	 * Owner and target columns, FROM and WHERE of the links to a deleted
	 * target, shared by the check and its cleanup (link table aliased `t`).
	 *
	 * @return array<string, array{owner_column: string, target_column: string, from: string, where: string}> link table => parts
	 */
	private function targetParts(): array {
		$parts = [];

		foreach ($this->targetLinks() as $type => [$owner, $owner_column, $target, $target_column]) {
			$parts[$type] = [
				'owner_column'  => $owner_column,
				'target_column' => $target_column,
				'from'          => "`" . DB_PREFIX . $type . "` `t`",
				'where'         => "EXISTS (SELECT 1 FROM `" . DB_PREFIX . $owner . "` `o` WHERE `o`.`" . $owner_column . "` = `t`.`" . $owner_column . "`)" . ($target == 'store' ? " AND `t`.`store_id` != '0'" : "") . " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $target . "` `g` WHERE `g`.`" . $target_column . "` = `t`.`" . $target_column . "`)"
			];
		}

		return $parts;
	}

	/**
	 * FROM and WHERE of the data of deleted products of each table (aliased
	 * `t`). product_viewed belongs to the "Products Viewed" report extension
	 * and is left out when its table does not exist.
	 *
	 * @return array<string, array{from: string, where: string}> table => parts
	 */
	private function orphanProductDataParts(): array {
		$tables = ['product_attribute', 'product_code', 'product_discount', 'product_filter', 'product_image', 'product_option', 'product_option_value', 'product_related', 'product_report', 'product_reward', 'product_subscription', 'product_viewed', 'review'];

		// "_" is a LIKE wildcard; escape it after the string escaping so the backslash survives.
		if (!$this->db->query("SHOW TABLES LIKE '" . str_replace('_', '\\_', $this->db->escape(DB_PREFIX . "product_viewed")) . "'")->num_rows) {
			$tables = array_diff($tables, ['product_viewed']);
		}

		$parts = [];

		foreach ($tables as $type) {
			$parts[$type] = [
				'from'  => "`" . DB_PREFIX . $type . "` `t`",
				'where' => "NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product` `p` WHERE `p`.`product_id` = `t`.`product_id`)"
			];
		}

		return $parts;
	}
}
