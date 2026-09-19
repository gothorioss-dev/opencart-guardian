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
		'product_no_model'    => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_no_category' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
	];

	/**
	 * Products with an empty model (required field; breaks search and feeds).
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoModel(): CheckResult {
		return $this->collect('product_no_model', "SELECT `p`.`product_id`, `pd`.`name`, `p`.`status` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE TRIM(`p`.`model`) = '' ORDER BY `p`.`product_id`");
	}

	/**
	 * Products not assigned to any category.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoCategory(): CheckResult {
		return $this->collect('product_no_category', "SELECT `p`.`product_id`, `pd`.`name`, `p`.`status` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`) WHERE `p2c`.`product_id` IS NULL ORDER BY `p`.`product_id`");
	}
}
