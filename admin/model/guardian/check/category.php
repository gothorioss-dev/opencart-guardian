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
	/**
	 * Deepest acceptable level for category_too_deep; a top-level category is 1.
	 */
	private const MAX_DEPTH = 5;

	protected array $checks = [
		'category_broken_parent'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_path_mismatch'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_incomplete_description' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_no_store'               => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_empty'                  => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'category_duplicate_name'         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_too_deep'               => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'category_broken_layout'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
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
	 * Categories missing a description row for an enabled language. The
	 * storefront category page and every category list filter on
	 * cd.language_id, so in that language the category is not shown at all.
	 * One finding row per missing language.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryIncompleteDescription(): CheckResult {
		return $this->collect('category_incomplete_description', $this->categorySelect("`l`.`code` AS `language`") . " CROSS JOIN `" . DB_PREFIX . "language` `l` WHERE `l`.`status` = '1' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_description` `cd2` WHERE `cd2`.`category_id` = `c`.`category_id` AND `cd2`.`language_id` = `l`.`language_id`) ORDER BY `c`.`category_id`, `l`.`code`");
	}

	/**
	 * Categories not assigned to any store are invisible on every storefront.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryNoStore(): CheckResult {
		return $this->collect('category_no_store', $this->categorySelect() . " LEFT JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id`) WHERE `c2s`.`category_id` IS NULL ORDER BY `c`.`category_id`");
	}

	/**
	 * Enabled categories with no enabled product in themselves or any
	 * subcategory: customers reach a page that leads to no product. The
	 * subtree comes from the stored paths, as the storefront takes it; the
	 * category's own products are checked directly, so a category missing
	 * its own path row is not reported as empty.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryEmpty(): CheckResult {
		$product = "INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `p2c`.`product_id` AND `p`.`status` = '1')";

		return $this->collect('category_empty', $this->categorySelect() . " WHERE `c`.`status` = '1' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_category` `p2c` " . $product . " WHERE `p2c`.`category_id` = `c`.`category_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_path` `cp` INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`category_id` = `cp`.`category_id`) " . $product . " WHERE `cp`.`path_id` = `c`.`category_id`) ORDER BY `c`.`category_id`");
	}

	/**
	 * Sibling categories sharing a name in the same enabled language, one
	 * finding row per category and language; `group_size` is the number of
	 * siblings with that name. Names are trimmed, and the column collation
	 * already ignores case. Core does not check name uniqueness. Empty names
	 * are skipped.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryDuplicateName(): CheckResult {
		$groups = "SELECT `c3`.`parent_id`, `cd3`.`language_id`, TRIM(`cd3`.`name`) AS `name`, COUNT(*) AS `group_size` FROM `" . DB_PREFIX . "category` `c3` INNER JOIN `" . DB_PREFIX . "category_description` `cd3` ON (`cd3`.`category_id` = `c3`.`category_id`) WHERE TRIM(`cd3`.`name`) != '' GROUP BY `c3`.`parent_id`, `cd3`.`language_id`, TRIM(`cd3`.`name`) HAVING `group_size` > 1";

		return $this->collect('category_duplicate_name', $this->categorySelect("`c`.`parent_id`, `l`.`code` AS `language`, TRIM(`cd2`.`name`) AS `duplicate`, `g`.`group_size`") . " INNER JOIN `" . DB_PREFIX . "category_description` `cd2` ON (`cd2`.`category_id` = `c`.`category_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `cd2`.`language_id` AND `l`.`status` = '1') INNER JOIN (" . $groups . ") `g` ON (`g`.`parent_id` = `c`.`parent_id` AND `g`.`language_id` = `cd2`.`language_id` AND `g`.`name` = TRIM(`cd2`.`name`)) ORDER BY `c`.`parent_id`, `l`.`code`, TRIM(`cd2`.`name`), `c`.`category_id`");
	}

	/**
	 * Categories nested deeper than MAX_DEPTH, the depth taken from the
	 * stored path as the storefront does. The menu and the category module
	 * show two levels only, so deeper categories are reached only through
	 * the subcategory list of each page on the way down.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryTooDeep(): CheckResult {
		return $this->collect('category_too_deep', $this->categorySelect("`d`.`depth`") . " INNER JOIN (SELECT `category_id`, COUNT(*) AS `depth` FROM `" . DB_PREFIX . "category_path` GROUP BY `category_id` HAVING `depth` > '" . (int)self::MAX_DEPTH . "') `d` ON (`d`.`category_id` = `c`.`category_id`) ORDER BY `c`.`category_id`");
	}

	/**
	 * Layout overrides pointing at a deleted layout, one finding row per store.
	 * The storefront falls back to the route layout only for layout_id 0
	 * (catalog/controller/common/column_left.php), so the category page loses
	 * all its modules. The admin refuses to delete a layout in use, so these
	 * come from imports or direct SQL.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryBrokenLayout(): CheckResult {
		return $this->collect('category_broken_layout', $this->categorySelect('`c2l`.`store_id`, `c2l`.`layout_id`') . " INNER JOIN `" . DB_PREFIX . "category_to_layout` `c2l` ON (`c2l`.`category_id` = `c`.`category_id`) WHERE `c2l`.`layout_id` != '0' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "layout` `l` WHERE `l`.`layout_id` = `c2l`.`layout_id`) ORDER BY `c`.`category_id`, `c2l`.`store_id`");
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
