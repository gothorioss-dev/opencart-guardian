<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Seo
 *
 * Category SEO (SQL part): SEO URL keywords and meta data of products,
 * categories, information pages and manufacturers. Findings come from
 * different records, so each check declares its own edit column.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Seo extends Base {
	protected array $checks = [
		'seo_duplicate_keyword'                => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_empty_keyword'                    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_invalid_keyword'                  => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_orphan_url'                       => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_orphan_store_language'            => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_missing_url'                      => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_urls_disabled'                    => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'seo_missing_url_category'             => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_missing_url_information'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_missing_url_manufacturer'         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'manufacturer_id'],
		'seo_incomplete_language'              => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_incomplete_language_category'     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_incomplete_language_information'  => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_incomplete_language_manufacturer' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'manufacturer_id']
	];

	/**
	 * Keywords that lead to different records in one store, one finding row
	 * per SEO URL; `targets` is the number of records sharing the keyword.
	 * The storefront looks a keyword up by store only, without the language,
	 * and takes the first match (catalog/model/design/seo_url.php), so all
	 * but one of those records become unreachable by their SEO URL. The same
	 * keyword for the same record in several languages is not a conflict:
	 * adding a language copies every SEO URL of the default one. Keywords
	 * compare as the storefront does, through the case-insensitive collation.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateKeyword(): CheckResult {
		return $this->collect('seo_duplicate_keyword', $this->seoUrlSelect('`g`.`targets`') . " INNER JOIN (SELECT `store_id`, `keyword`, COUNT(DISTINCT `key`, `value`) AS `targets` FROM `" . DB_PREFIX . "seo_url` WHERE `keyword` != '' GROUP BY `store_id`, `keyword` HAVING `targets` > 1) `g` ON (`g`.`store_id` = `su`.`store_id` AND `g`.`keyword` = `su`.`keyword`) ORDER BY `su`.`store_id`, `su`.`keyword`, `su`.`seo_url_id`");
	}

	/**
	 * SEO URLs with an empty keyword. With SEO URLs on, the storefront still
	 * matches the record and drops its id from the link, but has no path to
	 * add (catalog/controller/startup/seo_url.php), so links to the page lead
	 * to the store's home page. Every admin form requires 1-64 characters, so
	 * these come from imports, and the page cannot be saved until it is set.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyKeyword(): CheckResult {
		return $this->collect('seo_empty_keyword', $this->seoUrlSelect() . " WHERE TRIM(`su`.`keyword`) = '' ORDER BY `su`.`store_id`, `su`.`key`, `su`.`value`, `su`.`seo_url_id`");
	}

	/**
	 * Keywords with characters the admin refuses: the same rule as core's
	 * oc_validate_path() (Latin, Cyrillic and Greek letters in any case,
	 * digits, / - _), so these come from imports. Cyrillic and upper case are
	 * valid; the storefront compares keywords case-insensitively.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoInvalidKeyword(): CheckResult {
		return $this->collect('seo_invalid_keyword', $this->seoUrlSelect() . " WHERE `su`.`keyword` REGEXP '[^\\\\p{Latin}\\\\p{Cyrillic}\\\\p{Greek}0-9/_-]' ORDER BY `su`.`store_id`, `su`.`keyword`, `su`.`seo_url_id`");
	}

	/**
	 * SEO URLs of a deleted record. Every admin delete removes the record's
	 * SEO URLs, so these come from imports or direct SQL. The keyword stays
	 * taken: the admin refuses it for another page of the store. A category
	 * path points at its last id; `language` and `route` keys name no record.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoOrphanUrl(): CheckResult {
		// SEO URL key => [table, id column, record id taken from `su`.`value`]
		$targets = [
			'product_id'      => ['product', 'product_id', "`su`.`value`"],
			'path'            => ['category', 'category_id', "SUBSTRING_INDEX(`su`.`value`, '_', -1)"],
			'manufacturer_id' => ['manufacturer', 'manufacturer_id', "`su`.`value`"],
			'information_id'  => ['information', 'information_id', "`su`.`value`"],
			'article_id'      => ['article', 'article_id', "`su`.`value`"],
			'topic_id'        => ['topic', 'topic_id', "`su`.`value`"]
		];

		$implode = [];

		foreach ($targets as $key => [$table, $column, $id]) {
			$implode[] = "(`su`.`key` = '" . $key . "' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $table . "` `t` WHERE `t`.`" . $column . "` = " . $id . "))";
		}

		return $this->collect('seo_orphan_url', $this->seoUrlSelect() . " WHERE " . implode(" OR ", $implode) . " ORDER BY `su`.`store_id`, `su`.`key`, `su`.`value`, `su`.`seo_url_id`");
	}

	/**
	 * SEO URLs of a deleted store or language. Deleting either in the admin
	 * removes its SEO URLs, so these come from imports or direct SQL. The
	 * default store (0) has no store row. A row of a deleted language still
	 * takes its keyword in its store, as the admin checks keywords per store.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoOrphanStoreLanguage(): CheckResult {
		return $this->collect('seo_orphan_store_language', $this->seoUrlSelect('`su`.`language_id`') . " WHERE (`su`.`store_id` != '0' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `s` WHERE `s`.`store_id` = `su`.`store_id`)) OR `l`.`language_id` IS NULL ORDER BY `su`.`store_id`, `su`.`language_id`, `su`.`seo_url_id`");
	}

	/**
	 * Enabled products without an SEO URL in a store they are available in,
	 * one finding row per store; only stores with SEO URLs on. A URL in any
	 * language is enough here: gaps between languages are a separate check.
	 * Links of deleted stores belong to GARBAGE.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMissingUrl(): CheckResult {
		// CONCAT keeps the `query` index on the varchar `value`, and unlike
		// CAST ... AS CHAR takes the column's collation instead of failing.
		return $this->collect('seo_missing_url', "SELECT `p`.`product_id`, `pd`.`name`, `p2s`.`store_id` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND " . $this->seoUrlStore("`p2s`.`store_id`") . " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'product_id' AND `su`.`value` = CONCAT(`p`.`product_id`) AND `su`.`store_id` = `p2s`.`store_id`) ORDER BY `p2s`.`store_id`, `p`.`product_id`");
	}

	/**
	 * Stores with SEO URLs off, one finding row per store: the storefront
	 * ignores their keywords and the missing URL check skips them. May be
	 * intended, hence only a note.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoUrlsDisabled(): CheckResult {
		return $this->collect('seo_urls_disabled', "SELECT `st`.`store_id` FROM (SELECT 0 AS `store_id` UNION SELECT `store_id` FROM `" . DB_PREFIX . "store`) `st` WHERE " . $this->seoUrlSetting("`st`.`store_id`") . " != '1' ORDER BY `st`.`store_id`");
	}

	/**
	 * Enabled categories without an SEO URL in a store they are available in,
	 * as the missing URL check does for products. A category's URL is keyed by
	 * its path, so its own id is the last one, as in the orphan URL check.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMissingUrlCategory(): CheckResult {
		return $this->collect('seo_missing_url_category', "SELECT `c`.`category_id`, `cd`.`name`, `c2s`.`store_id` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id`) LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND " . $this->seoUrlStore("`c2s`.`store_id`") . " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'path' AND SUBSTRING_INDEX(`su`.`value`, '_', -1) = `c`.`category_id` AND `su`.`store_id` = `c2s`.`store_id`) ORDER BY `c2s`.`store_id`, `c`.`category_id`");
	}

	/**
	 * Enabled information pages without an SEO URL in a store they are
	 * available in, as the missing URL check does for products.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMissingUrlInformation(): CheckResult {
		return $this->collect('seo_missing_url_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `i2s`.`store_id` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s` ON (`i2s`.`information_id` = `i`.`information_id`) LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND " . $this->seoUrlStore("`i2s`.`store_id`") . " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'information_id' AND `su`.`value` = CONCAT(`i`.`information_id`) AND `su`.`store_id` = `i2s`.`store_id`) ORDER BY `i2s`.`store_id`, `i`.`information_id`");
	}

	/**
	 * Manufacturers without an SEO URL in a store they are available in, as
	 * the missing URL check does for products. Manufacturers have no status,
	 * so every store link counts.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMissingUrlManufacturer(): CheckResult {
		return $this->collect('seo_missing_url_manufacturer', "SELECT `m`.`manufacturer_id`, `m`.`name`, `m2s`.`store_id` FROM `" . DB_PREFIX . "manufacturer` `m` INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id`) WHERE " . $this->seoUrlStore("`m2s`.`store_id`") . " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'manufacturer_id' AND `su`.`value` = CONCAT(`m`.`manufacturer_id`) AND `su`.`store_id` = `m2s`.`store_id`) ORDER BY `m2s`.`store_id`, `m`.`manufacturer_id`");
	}

	/**
	 * Enabled products with an SEO URL in a store but not in every enabled
	 * language, one finding row per store with the missing language names.
	 * The storefront rewrites a link only by a URL of the current language
	 * (catalog/model/design/seo_url.php) and offers enabled languages only.
	 * Products with no URL at all belong to the missing URL check; a row of a
	 * disabled or deleted language counts as a URL there but fills no
	 * language here, so such products list every enabled language.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoIncompleteLanguage(): CheckResult {
		$seo_url = "SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'product_id' AND `su`.`value` = CONCAT(`p`.`product_id`) AND `su`.`store_id` = `p2s`.`store_id`";

		return $this->collect('seo_incomplete_language', "SELECT `p`.`product_id`, `pd`.`name`, `p2s`.`store_id`, GROUP_CONCAT(`l`.`name` ORDER BY `l`.`sort_order`, `l`.`name` SEPARATOR ', ') AS `language` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND " . $this->seoUrlStore("`p2s`.`store_id`") . " AND EXISTS (" . $seo_url . ") AND NOT EXISTS (" . $seo_url . " AND `su`.`language_id` = `l`.`language_id`) GROUP BY `p`.`product_id`, `pd`.`name`, `p2s`.`store_id` ORDER BY `p2s`.`store_id`, `p`.`product_id`");
	}

	/**
	 * Enabled categories with an SEO URL in a store but not in every enabled
	 * language, as the incomplete language check does for products. The own
	 * id is the last one of the path, as in the missing URL check for
	 * categories.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoIncompleteLanguageCategory(): CheckResult {
		$seo_url = "SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'path' AND SUBSTRING_INDEX(`su`.`value`, '_', -1) = `c`.`category_id` AND `su`.`store_id` = `c2s`.`store_id`";

		return $this->collect('seo_incomplete_language_category', "SELECT `c`.`category_id`, `cd`.`name`, `c2s`.`store_id`, GROUP_CONCAT(`l`.`name` ORDER BY `l`.`sort_order`, `l`.`name` SEPARATOR ', ') AS `language` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND " . $this->seoUrlStore("`c2s`.`store_id`") . " AND EXISTS (" . $seo_url . ") AND NOT EXISTS (" . $seo_url . " AND `su`.`language_id` = `l`.`language_id`) GROUP BY `c`.`category_id`, `cd`.`name`, `c2s`.`store_id` ORDER BY `c2s`.`store_id`, `c`.`category_id`");
	}

	/**
	 * Enabled information pages with an SEO URL in a store but not in every
	 * enabled language, as the incomplete language check does for products.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoIncompleteLanguageInformation(): CheckResult {
		$seo_url = "SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'information_id' AND `su`.`value` = CONCAT(`i`.`information_id`) AND `su`.`store_id` = `i2s`.`store_id`";

		return $this->collect('seo_incomplete_language_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `i2s`.`store_id`, GROUP_CONCAT(`l`.`name` ORDER BY `l`.`sort_order`, `l`.`name` SEPARATOR ', ') AS `language` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s` ON (`i2s`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND " . $this->seoUrlStore("`i2s`.`store_id`") . " AND EXISTS (" . $seo_url . ") AND NOT EXISTS (" . $seo_url . " AND `su`.`language_id` = `l`.`language_id`) GROUP BY `i`.`information_id`, `id`.`title`, `i2s`.`store_id` ORDER BY `i2s`.`store_id`, `i`.`information_id`");
	}

	/**
	 * Manufacturers with an SEO URL in a store but not in every enabled
	 * language, as the incomplete language check does for products.
	 * Manufacturers have no status, so every store link counts.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoIncompleteLanguageManufacturer(): CheckResult {
		$seo_url = "SELECT 1 FROM `" . DB_PREFIX . "seo_url` `su` WHERE `su`.`key` = 'manufacturer_id' AND `su`.`value` = CONCAT(`m`.`manufacturer_id`) AND `su`.`store_id` = `m2s`.`store_id`";

		return $this->collect('seo_incomplete_language_manufacturer', "SELECT `m`.`manufacturer_id`, `m`.`name`, `m2s`.`store_id`, GROUP_CONCAT(`l`.`name` ORDER BY `l`.`sort_order`, `l`.`name` SEPARATOR ', ') AS `language` FROM `" . DB_PREFIX . "manufacturer` `m` INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`status` = '1') WHERE " . $this->seoUrlStore("`m2s`.`store_id`") . " AND EXISTS (" . $seo_url . ") AND NOT EXISTS (" . $seo_url . " AND `su`.`language_id` = `l`.`language_id`) GROUP BY `m`.`manufacturer_id`, `m`.`name`, `m2s`.`store_id` ORDER BY `m2s`.`store_id`, `m`.`manufacturer_id`");
	}

	/**
	 * SQL condition: an existing store (the default store has no row) with
	 * SEO URLs on.
	 *
	 * @param string $store_id SQL expression of the store id
	 *
	 * @return string
	 */
	private function seoUrlStore(string $store_id): string {
		return "(" . $store_id . " = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = " . $store_id . ")) AND " . $this->seoUrlSetting($store_id) . " = '1'";
	}

	/**
	 * A store's effective config_seo_url, as the storefront reads it: the
	 * store's own value over the default store's. Only the default store's
	 * form has the field, so an own value comes from imports or direct SQL.
	 *
	 * @param string $store_id SQL expression of the store id
	 *
	 * @return string SQL expression, '0' when the setting is absent
	 */
	private function seoUrlSetting(string $store_id): string {
		$setting = "SELECT `value` FROM `" . DB_PREFIX . "setting` `s` WHERE `s`.`key` = 'config_seo_url' AND `s`.`store_id` = ";

		return "COALESCE((" . $setting . $store_id . "), (" . $setting . "'0'), '0')";
	}

	/**
	 * Common finding columns of an SEO URL, as the Design > SEO URL list shows it.
	 *
	 * @param string $columns extra select list
	 *
	 * @return string SELECT ... FROM seo_url `su` with the language joined, no WHERE
	 */
	private function seoUrlSelect(string $columns = ''): string {
		return "SELECT `su`.`seo_url_id`, `su`.`store_id`, `l`.`code` AS `language`, `su`.`keyword`, `su`.`key`, `su`.`value`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "seo_url` `su` LEFT JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `su`.`language_id`)";
	}
}
