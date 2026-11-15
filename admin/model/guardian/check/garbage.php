<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check;

/**
 * Class Garbage
 *
 * Category GARBAGE: records left behind by deleted or outdated data.
 * Findings only; cleanup is a separate step.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check
 */
class Garbage extends Base {
	protected array $checks = [];
}
