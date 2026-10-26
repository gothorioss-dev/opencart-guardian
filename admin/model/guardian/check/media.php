<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

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

	protected array $checks = [];
}
