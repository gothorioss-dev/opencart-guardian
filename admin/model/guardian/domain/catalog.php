<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Domain;

use Opencart\Admin\Model\Extension\GtrGuardian\Guardian\DomainBase;
/**
 * Class Catalog
 *
 * Guardian domain A: catalog data quality (DATA, CATALOG, MEDIA, SEO, GARBAGE).
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
		return ['data', 'catalog', 'media', 'seo', 'garbage'];
	}
}
