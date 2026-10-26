<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Category
 *
 * Category CATEGORY: category tree and catalog list (filters, attributes,
 * options, manufacturers) integrity.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Category extends Base {
	/**
	 * Deepest acceptable level for category_too_deep; a top-level category is 1.
	 */
	private const MAX_DEPTH = 5;

	protected string $edit = 'category_id';

	protected array $checks = [
		'category_broken_parent'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_path_mismatch'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_incomplete_description' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_no_store'               => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_empty'                  => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'category_duplicate_name'         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_too_deep'               => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'category_broken_layout'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'category_broken_filter'          => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'filter_group_empty'              => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'filter_group_id'],
		'attribute_unused'                => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'attribute_id'],
		'option_unused'                   => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'option_id'],
		'option_value_unused'             => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'option_id'],
		'filter_unused'                   => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'filter_id'],
		'manufacturer_no_products'        => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql', 'edit' => 'manufacturer_id']
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
		// Depth and path of every category, read once and joined for the category and its parent
		$paths = "(SELECT `category_id`, COUNT(*) AS `depth`, GROUP_CONCAT(`path_id` ORDER BY `level` SEPARATOR ' > ') AS `path` FROM `" . DB_PREFIX . "category_path` GROUP BY `category_id`)";

		$parent_depth = "COALESCE(`pd`.`depth`, 0)";

		$matched = "(SELECT COUNT(*) FROM `" . DB_PREFIX . "category_path` `cp` WHERE `cp`.`category_id` = `c`.`category_id` AND ((`cp`.`path_id` = `c`.`category_id` AND `cp`.`level` = " . $parent_depth . ") OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category_path` `pp` WHERE `pp`.`category_id` = `c`.`parent_id` AND `pp`.`path_id` = `cp`.`path_id` AND `pp`.`level` = `cp`.`level`)))";

		return $this->collect('category_path_mismatch', $this->categorySelect('`c`.`parent_id`, `od`.`path`') . " LEFT JOIN " . $paths . " `od` ON (`od`.`category_id` = `c`.`category_id`) LEFT JOIN " . $paths . " `pd` ON (`pd`.`category_id` = `c`.`parent_id`) WHERE (`c`.`parent_id` = '0' OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "category` `pc` WHERE `pc`.`category_id` = `c`.`parent_id`)) AND (COALESCE(`od`.`depth`, 0) != " . $parent_depth . " + 1 OR " . $matched . " != " . $parent_depth . " + 1) ORDER BY `c`.`category_id`");
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

		// Every category holding an enabled product in its subtree or directly, built once
		$filled = "SELECT `cp`.`path_id` AS `category_id` FROM `" . DB_PREFIX . "category_path` `cp` INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`category_id` = `cp`.`category_id`) " . $product . " UNION SELECT `p2c`.`category_id` FROM `" . DB_PREFIX . "product_to_category` `p2c` " . $product;

		return $this->collect('category_empty', $this->categorySelect() . " LEFT JOIN (" . $filled . ") `f` ON (`f`.`category_id` = `c`.`category_id`) WHERE `c`.`status` = '1' AND `f`.`category_id` IS NULL ORDER BY `c`.`category_id`");
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
	 * Category filter links to a deleted filter, one finding row per link.
	 * Info only: the storefront looks filters up by id and skips missing
	 * ones, and the admin form hides them, so saving the category drops the
	 * link. The admin deletes these links with the filter, so they come from
	 * imports or direct SQL.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function categoryBrokenFilter(): CheckResult {
		return $this->collect('category_broken_filter', $this->categorySelect('`cf`.`filter_id`') . " INNER JOIN `" . DB_PREFIX . "category_filter` `cf` ON (`cf`.`category_id` = `c`.`category_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "filter` `f` WHERE `f`.`filter_id` = `cf`.`filter_id`) ORDER BY `c`.`category_id`, `cf`.`filter_id`");
	}

	/**
	 * Filter groups without a single filter. The storefront reaches groups
	 * only through the filters linked to a category, so such a group is
	 * never shown; it only clutters the admin lists.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function filterGroupEmpty(): CheckResult {
		return $this->collect('filter_group_empty', "SELECT `fg`.`filter_group_id`, `fgd`.`name` FROM `" . DB_PREFIX . "filter_group` `fg` LEFT JOIN `" . DB_PREFIX . "filter_group_description` `fgd` ON (`fgd`.`filter_group_id` = `fg`.`filter_group_id` AND `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "filter` `f` WHERE `f`.`filter_group_id` = `fg`.`filter_group_id`) ORDER BY `fg`.`filter_group_id`");
	}

	/**
	 * Attributes not set on any product. Any product link counts, even one
	 * left by a deleted product, as in the admin delete guard, so every
	 * finding can be deleted; such leftovers belong to the GARBAGE category.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function attributeUnused(): CheckResult {
		return $this->collect('attribute_unused', "SELECT `a`.`attribute_id`, `ad`.`name` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`ad`.`attribute_id` = `a`.`attribute_id` AND `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` `pa` WHERE `pa`.`attribute_id` = `a`.`attribute_id`) ORDER BY `a`.`attribute_id`");
	}

	/**
	 * Options not added to any product. Any product link counts, as in the
	 * admin delete guard; see attributeUnused().
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function optionUnused(): CheckResult {
		return $this->collect('option_unused', "SELECT `o`.`option_id`, `od`.`name` FROM `" . DB_PREFIX . "option` `o` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`od`.`option_id` = `o`.`option_id` AND `od`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option` `po` WHERE `po`.`option_id` = `o`.`option_id`) ORDER BY `o`.`option_id`");
	}

	/**
	 * Option values not offered on any product, within options that are in
	 * use; a wholly unused option is option_unused. Any product link counts,
	 * as in the admin guard against removing a value from an option.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function optionValueUnused(): CheckResult {
		$language_id = (int)$this->config->get('config_language_id');

		return $this->collect('option_value_unused', "SELECT `ov`.`option_id`, `od`.`name` AS `option`, `ov`.`option_value_id`, `ovd`.`name` FROM `" . DB_PREFIX . "option_value` `ov` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`od`.`option_id` = `ov`.`option_id` AND `od`.`language_id` = '" . $language_id . "') LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ovd`.`option_value_id` = `ov`.`option_value_id` AND `ovd`.`language_id` = '" . $language_id . "') WHERE EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option` `po` WHERE `po`.`option_id` = `ov`.`option_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option_value` `pov` WHERE `pov`.`option_value_id` = `ov`.`option_value_id`) ORDER BY `ov`.`option_id`, `ov`.`option_value_id`");
	}

	/**
	 * Filters not set on any existing product; `categories` is the number of
	 * categories offering the filter. The admin deletes a filter together
	 * with all its links, so a link left by a deleted product does not count.
	 * In an offering category the filter module lists the filter, and
	 * choosing it shows no products.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function filterUnused(): CheckResult {
		$language_id = (int)$this->config->get('config_language_id');

		return $this->collect('filter_unused', "SELECT `f`.`filter_id`, `fd`.`name`, `fgd`.`name` AS `group`, COALESCE(`cc`.`categories`, 0) AS `categories` FROM `" . DB_PREFIX . "filter` `f` LEFT JOIN `" . DB_PREFIX . "filter_description` `fd` ON (`fd`.`filter_id` = `f`.`filter_id` AND `fd`.`language_id` = '" . $language_id . "') LEFT JOIN `" . DB_PREFIX . "filter_group_description` `fgd` ON (`fgd`.`filter_group_id` = `f`.`filter_group_id` AND `fgd`.`language_id` = '" . $language_id . "') LEFT JOIN (SELECT DISTINCT `pf`.`filter_id` FROM `" . DB_PREFIX . "product_filter` `pf` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `pf`.`product_id`)) `u` ON (`u`.`filter_id` = `f`.`filter_id`) LEFT JOIN (SELECT `filter_id`, COUNT(*) AS `categories` FROM `" . DB_PREFIX . "category_filter` GROUP BY `filter_id`) `cc` ON (`cc`.`filter_id` = `f`.`filter_id`) WHERE `u`.`filter_id` IS NULL ORDER BY `f`.`filter_id`");
	}

	/**
	 * Manufacturers without an enabled product; `products` counts all their
	 * products, so a zero means the manufacturer can be deleted (the admin
	 * refuses while any product uses it). Manufacturers have no status, and
	 * the storefront brand list shows every one assigned to the store, so
	 * customers open a brand page that lists no products.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function manufacturerNoProducts(): CheckResult {
		return $this->collect('manufacturer_no_products', "SELECT `m`.`manufacturer_id`, `m`.`name`, COALESCE(`pm`.`products`, 0) AS `products` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN (SELECT `manufacturer_id`, COUNT(*) AS `products`, SUM(`status` = '1') AS `enabled` FROM `" . DB_PREFIX . "product` GROUP BY `manufacturer_id`) `pm` ON (`pm`.`manufacturer_id` = `m`.`manufacturer_id`) WHERE COALESCE(`pm`.`enabled`, 0) = '0' ORDER BY `m`.`manufacturer_id`");
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
