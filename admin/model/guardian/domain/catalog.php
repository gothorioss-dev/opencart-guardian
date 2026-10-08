<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class Catalog
 *
 * Guardian domain A: catalog data quality (PRODUCT, CATEGORY, MEDIA, SEO, GARBAGE).
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain
 */
class Catalog extends DomainBase {
	/**
	 * @return string
	 */
	public function getCode(): string {
		return 'catalog';
	}

	/**
	 * @return array<int, string>
	 */
	public function categories(): array {
		return ['product', 'category', 'media', 'seo', 'garbage'];
	}
}
