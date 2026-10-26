<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Media
 *
 * Category MEDIA (SQL part): product images and downloads as recorded in the
 * database; files on disk are checked separately.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Media extends Base {
	protected string $edit = 'product_id';

	protected array $checks = [
		'product_no_image' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
	];

	/**
	 * Enabled products without a main image. A stored placeholder counts as
	 * none: the storefront lists show placeholder.png for a missing image
	 * (catalog/controller/product/category.php), and no_image.png is the
	 * admin's own placeholder, never meant to be saved on a product.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNoImage(): CheckResult {
		return $this->collect('product_no_image', "SELECT `p`.`product_id`, `pd`.`name`, `p`.`status`, `p`.`image` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `p`.`status` = '1' AND TRIM(`p`.`image`) IN ('', 'no_image.png', 'placeholder.png') ORDER BY `p`.`product_id`");
	}
}
