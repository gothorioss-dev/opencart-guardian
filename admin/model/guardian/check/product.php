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
		'product_zero_price'              => ['severity' => CheckResult::SEVERITY_CRITICAL, 'source' => 'sql'],
		'product_discount_dates_inverted' => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_discount_not_lower'      => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_negative_stock'          => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_minimum_over_stock'      => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_duplicate_model'         => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_duplicate_identifier'    => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql'],
		'product_related_broken'          => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_related_one_way'         => ['severity' => CheckResult::SEVERITY_INFO, 'source' => 'sql'],
		'product_broken_layout'           => ['severity' => CheckResult::SEVERITY_WARNING, 'source' => 'sql']
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
	 * Enabled products that cost zero or less for a single item in at least
	 * one customer group; `customer_groups` lists those groups. Mirrors the
	 * cart (system/library/cart/cart.php): of the group's product_discount
	 * rows active by date with quantity <= 1, special or not, the first by
	 * quantity DESC, priority ASC, price ASC sets the price; without such a
	 * row the base price applies. Products with a required select, radio or
	 * checkbox option whose every value adds a positive price are skipped:
	 * the cart cannot take them without a surcharge. Variants take options
	 * from their master, as the cart does. A group is skipped when the product
	 * has an enabled subscription plan for it: the cart then takes the plan's
	 * price, looked up by the product's own id.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productZeroPrice(): CheckResult {
		$final_price = "(CASE WHEN `dc`.`type` = 'P' THEN (`p`.`price` - (`p`.`price` * (`dc`.`price` / 100))) WHEN `dc`.`type` = 'S' THEN (`p`.`price` - `dc`.`price`) ELSE `dc`.`price` END)";

		$cart_price = "COALESCE((SELECT " . $final_price . " FROM `" . DB_PREFIX . "product_discount` `dc` WHERE `dc`.`product_id` = `p`.`product_id` AND `dc`.`customer_group_id` = `cg`.`customer_group_id` AND `dc`.`quantity` <= '1' AND (`dc`.`date_start` = '0000-00-00' OR `dc`.`date_start` < NOW()) AND (`dc`.`date_end` = '0000-00-00' OR `dc`.`date_end` > NOW()) ORDER BY `dc`.`quantity` DESC, `dc`.`priority` ASC, `dc`.`price` ASC LIMIT 1), `p`.`price`)";

		$option_priced = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option` `po` INNER JOIN `" . DB_PREFIX . "option` `o` ON (`o`.`option_id` = `po`.`option_id`) WHERE `po`.`product_id` = IF(`p`.`master_id` != '0', `p`.`master_id`, `p`.`product_id`) AND `po`.`required` = '1' AND `o`.`type` IN ('select', 'radio', 'checkbox') AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option_value` `pov` WHERE `pov`.`product_option_id` = `po`.`product_option_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_option_value` `pov` WHERE `pov`.`product_option_id` = `po`.`product_option_id` AND NOT (`pov`.`price_prefix` = '+' AND `pov`.`price` > '0')))";

		$subscription_priced = "EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_subscription` `psb` INNER JOIN `" . DB_PREFIX . "subscription_plan` `sp` ON (`sp`.`subscription_plan_id` = `psb`.`subscription_plan_id`) WHERE `psb`.`product_id` = `p`.`product_id` AND `psb`.`customer_group_id` = `cg`.`customer_group_id` AND `sp`.`status` = '1')";

		return $this->collect('product_zero_price', $this->productSelect("`p`.`price`, GROUP_CONCAT(`cg`.`customer_group_id` ORDER BY `cg`.`customer_group_id` SEPARATOR ', ') AS `customer_groups`") . " CROSS JOIN `" . DB_PREFIX . "customer_group` `cg` WHERE `p`.`status` = '1' AND " . $cart_price . " <= '0' AND NOT " . $option_priced . " AND NOT " . $subscription_priced . " GROUP BY `p`.`product_id`, `pd`.`name`, `p`.`status`, `p`.`price` ORDER BY `p`.`product_id`");
	}

	/**
	 * Discount and special rows that can never apply: both dates are set and
	 * the start is not before the end. The cart and storefront need
	 * date_start < NOW() < date_end on midnight-based dates, so equal dates
	 * never match either. One finding row per discount row.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productDiscountDatesInverted(): CheckResult {
		return $this->collect('product_discount_dates_inverted', $this->productSelect("IF(`dc`.`special` = '1', 'special', 'discount') AS `kind`, `dc`.`customer_group_id`, `dc`.`quantity`, `dc`.`date_start`, `dc`.`date_end`") . " INNER JOIN `" . DB_PREFIX . "product_discount` `dc` ON (`dc`.`product_id` = `p`.`product_id`) WHERE `dc`.`date_start` != '0000-00-00' AND `dc`.`date_end` != '0000-00-00' AND `dc`.`date_start` >= `dc`.`date_end` ORDER BY `p`.`product_id`, `dc`.`product_discount_id`");
	}

	/**
	 * Discount and special rows whose final price is not below the product
	 * price. Such a row still wins by priority in the cart and storefront and
	 * hides a real discount behind it. Dates are ignored; zero-priced
	 * products belong to product_zero_price. One finding row per discount row.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productDiscountNotLower(): CheckResult {
		$final_price = "(CASE WHEN `dc`.`type` = 'P' THEN (`p`.`price` - (`p`.`price` * (`dc`.`price` / 100))) WHEN `dc`.`type` = 'S' THEN (`p`.`price` - `dc`.`price`) ELSE `dc`.`price` END)";

		return $this->collect('product_discount_not_lower', $this->productSelect("IF(`dc`.`special` = '1', 'special', 'discount') AS `kind`, `dc`.`customer_group_id`, `dc`.`quantity`, `dc`.`type`, `dc`.`price` AS `value`, `p`.`price`, ROUND(" . $final_price . ", 4) AS `final_price`") . " INNER JOIN `" . DB_PREFIX . "product_discount` `dc` ON (`dc`.`product_id` = `p`.`product_id`) WHERE `p`.`price` > '0' AND " . $final_price . " >= `p`.`price` ORDER BY `p`.`product_id`, `dc`.`product_discount_id`");
	}

	/**
	 * Products whose stock went below zero while orders subtract it: oversold
	 * or edited by hand. Disabled products are included, the count is wrong
	 * either way.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productNegativeStock(): CheckResult {
		return $this->collect('product_negative_stock', $this->productSelect('`p`.`quantity`') . " WHERE `p`.`subtract` = '1' AND `p`.`quantity` < '0' ORDER BY `p`.`product_id`");
	}

	/**
	 * Enabled products in stock but below their minimum order quantity, so
	 * the stock that is there cannot be bought. Out-of-stock products are not
	 * reported: that is a normal state shown by the stock status.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productMinimumOverStock(): CheckResult {
		return $this->collect('product_minimum_over_stock', $this->productSelect('`p`.`quantity`, `p`.`minimum`') . " WHERE `p`.`status` = '1' AND `p`.`subtract` = '1' AND `p`.`quantity` > '0' AND `p`.`minimum` > `p`.`quantity` ORDER BY `p`.`product_id`");
	}

	/**
	 * Products sharing a model with another product outside their variant
	 * family, one finding row per product; `group_size` is the number of
	 * families using that model. Models are trimmed, and the column collation
	 * already ignores case. Variants share the master's model by design
	 * (admin addVariant/editVariants copy it unless overridden), so a family
	 * counts once: the master's id, also for a variant of a variant. Core only
	 * validates the model's length, not uniqueness. Products with a missing
	 * master belong to product_broken_variant and are skipped, as are empty
	 * models.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productDuplicateModel(): CheckResult {
		$families = $this->variantFamilies("TRIM(`p2`.`model`) AS `model`") . " AND TRIM(`p2`.`model`) != ''";

		return $this->collect('product_duplicate_model', $this->productSelect("`f`.`model`, `g`.`group_size`") . " INNER JOIN (" . $families . ") `f` ON (`f`.`product_id` = `p`.`product_id`) INNER JOIN (SELECT `model`, COUNT(DISTINCT `family`) AS `group_size` FROM (" . $families . ") `f2` GROUP BY `model` HAVING `group_size` > 1) `g` ON (`g`.`model` = `f`.`model`) ORDER BY `f`.`model`, `p`.`product_id`");
	}

	/**
	 * Products sharing an identifier value (same code type, e.g. two EANs)
	 * with another product outside their variant family, one finding row per
	 * product and value; `group_size` is the number of families using it.
	 * Only enabled identifier types count; values are trimmed and compared
	 * case-insensitively, empty ones skipped. Families as in
	 * product_duplicate_model.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productDuplicateIdentifier(): CheckResult {
		$codes = "SELECT DISTINCT `f`.`product_id`, `f`.`family`, `pc`.`code`, TRIM(`pc`.`value`) AS `value` FROM `" . DB_PREFIX . "product_code` `pc` INNER JOIN `" . DB_PREFIX . "identifier` `i` ON (`i`.`code` = `pc`.`code` AND `i`.`status` = '1') INNER JOIN (" . $this->variantFamilies() . ") `f` ON (`f`.`product_id` = `pc`.`product_id`) WHERE TRIM(`pc`.`value`) != ''";

		return $this->collect('product_duplicate_identifier', $this->productSelect("`c`.`code`, `c`.`value`, `g`.`group_size`") . " INNER JOIN (" . $codes . ") `c` ON (`c`.`product_id` = `p`.`product_id`) INNER JOIN (SELECT `code`, `value`, COUNT(DISTINCT `family`) AS `group_size` FROM (" . $codes . ") `c2` GROUP BY `code`, `value` HAVING `group_size` > 1) `g` ON (`g`.`code` = `c`.`code` AND `g`.`value` = `c`.`value`) ORDER BY `c`.`code`, `c`.`value`, `p`.`product_id`");
	}

	/**
	 * Related product links pointing at a deleted product, one finding row per
	 * link. The storefront drops such links (getRelated joins the product), so
	 * this is leftover data. Links of deleted products are GARBAGE.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productRelatedBroken(): CheckResult {
		return $this->collect('product_related_broken', $this->productSelect('`pr`.`related_id`') . " INNER JOIN `" . DB_PREFIX . "product_related` `pr` ON (`pr`.`product_id` = `p`.`product_id`) WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product` `r` WHERE `r`.`product_id` = `pr`.`related_id`) ORDER BY `p`.`product_id`, `pr`.`related_id`");
	}

	/**
	 * Related links stored in one direction only, one finding row per link.
	 * Admin addRelated always writes both directions, so a one-way link came
	 * from an import, another module or direct SQL. Links to deleted
	 * products belong to product_related_broken.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productRelatedOneWay(): CheckResult {
		return $this->collect('product_related_one_way', $this->productSelect('`pr`.`related_id`') . " INNER JOIN `" . DB_PREFIX . "product_related` `pr` ON (`pr`.`product_id` = `p`.`product_id`) WHERE EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product` `r` WHERE `r`.`product_id` = `pr`.`related_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_related` `rr` WHERE `rr`.`product_id` = `pr`.`related_id` AND `rr`.`related_id` = `pr`.`product_id`) ORDER BY `p`.`product_id`, `pr`.`related_id`");
	}

	/**
	 * Layout overrides pointing at a deleted layout, one finding row per store.
	 * The storefront falls back to the route layout only for layout_id 0
	 * (catalog/controller/common/column_left.php), so the product page loses
	 * all its modules. The admin refuses to delete a layout in use, so these
	 * come from imports or direct SQL.
	 *
	 * @return \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult
	 */
	protected function productBrokenLayout(): CheckResult {
		return $this->collect('product_broken_layout', $this->productSelect('`p2l`.`store_id`, `p2l`.`layout_id`') . " INNER JOIN `" . DB_PREFIX . "product_to_layout` `p2l` ON (`p2l`.`product_id` = `p`.`product_id`) WHERE `p2l`.`layout_id` != '0' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "layout` `l` WHERE `l`.`layout_id` = `p2l`.`layout_id`) ORDER BY `p`.`product_id`, `p2l`.`store_id`");
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

	/**
	 * Variant family of every product whose master exists: the product's own
	 * id for a master, the master's id for a variant, the master's master for
	 * a variant of a variant. Products with a missing master are left out.
	 *
	 * @param string $columns extra select list over `p2` (product) and `m2` (its master)
	 *
	 * @return string SELECT product_id, family ... with an open WHERE that callers may extend with AND
	 */
	private function variantFamilies(string $columns = ''): string {
		return "SELECT `p2`.`product_id`, IF(`p2`.`master_id` = '0', `p2`.`product_id`, IF(`m2`.`master_id` != '0', `m2`.`master_id`, `p2`.`master_id`)) AS `family`" . ($columns ? ", " . $columns : "") . " FROM `" . DB_PREFIX . "product` `p2` LEFT JOIN `" . DB_PREFIX . "product` `m2` ON (`m2`.`product_id` = `p2`.`master_id`) WHERE (`p2`.`master_id` = '0' OR `m2`.`product_id` IS NOT NULL)";
	}
}
