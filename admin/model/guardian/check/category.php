<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Category
 *
 * Category CATEGORY: category tree and catalog lookup integrity.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Category extends Base {
	protected array $checks = [
		'category_broken_parent' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_path_mismatch' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
	];

	/**
	 * Categories whose parent does not exist. The admin deletes a category
	 * together with its subtree, so these come from imports or direct SQL.
	 * The storefront menu and category module walk the tree from the root,
	 * so such a category is reachable only by a direct link.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryBrokenParent(): CheckResult {
		return $this->collect('category_broken_parent', $this->categorySelect('`c`.`parent_id`') . " LEFT JOIN `" . DB_PREFIX . "category` `pc` ON (`pc`.`category_id` = `c`.`parent_id`) WHERE `c`.`parent_id` != '0' AND `pc`.`category_id` IS NULL ORDER BY `c`.`category_id`");
	}

	/**
	 * Categories whose stored path differs from the one the admin builds:
	 * the parent's path rows at the same levels plus the category itself one
	 * level below. Rows are matched one by one and counted, so a missing,
	 * extra or shifted row all break the equality. Categories in a parent
	 * cycle never have a consistent path and are reported too; a missing
	 * parent is left to category_broken_parent. The storefront relies on
	 * these paths for subcategory product counts, search in subcategories
	 * and category-bound coupons; the admin list builds names from them.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryPathMismatch(): CheckResult {
		$parent_depth = "(SELECT COUNT(*) FROM `" . DB_PREFIX . "category_path` `pp` WHERE `pp`.`category_id` = `c`.`parent_id`)";

		$matched = "(SELECT COUNT(*) FROM `" . DB_PREFIX . "category_path` `cp` WHERE `cp`.`category_id` = `c`.`category_id` AND ((`cp`.`path_id` = `c`.`category_id` AND `cp`.`level` = " . $parent_depth . ") OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_path` `pp` WHERE `pp`.`category_id` = `c`.`parent_id` AND `pp`.`path_id` = `cp`.`path_id` AND `pp`.`level` = `cp`.`level`)))";

		$path = "(SELECT GROUP_CONCAT(`cp`.`path_id` ORDER BY `cp`.`level` SEPARATOR ' > ') FROM `" . DB_PREFIX . "category_path` `cp` WHERE `cp`.`category_id` = `c`.`category_id`) AS `path`";

		return $this->collect('category_path_mismatch', $this->categorySelect('`c`.`parent_id`, ' . $path) . " WHERE (`c`.`parent_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category` `pc` WHERE `pc`.`category_id` = `c`.`parent_id`)) AND ((SELECT COUNT(*) FROM `" . DB_PREFIX . "category_path` `cp` WHERE `cp`.`category_id` = `c`.`category_id`) != " . $parent_depth . " + 1 OR " . $matched . " != " . $parent_depth . " + 1) ORDER BY `c`.`category_id`");
	}

	/**
	 * Common finding columns: id, name in the admin language, status.
	 *
	 * @param string $columns extra select list over `c` (category)
	 *
	 * @return string SELECT ... FROM category `c` with the description joined, no WHERE
	 */
	private function categorySelect(string $columns = ''): string {
		return "SELECT `c`.`category_id`, `cd`.`name`, `c`.`status`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";
	}
}
