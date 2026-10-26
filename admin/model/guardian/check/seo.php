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
		'seo_duplicate_keyword' => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_empty_keyword'     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id'],
		'seo_invalid_keyword'   => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql', 'edit' => 'seo_url_id']
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
