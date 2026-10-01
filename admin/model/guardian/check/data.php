<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Data
 *
 * Category DATA: product record integrity.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Data extends Base {
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
		'product_broken_attribute_option' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
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
	 * legitimate setup.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productBrokenVariant(): CheckResult {
		return $this->collect('product_broken_variant', $this->productSelect('`p`.`master_id`, `m`.`master_id` AS `master_master_id`') . " LEFT JOIN `" . DB_PREFIX . "product` `m` ON (`m`.`product_id` = `p`.`master_id`) WHERE `p`.`master_id` != '0' AND (`m`.`product_id` IS NULL OR `p`.`master_id` = `p`.`product_id` OR `m`.`master_id` != '0') ORDER BY `p`.`product_id`");
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
