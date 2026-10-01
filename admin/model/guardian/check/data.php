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
		'product_no_model'               => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_no_category'            => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_no_store'               => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_shipping_no_weight'     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_no_manufacturer'        => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_future_available'       => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_shipping_no_dimensions' => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql']
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
