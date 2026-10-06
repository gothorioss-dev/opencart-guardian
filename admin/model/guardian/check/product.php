<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Product
 *
 * Category PRODUCT: product record integrity.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Product extends Base {
	protected array $checks = [
		'product_no_model'                => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_no_category'             => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_no_store'                => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_shipping_no_weight'      => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_no_manufacturer'         => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_future_available'        => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_shipping_no_dimensions'  => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_broken_variant'          => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_broken_reference'        => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_broken_attribute_option' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_empty_content'           => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_incomplete_description'  => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_zero_price'              => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql']
	];

	/**
	 * Products with an empty model (required field; breaks search and feeds).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoModel(): CheckResult {
		return $this->collect('product_no_model', $this->productSelect() . " WHERE TRIM(`p`.`model`) = '' ORDER BY `p`.`product_id`");
	}

	/**
	 * Products not assigned to any category.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoCategory(): CheckResult {
		return $this->collect('product_no_category', $this->productSelect() . " LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`) WHERE `p2c`.`product_id` IS NULL ORDER BY `p`.`product_id`");
	}

	/**
	 * Products not assigned to any store are invisible on every storefront.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoStore(): CheckResult {
		return $this->collect('product_no_store', $this->productSelect() . " LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id`) WHERE `p2s`.`product_id` IS NULL ORDER BY `p`.`product_id`");
	}

	/**
	 * Shippable products with zero weight break weight-based shipping rates.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productShippingNoWeight(): CheckResult {
		return $this->collect('product_shipping_no_weight', $this->productSelect('`p`.`weight`') . " WHERE `p`.`shipping` = '1' AND `p`.`weight` <= '0' ORDER BY `p`.`product_id`");
	}

	/**
	 * Products without a manufacturer.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoManufacturer(): CheckResult {
		return $this->collect('product_no_manufacturer', $this->productSelect() . " WHERE `p`.`manufacturer_id` = '0' ORDER BY `p`.`product_id`");
	}

	/**
	 * Enabled products hidden from the storefront until date_available
	 * (the catalog model filters on date_available <= NOW()).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productFutureAvailable(): CheckResult {
		return $this->collect('product_future_available', $this->productSelect('`p`.`date_available`') . " WHERE `p`.`status` = '1' AND `p`.`date_available` > CURDATE() ORDER BY `p`.`date_available`, `p`.`product_id`");
	}

	/**
	 * Shippable products with a zero dimension. Info only: core shipping
	 * methods rate by weight, dimensions matter to carrier integrations.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productShippingNoDimensions(): CheckResult {
		return $this->collect('product_shipping_no_dimensions', $this->productSelect('`p`.`length`, `p`.`width`, `p`.`height`') . " WHERE `p`.`shipping` = '1' AND (`p`.`length` <= '0' OR `p`.`width` <= '0' OR `p`.`height` <= '0') ORDER BY `p`.`product_id`");
	}

	/**
	 * Variants whose master is missing, is the variant itself, or is a variant
	 * too. The storefront and cart load a variant's options from its master
	 * (catalog/controller/product/product.php, checkout/cart.php), so such
	 * variants lose their options. A disabled master is not reported: options
	 * still load from it, and hiding the master while selling variants is a
	 * legitimate setup. A self-referencing variant joins itself as `m`, so the
	 * `m`.`master_id` test covers it.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productBrokenVariant(): CheckResult {
		return $this->collect('product_broken_variant', $this->productSelect('`p`.`master_id`, `m`.`master_id` AS `master_master_id`') . " LEFT JOIN `" . DB_PREFIX . "product` `m` ON (`m`.`product_id` = `p`.`master_id`) WHERE `p`.`master_id` != '0' AND (`m`.`product_id` IS NULL OR `m`.`master_id` != '0') ORDER BY `p`.`product_id`");
	}

	/**
	 * Product fields pointing at a deleted lookup record, one finding row per
	 * broken field. tax_class_id and manufacturer_id use 0 for "none"; the
	 * weight/length class and stock status have no such value, so 0 is broken.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productBrokenReference(): CheckResult {
		$references = [
			'tax_class_id'    => ['tax_class', true],
			'weight_class_id' => ['weight_class', false],
			'length_class_id' => ['length_class', false],
			'stock_status_id' => ['stock_status', false],
			'manufacturer_id' => ['manufacturer', true]
		];

		$parts = [];

		foreach ($references as $field => [$table, $zero_allowed]) {
			$parts[] = $this->productSelect("'" . $field . "' AS `field`, `p`.`" . $field . "` AS `value`") . " WHERE " . ($zero_allowed ? "`p`.`" . $field . "` != '0' AND " : "") . "NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . $table . "` `r` WHERE `r`.`" . $field . "` = `p`.`" . $field . "`)";
		}

		return $this->collect('product_broken_reference', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `ref` ORDER BY `product_id`, `field`");
	}

	/**
	 * Attribute and option links pointing at a deleted attribute, option,
	 * option value or parent product option, one finding row per broken link.
	 * Links of deleted products are GARBAGE, not reported here.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productBrokenAttributeOption(): CheckResult {
		$parts = [];

		// product_attribute holds one row per language; collapse to one per attribute
		$parts[] = $this->productSelect("'attribute' AS `type`, `pa`.`attribute_id` AS `ref_id`") . " INNER JOIN (SELECT DISTINCT `product_id`, `attribute_id` FROM `" . DB_PREFIX . "product_attribute`) `pa` ON (`pa`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "attribute` `a` WHERE `a`.`attribute_id` = `pa`.`attribute_id`)";
		$parts[] = $this->productSelect("'option' AS `type`, `po`.`option_id` AS `ref_id`") . " INNER JOIN `" . DB_PREFIX . "product_option` `po` ON (`po`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "option` `o` WHERE `o`.`option_id` = `po`.`option_id`)";
		$parts[] = $this->productSelect("'option_value' AS `type`, `pov`.`option_value_id` AS `ref_id`") . " INNER JOIN `" . DB_PREFIX . "product_option_value` `pov` ON (`pov`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "option_value` `ov` WHERE `ov`.`option_value_id` = `pov`.`option_value_id`)";
		$parts[] = $this->productSelect("'product_option' AS `type`, `pov`.`product_option_id` AS `ref_id`") . " INNER JOIN `" . DB_PREFIX . "product_option_value` `pov` ON (`pov`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option` `po` WHERE `po`.`product_option_id` = `pov`.`product_option_id`)";

		return $this->collect('product_broken_attribute_option', "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS `link` ORDER BY `product_id`, `type`, `ref_id`");
	}

	/**
	 * Empty name or description in an enabled language, one finding row per
	 * description row; `field` lists which of the two is empty. Descriptions
	 * are stored HTML-escaped, so an editor's empty markup (&lt;p&gt;&lt;br&gt;&lt;/p&gt;)
	 * is stripped before testing. Short or duplicate texts belong to SEO.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productEmptyContent(): CheckResult {
		$empty_name = "TRIM(`pd2`.`name`) = ''";
		// REGEXP_REPLACE needs MySQL 8.0+ / MariaDB 10.0.5+; (?s) lets a tag span
		// lines; media tags are kept, an image-only description is not empty
		$empty_description = "REGEXP_REPLACE(REGEXP_REPLACE(`pd2`.`description`, '(?s)&lt;(?!(img|iframe|video|embed|object)[[:space:]&/]).*?&gt;', ''), '&amp;nbsp;|[[:space:]]', '') = ''";

		return $this->collect('product_empty_content', $this->productSelect("`l`.`code` AS `language`, CONCAT_WS(', ', IF(" . $empty_name . ", 'name', NULL), IF(" . $empty_description . ", 'description', NULL)) AS `field`") . " INNER JOIN `" . DB_PREFIX . "product_description` `pd2` ON (`pd2`.`product_id` = `p`.`product_id`) INNER JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`language_id` = `pd2`.`language_id` AND `l`.`status` = '1') WHERE " . $empty_name . " OR " . $empty_description . " ORDER BY `p`.`product_id`, `l`.`code`");
	}

	/**
	 * Products missing a description row for an enabled language; the
	 * storefront then shows them with no name in that language. One finding
	 * row per missing language.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productIncompleteDescription(): CheckResult {
		return $this->collect('product_incomplete_description', $this->productSelect("`l`.`code` AS `language`") . " CROSS JOIN `" . DB_PREFIX . "language` `l` WHERE `l`.`status` = '1' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_description` `pd2` WHERE `pd2`.`product_id` = `p`.`product_id` AND `pd2`.`language_id` = `l`.`language_id`) ORDER BY `p`.`product_id`, `l`.`code`");
	}

	/**
	 * Enabled products priced at zero or below that some customer group can
	 * buy for free: the group has no active special with a positive price
	 * for a single item (the cart applies a row only from its quantity up).
	 * The special's final price and date window follow
	 * catalog/model/catalog/product.php.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productZeroPrice(): CheckResult {
		$special_price = "(CASE WHEN `ps`.`type` = 'P' THEN (`p`.`price` - (`p`.`price` * (`ps`.`price` / 100))) WHEN `ps`.`type` = 'S' THEN (`p`.`price` - `ps`.`price`) ELSE `ps`.`price` END)";

		return $this->collect('product_zero_price', $this->productSelect('`p`.`price`') . " WHERE `p`.`status` = '1' AND `p`.`price` <= '0' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "customer_group` `cg` WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_discount` `ps` WHERE `ps`.`product_id` = `p`.`product_id` AND `ps`.`customer_group_id` = `cg`.`customer_group_id` AND `ps`.`special` = '1' AND `ps`.`quantity` <= '1' AND (`ps`.`date_start` = '0000-00-00' OR `ps`.`date_start` < NOW()) AND (`ps`.`date_end` = '0000-00-00' OR `ps`.`date_end` > NOW()) AND " . $special_price . " > '0')) ORDER BY `p`.`product_id`");
	}

	/**
	 * Common finding columns for product checks: id, admin-language name, status.
	 *
	 * @param string $columns extra select list appended after the common columns
	 *
	 * @return string SELECT ... FROM product p LEFT JOIN product_description pd, without WHERE
	 */
	private function productSelect(string $columns = ''): string {
		return "SELECT `p`.`product_id`, `pd`.`name`, `p`.`status`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";
	}
}
