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
		'seo_duplicate_keyword'     => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_empty_keyword'         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_invalid_keyword'       => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_orphan_url'            => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_orphan_store_language' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'seo_url_id']
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
