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
		'product_no_image'     => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'download_broken_link' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
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
		return $this->collect('product_no_image', $this->productSelect('`p`.`image`') . " WHERE `p`.`status` = '1' AND TRIM(`p`.`image`) IN ('', 'no_image.png', 'placeholder.png') ORDER BY `p`.`product_id`");
	}

	/**
	 * Product download links to a deleted download, one finding row per link.
	 * The cart and the account download list drop such links, but the order
	 * mail counts them (catalog/controller/mail/order.php), so the customer is
	 * sent a download link to an empty page. The admin deletes the links with
	 * the download and refuses to delete one in use, so these come from
	 * imports or direct SQL; saving the product drops them, as the form hides
	 * missing downloads. Links of deleted products belong to GARBAGE.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function downloadBrokenLink(): CheckResult {
		return $this->collect('download_broken_link', $this->productSelect('`p2d`.`download_id`') . " INNER JOIN `" . DB_PREFIX . "product_to_download` `p2d` ON (`p2d`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "download` `d` WHERE `d`.`download_id` = `p2d`.`download_id`) ORDER BY `p`.`product_id`, `p2d`.`download_id`");
	}

	/**
	 * Common finding columns: id, name in the admin language, status.
	 *
	 * @param string $columns extra select list over `p` (product)
	 *
	 * @return string SELECT ... FROM product `p` with the description joined, no WHERE
	 */
	private function productSelect(string $columns = ''): string {
		return "SELECT `p`.`product_id`, `pd`.`name`, `p`.`status`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";
	}
}
