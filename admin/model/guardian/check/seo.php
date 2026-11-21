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
	/**
	 * Inclusive acceptable length range, in characters, for
	 * the seo_meta_description_length* checks; to be moved to settings in M7.
	 */
	private const META_DESCRIPTION_MIN = 50;
	private const META_DESCRIPTION_MAX = 160;

	/**
	 * Minimum length, in characters, of a non-empty description for
	 * the seo_thin_description* checks; to be moved to settings in M7.
	 */
	private const THIN_DESCRIPTION_MIN = 50;

	protected array $checks = [
		'seo_duplicate_keyword'                   => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_empty_keyword'                       => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_invalid_keyword'                     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_orphan_url'                          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id', 'clean' => true],
		'seo_orphan_store_language'               => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_missing_url'                         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_urls_disabled'                       => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'seo_missing_url_category'                => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_missing_url_information'             => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_missing_url_manufacturer'            => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'manufacturer_id'],
		'seo_incomplete_language'                 => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_incomplete_language_category'        => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_incomplete_language_information'     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_incomplete_language_manufacturer'    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'manufacturer_id'],
		'seo_reserved_keyword'                    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_empty_meta_title'                    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_empty_meta_title_category'           => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_empty_meta_title_information'        => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_empty_meta_description'              => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_empty_meta_description_category'     => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_empty_meta_description_information'  => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_meta_description_length'             => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_meta_description_length_category'    => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_meta_description_length_information' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_duplicate_meta_title'                => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_duplicate_meta_title_category'       => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_duplicate_meta_title_information'    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_thin_description'                    => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_thin_description_category'           => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_thin_description_information'        => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'information_id'],
		'seo_duplicate_description'               => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'product_id'],
		'seo_duplicate_description_category'      => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'category_id'],
		'seo_duplicate_description_information'   => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'information_id']
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
	 * The storefront opens no page for such an address (the record is not
	 * found); the cleanup deletes the rows and so frees their keywords.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoOrphanUrl(): CheckResult {
		return $this->collect('seo_orphan_url', $this->seoUrlSelect() . " WHERE " . $this->orphanUrlWhere() . " ORDER BY `su`.`store_id`, `su`.`key`, `su`.`value`, `su`.`seo_url_id`");
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
	 * Keywords equal to the name of a folder in the store root, in stores with
	 * SEO URLs on. The web server serves an existing file or folder before the
	 * request reaches the SEO route (.htaccess.txt: !-f, !-d), so the address
	 * opens the folder instead of the page. The folders are scanned, not
	 * listed: the admin folder is often renamed and hosts add their own.
	 * The whole keyword is compared because the whole request path is tested;
	 * paths of real sub-folders (catalog/view) are not modelled. Only rows that
	 * can start an address count: a language keyword, or any keyword where the
	 * store has no keyword for that language, as storefront links carry the
	 * language first; so core's own `catalog` route keyword stays behind the
	 * language prefix. A route keyword in front (/product/...) is not
	 * modelled, so in a store without language keywords such a row is
	 * reported although it works. Compared case-insensitively, as core
	 * compares keywords; on a case-sensitive host `Admin` really works. Root
	 * files all have a dot, which the invalid keyword check already reports.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoReservedKeyword(): CheckResult {
		$folders = array_map('basename', glob(DIR_OPENCART . '*', GLOB_ONLYDIR) ?: []);

		// IN () is invalid SQL, and without folders nothing can clash.
		if (!$folders) {
			return CheckResult::ok('seo_reserved_keyword', $this->checks['seo_reserved_keyword']['severity']);
		}

		$implode = [];

		foreach ($folders as $folder) {
			$implode[] = "'" . $this->db->escape($folder) . "'";
		}

		return $this->collect('seo_reserved_keyword', $this->seoUrlSelect() . " WHERE `su`.`keyword` IN (" . implode(", ", $implode) . ") AND " . $this->seoUrlStore("`su`.`store_id`") . " AND (`su`.`key` = 'language' OR NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "seo_url` `sl` WHERE `sl`.`key` = 'language' AND `sl`.`value` = `l`.`code` AND `sl`.`store_id` = `su`.`store_id` AND `sl`.`language_id` = `su`.`language_id`)) ORDER BY `su`.`store_id`, `su`.`keyword`, `su`.`seo_url_id`");
	}

	/**
	 * Enabled products with an empty meta title in an enabled language, one
	 * finding row per language. The product page sets it as the page title
	 * as is (catalog/controller/product/product.php), with no fallback to the
	 * product name and whatever the SEO URL setting, so the page has an empty
	 * <title>. The admin form requires 1-255 characters, so these come from
	 * imports or direct SQL. Only products linked to an existing store have a
	 * page: no link at all is product_no_store, a link only to a deleted store
	 * belongs to GARBAGE. Products available later are still reported, as
	 * they get a page then. A missing description row is
	 * product_incomplete_description; MySQL TRIM strips spaces only.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaTitle(): CheckResult {
		return $this->collect('seo_empty_meta_title', "SELECT `p`.`product_id`, `pd`.`name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND TRIM(`pd2`.`meta_title`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_store` `p2s` WHERE `p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) ORDER BY `p`.`product_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled categories with an empty meta title in an enabled language, one
	 * finding row per language. The category page sets it as the page title
	 * as is (catalog/controller/product/category.php), with no fallback to the
	 * category name and whatever the SEO URL setting; the admin form requires
	 * 1-255 characters. Store link and TRIM rules as in seoEmptyMetaTitle();
	 * a missing description row is category_incomplete_description.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaTitleCategory(): CheckResult {
		return $this->collect('seo_empty_meta_title_category', "SELECT `c`.`category_id`, `cd`.`name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND TRIM(`cd2`.`meta_title`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_to_store` `c2s` WHERE `c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) ORDER BY `c`.`category_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled information pages with an empty meta title in an enabled
	 * language, one finding row per language. The information page sets it as
	 * the page title as is (catalog/controller/information/information.php),
	 * with no fallback to the page title field and whatever the SEO URL
	 * setting; the admin form requires 1-255 characters. Store link and TRIM
	 * rules as in seoEmptyMetaTitle().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaTitleInformation(): CheckResult {
		return $this->collect('seo_empty_meta_title_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND TRIM(`id2`.`meta_title`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "information_to_store` `i2s` WHERE `i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) ORDER BY `i`.`information_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled products with an empty meta description in an enabled language,
	 * one finding row per language. The product page passes it to the
	 * document as is (catalog/controller/product/product.php), with no
	 * fallback to the product description and whatever the SEO URL setting,
	 * and the header omits the description meta tag when it is empty
	 * (catalog/view/template/common/header.twig). The admin form does not
	 * require the field, so an empty value is a normal choice: info, not a
	 * defect. Store link and TRIM rules as in seoEmptyMetaTitle(); a tab-only
	 * value still outputs a blank tag but is not reported, as MySQL TRIM
	 * strips spaces only.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaDescription(): CheckResult {
		return $this->collect('seo_empty_meta_description', "SELECT `p`.`product_id`, `pd`.`name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND TRIM(`pd2`.`meta_description`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_store` `p2s` WHERE `p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) ORDER BY `p`.`product_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled categories with an empty meta description in an enabled
	 * language, one finding row per language. The category page passes it to
	 * the document as is (catalog/controller/product/category.php), with no
	 * fallback to the category description and whatever the SEO URL setting,
	 * and the header omits the description meta tag when it is empty. The
	 * admin form does not require the field, so this is info, not a defect.
	 * Store link and TRIM rules as in seoEmptyMetaDescription(); a missing
	 * description row is category_incomplete_description.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaDescriptionCategory(): CheckResult {
		return $this->collect('seo_empty_meta_description_category', "SELECT `c`.`category_id`, `cd`.`name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND TRIM(`cd2`.`meta_description`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_to_store` `c2s` WHERE `c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) ORDER BY `c`.`category_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled information pages with an empty meta description in an enabled
	 * language, one finding row per language. The information page passes it
	 * to the document as is (catalog/controller/information/information.php),
	 * with no fallback to the page description and whatever the SEO URL
	 * setting, and the header omits the description meta tag when it is
	 * empty. The admin form does not require the field, so this is info, not
	 * a defect. Store link and TRIM rules as in seoEmptyMetaDescription().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoEmptyMetaDescriptionInformation(): CheckResult {
		return $this->collect('seo_empty_meta_description_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `l`.`name` AS `language` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND TRIM(`id2`.`meta_description`) = '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "information_to_store` `i2s` WHERE `i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) ORDER BY `i`.`information_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled products whose non-empty meta description in an enabled
	 * language is shorter than META_DESCRIPTION_MIN or longer than
	 * META_DESCRIPTION_MAX characters, one finding row per language. The
	 * header outputs the stored text raw (Twig autoescape is off), so the
	 * owner in the admin field and search engines read it with entities
	 * decoded; the length therefore reverses the four entities that
	 * Request::clean() stores for admin input (&amp; last, as a browser
	 * would), while other entities, e.g. from imports, count as stored. The
	 * admin form does not validate the length, so this is info. TRIM only
	 * matters for imported values, admin-saved ones are already trimmed by
	 * Request::clean(). Empty values are seoEmptyMetaDescription(); a
	 * tab-only value is reported here, as MySQL TRIM strips spaces only.
	 * Store link and language filters as in seoEmptyMetaDescription().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMetaDescriptionLength(): CheckResult {
		return $this->collect('seo_meta_description_length', "SELECT `p`.`product_id`, `pd`.`name`, `l`.`name` AS `language`, " . $this->metaDescriptionLength('`pd2`.`meta_description`') . " AS `length` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND TRIM(`pd2`.`meta_description`) != '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_store` `p2s` WHERE `p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) HAVING `length` < '" . (int)self::META_DESCRIPTION_MIN . "' OR `length` > '" . (int)self::META_DESCRIPTION_MAX . "' ORDER BY `p`.`product_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled categories whose non-empty meta description in an enabled
	 * language is shorter than META_DESCRIPTION_MIN or longer than
	 * META_DESCRIPTION_MAX characters, one finding row per language. The
	 * category page passes it to the document as is
	 * (catalog/controller/product/category.php) and the admin form does not
	 * validate its length, so this is info. Length, TRIM and empty rules as
	 * in seoMetaDescriptionLength(); store link and language filters as in
	 * seoEmptyMetaDescriptionCategory().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMetaDescriptionLengthCategory(): CheckResult {
		return $this->collect('seo_meta_description_length_category', "SELECT `c`.`category_id`, `cd`.`name`, `l`.`name` AS `language`, " . $this->metaDescriptionLength('`cd2`.`meta_description`') . " AS `length` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND TRIM(`cd2`.`meta_description`) != '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_to_store` `c2s` WHERE `c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) HAVING `length` < '" . (int)self::META_DESCRIPTION_MIN . "' OR `length` > '" . (int)self::META_DESCRIPTION_MAX . "' ORDER BY `c`.`category_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled information pages whose non-empty meta description in an
	 * enabled language is shorter than META_DESCRIPTION_MIN or longer than
	 * META_DESCRIPTION_MAX characters, one finding row per language. The
	 * information page passes it to the document as is
	 * (catalog/controller/information/information.php) and the admin form
	 * does not validate its length, so this is info. Length, TRIM and empty
	 * rules as in seoMetaDescriptionLength(); store link and language filters
	 * as in seoEmptyMetaDescriptionInformation().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoMetaDescriptionLengthInformation(): CheckResult {
		return $this->collect('seo_meta_description_length_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `l`.`name` AS `language`, " . $this->metaDescriptionLength('`id2`.`meta_description`') . " AS `length` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND TRIM(`id2`.`meta_description`) != '' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "information_to_store` `i2s` WHERE `i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) HAVING `length` < '" . (int)self::META_DESCRIPTION_MIN . "' OR `length` > '" . (int)self::META_DESCRIPTION_MAX . "' ORDER BY `i`.`information_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled products that share the same meta title with another product in
	 * one store in the same language, one finding row per product, store and
	 * language. The product page sets it as the page title as is
	 * (catalog/controller/product/product.php) and the admin form validates
	 * only its length (admin/controller/catalog/product.php), not uniqueness.
	 * A group counts only if it spans more than one variant family: variants
	 * inherit the master's fields unless overridden and stay in its family
	 * either way (admin/model/catalog/product.php), so their equal titles are
	 * not a finding; variants with a missing master are
	 * product_broken_variant. Values are compared trimmed and by the column
	 * collation, so case and accents are ignored. A product counts in every
	 * existing store it is linked to; empty titles are seo_empty_meta_title.
	 * The group value alias is not `meta_title`, a real column name.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateMetaTitle(): CheckResult {
		return $this->collect('seo_duplicate_meta_title', "SELECT `p`.`product_id`, `pd`.`name`, `p2s`.`store_id`, `l`.`name` AS `language`, TRIM(`pd2`.`meta_title`) AS `meta_title`, `g`.`duplicates` FROM `" . DB_PREFIX . "product` `p` INNER JOIN (" . $this->variantFamilies() . ") `f2` ON (`f2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `p2s3`.`store_id`, `pd3`.`language_id`, TRIM(`pd3`.`meta_title`) AS `meta_key`, COUNT(DISTINCT `p3`.`product_id`) AS `duplicates` FROM `" . DB_PREFIX . "product` `p3` INNER JOIN (" . $this->variantFamilies() . ") `f` ON (`f`.`product_id` = `p3`.`product_id`) INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s3` ON (`p2s3`.`product_id` = `p3`.`product_id` AND (`p2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "product_description` `pd3` ON (`pd3`.`product_id` = `p3`.`product_id`) WHERE `p3`.`status` = '1' AND TRIM(`pd3`.`meta_title`) != '' GROUP BY `p2s3`.`store_id`, `pd3`.`language_id`, TRIM(`pd3`.`meta_title`) HAVING COUNT(DISTINCT `f`.`family`) > 1) `g` ON (`g`.`store_id` = `p2s`.`store_id` AND `g`.`language_id` = `pd2`.`language_id` AND `g`.`meta_key` = TRIM(`pd2`.`meta_title`)) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' ORDER BY `p2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`meta_key`, `p`.`product_id`");
	}

	/**
	 * Enabled categories that share the same meta title with another category
	 * in one store in the same language, one finding row per category, store
	 * and language. The category page sets it as the page title as is
	 * (catalog/controller/product/category.php) and the admin form validates
	 * only its length (admin/controller/catalog/category.php), not uniqueness.
	 * Values are compared trimmed and by the column collation, so case and
	 * accents are ignored. A category counts in every existing store it is
	 * linked to; empty titles are seo_empty_meta_title_category. Overlaps with
	 * category_duplicate_name, which compares another field (names under one
	 * parent), so the same category may be listed by both.
	 * The group value alias is not `meta_title`, a real column name.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateMetaTitleCategory(): CheckResult {
		return $this->collect('seo_duplicate_meta_title_category', "SELECT `c`.`category_id`, `cd`.`name`, `c2s`.`store_id`, `l`.`name` AS `language`, TRIM(`cd2`.`meta_title`) AS `meta_title`, `g`.`duplicates` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `c2s3`.`store_id`, `cd3`.`language_id`, TRIM(`cd3`.`meta_title`) AS `meta_key`, COUNT(DISTINCT `c3`.`category_id`) AS `duplicates` FROM `" . DB_PREFIX . "category` `c3` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s3` ON (`c2s3`.`category_id` = `c3`.`category_id` AND (`c2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "category_description` `cd3` ON (`cd3`.`category_id` = `c3`.`category_id`) WHERE `c3`.`status` = '1' AND TRIM(`cd3`.`meta_title`) != '' GROUP BY `c2s3`.`store_id`, `cd3`.`language_id`, TRIM(`cd3`.`meta_title`) HAVING COUNT(DISTINCT `c3`.`category_id`) > 1) `g` ON (`g`.`store_id` = `c2s`.`store_id` AND `g`.`language_id` = `cd2`.`language_id` AND `g`.`meta_key` = TRIM(`cd2`.`meta_title`)) LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' ORDER BY `c2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`meta_key`, `c`.`category_id`");
	}

	/**
	 * Enabled information pages that share the same meta title with another page
	 * in one store in the same language, one finding row per page, store
	 * and language. The information page sets it as the page title as is
	 * (catalog/controller/information/information.php) and the admin form validates
	 * only its length (admin/controller/catalog/information.php), not uniqueness.
	 * Values are compared trimmed and by the column collation, so case and
	 * accents are ignored. A page counts in every existing store it is
	 * linked to; empty titles are seo_empty_meta_title_information.
	 * The group value alias is not `meta_title`, a real column name.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateMetaTitleInformation(): CheckResult {
		return $this->collect('seo_duplicate_meta_title_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `i2s`.`store_id`, `l`.`name` AS `language`, TRIM(`id2`.`meta_title`) AS `meta_title`, `g`.`duplicates` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s` ON (`i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `i2s3`.`store_id`, `id3`.`language_id`, TRIM(`id3`.`meta_title`) AS `meta_key`, COUNT(DISTINCT `i3`.`information_id`) AS `duplicates` FROM `" . DB_PREFIX . "information` `i3` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s3` ON (`i2s3`.`information_id` = `i3`.`information_id` AND (`i2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "information_description` `id3` ON (`id3`.`information_id` = `i3`.`information_id`) WHERE `i3`.`status` = '1' AND TRIM(`id3`.`meta_title`) != '' GROUP BY `i2s3`.`store_id`, `id3`.`language_id`, TRIM(`id3`.`meta_title`) HAVING COUNT(DISTINCT `i3`.`information_id`) > 1) `g` ON (`g`.`store_id` = `i2s`.`store_id` AND `g`.`language_id` = `id2`.`language_id` AND `g`.`meta_key` = TRIM(`id2`.`meta_title`)) LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' ORDER BY `i2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`meta_key`, `i`.`information_id`");
	}

	/**
	 * Enabled products whose non-empty Description in an enabled language has
	 * fewer than THIN_DESCRIPTION_MIN characters of text, one finding row per
	 * language. The product page prints the description after
	 * html_entity_decode (catalog/controller/product/product.php); the admin
	 * form does not require it (admin/controller/catalog/product.php), so
	 * this is info. Length is that of descriptionText(): tags do not count and
	 * an entity counts as one character; a tag replaced by a space may add a
	 * character inside a word. Descriptions without any text or image are
	 * product_empty_content, a missing row is product_incomplete_description;
	 * an image-only description is reported here with length 0. Store link and
	 * language filters as in seoMetaDescriptionLength().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoThinDescription(): CheckResult {
		return $this->collect('seo_thin_description', "SELECT `p`.`product_id`, `pd`.`name`, `l`.`name` AS `language`, CHAR_LENGTH(" . $this->descriptionText('`pd2`.`description`') . ") AS `length` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND NOT (" . $this->descriptionEmpty('`pd2`.`description`') . ") AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_store` `p2s` WHERE `p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) HAVING `length` < '" . (int)self::THIN_DESCRIPTION_MIN . "' ORDER BY `p`.`product_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled categories whose non-empty Description in an enabled language has
	 * fewer than THIN_DESCRIPTION_MIN characters of text, one finding row per
	 * language. The category page prints the description above the product
	 * list (catalog/controller/product/category.php) and the admin form does
	 * not require it (admin/controller/catalog/category.php), so this is info.
	 * Length rules as in seoThinDescription(). Unlike products, completely
	 * empty descriptions are not reported anywhere, as the field is optional
	 * and many stores leave it empty; an image-only description is reported
	 * here with length 0. Store link and language filters as in
	 * seoMetaDescriptionLengthCategory().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoThinDescriptionCategory(): CheckResult {
		return $this->collect('seo_thin_description_category', "SELECT `c`.`category_id`, `cd`.`name`, `l`.`name` AS `language`, CHAR_LENGTH(" . $this->descriptionText('`cd2`.`description`') . ") AS `length` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' AND NOT (" . $this->descriptionEmpty('`cd2`.`description`') . ") AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_to_store` `c2s` WHERE `c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) HAVING `length` < '" . (int)self::THIN_DESCRIPTION_MIN . "' ORDER BY `c`.`category_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled information pages whose non-empty Description in an enabled
	 * language has fewer than THIN_DESCRIPTION_MIN characters of text, one
	 * finding row per language. The information page prints the description
	 * (catalog/controller/information/information.php) and the admin form does
	 * not require it (admin/controller/catalog/information.php), so this is info.
	 * Length rules as in seoThinDescription(). Unlike products, completely
	 * empty descriptions are not reported anywhere, as the field is optional
	 * and many stores leave it empty; an image-only description is reported
	 * here with length 0. Store link and language filters as in
	 * seoMetaDescriptionLengthInformation().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoThinDescriptionInformation(): CheckResult {
		return $this->collect('seo_thin_description_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `l`.`name` AS `language`, CHAR_LENGTH(" . $this->descriptionText('`id2`.`description`') . ") AS `length` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' AND NOT (" . $this->descriptionEmpty('`id2`.`description`') . ") AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "information_to_store` `i2s` WHERE `i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) HAVING `length` < '" . (int)self::THIN_DESCRIPTION_MIN . "' ORDER BY `i`.`information_id`, `l`.`sort_order`, `l`.`name`");
	}

	/**
	 * Enabled products that share the same description text with another
	 * product in one store in the same language, one finding row per product,
	 * store and language. The product page prints the description after
	 * html_entity_decode (catalog/controller/product/product.php) and the admin
	 * form does not check it for uniqueness (admin/controller/catalog/product.php). The text
	 * is that of descriptionText() (tags and formatting ignored) compared by
	 * its MD5 of the lower-cased text, since GROUP BY on a long TEXT column
	 * compares only max_sort_length bytes; unlike seoDuplicateMetaTitle() the
	 * comparison is not accent-insensitive. Empty and image-only descriptions
	 * never form a group. A group counts only if it spans more than one
	 * variant family, as in seoDuplicateMetaTitle(); a product counts in every
	 * existing store it is linked to. Caveats: a tag replaced by a space may
	 * add a character inside a word; different entities fold to one character,
	 * so two texts differing only in an entity may rarely be reported as equal;
	 * the regular expressions run per row twice, which may be slow on large
	 * catalogs (see M5b). The group value alias is `text_key`, not a column name.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateDescription(): CheckResult {
		return $this->collect('seo_duplicate_description', "SELECT `p`.`product_id`, `pd`.`name`, `p2s`.`store_id`, `l`.`name` AS `language`, `g`.`duplicates` FROM `" . DB_PREFIX . "product` `p` INNER JOIN (" . $this->variantFamilies() . ") `f2` ON (`f2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND (`p2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `p2s3`.`store_id`, `pd3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`pd3`.`description`') . ")) AS `text_key`, COUNT(DISTINCT `p3`.`product_id`) AS `duplicates` FROM `" . DB_PREFIX . "product` `p3` INNER JOIN (" . $this->variantFamilies() . ") `f` ON (`f`.`product_id` = `p3`.`product_id`) INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s3` ON (`p2s3`.`product_id` = `p3`.`product_id` AND (`p2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `p2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "product_description` `pd3` ON (`pd3`.`product_id` = `p3`.`product_id`) WHERE `p3`.`status` = '1' AND " . $this->descriptionText('`pd3`.`description`') . " != '' GROUP BY `p2s3`.`store_id`, `pd3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`pd3`.`description`') . ")) HAVING COUNT(DISTINCT `f`.`family`) > 1) `g` ON (`g`.`store_id` = `p2s`.`store_id` AND `g`.`language_id` = `pd2`.`language_id` AND `g`.`text_key` = MD5(LOWER(" . $this->descriptionText('`pd2`.`description`') . "))) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' ORDER BY `p2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`text_key`, `p`.`product_id`");
	}

	/**
	 * Enabled categories that share the same description text with another
	 * category in one store in the same language, one finding row per
	 * category, store and language. The category page prints the description
	 * after html_entity_decode (catalog/controller/product/category.php) and the
	 * admin form does not check it for uniqueness
	 * (admin/controller/catalog/category.php).
	 * Text, MD5 comparison, empty and image-only exclusion and the caveats as
	 * in seoDuplicateDescription(); no variant families here. A category
	 * counts in every existing store it is linked to. The group value alias is
	 * `text_key`, not a column name.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateDescriptionCategory(): CheckResult {
		return $this->collect('seo_duplicate_description_category', "SELECT `c`.`category_id`, `cd`.`name`, `c2s`.`store_id`, `l`.`name` AS `language`, `g`.`duplicates` FROM `" . DB_PREFIX . "category` `c` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id` AND (`c2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `c2s3`.`store_id`, `cd3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`cd3`.`description`') . ")) AS `text_key`, COUNT(DISTINCT `c3`.`category_id`) AS `duplicates` FROM `" . DB_PREFIX . "category` `c3` INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s3` ON (`c2s3`.`category_id` = `c3`.`category_id` AND (`c2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `c2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "category_description` `cd3` ON (`cd3`.`category_id` = `c3`.`category_id`) WHERE `c3`.`status` = '1' AND " . $this->descriptionText('`cd3`.`description`') . " != '' GROUP BY `c2s3`.`store_id`, `cd3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`cd3`.`description`') . ")) HAVING COUNT(DISTINCT `c3`.`category_id`) > 1) `g` ON (`g`.`store_id` = `c2s`.`store_id` AND `g`.`language_id` = `cd2`.`language_id` AND `g`.`text_key` = MD5(LOWER(" . $this->descriptionText('`cd2`.`description`') . "))) LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `c`.`status` = '1' ORDER BY `c2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`text_key`, `c`.`category_id`");
	}

	/**
	 * Enabled information pages that share the same description text with
	 * another page in one store in the same language, one finding row per
	 * page, store and language. The information page prints the description
	 * after html_entity_decode (catalog/controller/information/information.php)
	 * and the admin form does not check it for uniqueness
	 * (admin/controller/catalog/information.php). Text, MD5 comparison, empty
	 * and image-only exclusion and the caveats as in seoDuplicateDescription();
	 * no variant families here. A page counts in every existing store it is
	 * linked to. The group value alias is `text_key`, not a column name (the
	 * table also has `title`).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function seoDuplicateDescriptionInformation(): CheckResult {
		return $this->collect('seo_duplicate_description_information', "SELECT `i`.`information_id`, `id`.`title` AS `name`, `i2s`.`store_id`, `l`.`name` AS `language`, `g`.`duplicates` FROM `" . DB_PREFIX . "information` `i` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s` ON (`i2s`.`information_id` = `i`.`information_id` AND (`i2s`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s`.`store_id`))) INNER JOIN `" . DB_PREFIX . "information_description` `id2` ON (`id2`.`information_id` = `i`.`information_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `id2`.`language_id` AND `l`.`status` = '1') INNER JOIN (SELECT `i2s3`.`store_id`, `id3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`id3`.`description`') . ")) AS `text_key`, COUNT(DISTINCT `i3`.`information_id`) AS `duplicates` FROM `" . DB_PREFIX . "information` `i3` INNER JOIN `" . DB_PREFIX . "information_to_store` `i2s3` ON (`i2s3`.`information_id` = `i3`.`information_id` AND (`i2s3`.`store_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "store` `st` WHERE `st`.`store_id` = `i2s3`.`store_id`))) INNER JOIN `" . DB_PREFIX . "information_description` `id3` ON (`id3`.`information_id` = `i3`.`information_id`) WHERE `i3`.`status` = '1' AND " . $this->descriptionText('`id3`.`description`') . " != '' GROUP BY `i2s3`.`store_id`, `id3`.`language_id`, MD5(LOWER(" . $this->descriptionText('`id3`.`description`') . ")) HAVING COUNT(DISTINCT `i3`.`information_id`) > 1) `g` ON (`g`.`store_id` = `i2s`.`store_id` AND `g`.`language_id` = `id2`.`language_id` AND `g`.`text_key` = MD5(LOWER(" . $this->descriptionText('`id2`.`description`') . "))) LEFT JOIN `" . DB_PREFIX . "information_description` `id` ON (`id`.`information_id` = `i`.`information_id` AND `id`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `i`.`status` = '1' ORDER BY `i2s`.`store_id`, `l`.`sort_order`, `l`.`name`, `g`.`text_key`, `i`.`information_id`");
	}

	/**
	 * Cleanup of seo_orphan_url: deletes the SEO URLs of deleted records. Same
	 * condition as the check. The storefront opens no page for these addresses
	 * (the record is not found), and the delete frees their keywords.
	 *
	 * @return array<int, array{alias: string, from: string, where: string}>
	 */
	protected function seoOrphanUrlCleanup(): array {
		return [['alias' => 'su', 'from' => "`" . DB_PREFIX . "seo_url` `su`", 'where' => $this->orphanUrlWhere()]];
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

	/**
	 * SQL expression: the character length of a trimmed meta description as
	 * the owner and search engines see it; see seoMetaDescriptionLength().
	 *
	 * @param string $column SQL expression of the column
	 *
	 * @return string SQL expression
	 */
	private function metaDescriptionLength(string $column): string {
		return "CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(" . $column . "), '&quot;', '\"'), '&lt;', '<'), '&gt;', '>'), '&amp;', '&'))";
	}

	/**
	 * SQL expression: the visible text of a stored (HTML-escaped) description,
	 * as the storefront shows it after html_entity_decode: tags removed,
	 * non-breaking spaces as spaces, any other entity as one character,
	 * whitespace runs collapsed and trimmed. Needs MySQL 8.0+ / MariaDB 10.0.5+.
	 *
	 * @param string $column SQL expression of the column
	 *
	 * @return string SQL expression
	 */
	private function descriptionText(string $column): string {
		return "TRIM(REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(" . $column . ", '(?s)&lt;.*?&gt;', ' '), '&amp;(nbsp|#160);', ' '), '&amp;#?[[:alnum:]]+;|&(quot|lt|gt|amp|#039);', 'x'), '[[:space:]]+', ' '))";
	}

	/**
	 * SQL condition: an SEO URL of a deleted product, category, manufacturer,
	 * information page, article or topic, shared by the check and its cleanup
	 * (seo_url table aliased `su`).
	 *
	 * @return string
	 */
	private function orphanUrlWhere(): string {
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

		return implode(" OR ", $implode);
	}
}
