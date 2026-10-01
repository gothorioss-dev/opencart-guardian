<?php
// Heading
$_['heading_title']                               = 'Product data integrity';

// Checks
$_['check_product_no_model_title']                = 'Products without model';
$_['check_product_no_model_desc']                 = 'Model is a required field; products without it break search and product feeds.';
$_['check_product_no_model_hint']                 = 'Open each product and fill in the Model field on the Data tab.';
$_['check_product_no_category_title']             = 'Products without a category';
$_['check_product_no_category_desc']              = 'Products not assigned to any category are unreachable from the catalog navigation.';
$_['check_product_no_category_hint']              = 'Assign at least one category on the product Links tab.';
$_['check_product_no_store_title']                = 'Products not assigned to a store';
$_['check_product_no_store_desc']                 = 'Products without a store link are not shown on any storefront.';
$_['check_product_no_store_hint']                 = 'Tick at least one store on the product Links tab.';
$_['check_product_shipping_no_weight_title']      = 'Shippable products without weight';
$_['check_product_shipping_no_weight_desc']       = 'Products that require shipping but have zero weight break weight-based shipping rates.';
$_['check_product_shipping_no_weight_hint']       = 'Set the weight on the product Data tab, or turn off Requires Shipping for non-physical goods.';
$_['check_product_no_manufacturer_title']         = 'Products without a manufacturer';
$_['check_product_no_manufacturer_desc']          = 'Products without a manufacturer are missing from brand pages and filters.';
$_['check_product_no_manufacturer_hint']          = 'Choose a manufacturer on the product Links tab if the product has one.';
$_['check_product_future_available_title']        = 'Enabled products with a future availability date';
$_['check_product_future_available_desc']         = 'The storefront hides products until their Date Available, even when they are enabled.';
$_['check_product_future_available_hint']         = 'Check the Date Available field on the product Data tab; clear it or set today if the product should be visible now.';
$_['check_product_shipping_no_dimensions_title']  = 'Shippable products without dimensions';
$_['check_product_shipping_no_dimensions_desc']   = 'Products that require shipping but have zero length, width or height; carrier integrations that rate by size cannot quote them.';
$_['check_product_shipping_no_dimensions_hint']   = 'Fill in Dimensions (L x W x H) on the product Data tab if your shipping methods use them.';
$_['check_product_broken_variant_title']          = 'Variants with a broken master link';
$_['check_product_broken_variant_desc']           = 'The variant\'s master product is missing, is the variant itself, or is a variant too. The storefront and cart take a variant\'s options from its master, so such a variant loses its options.';
$_['check_product_broken_variant_hint']           = 'Delete the broken variant and create it again from the master product: Catalog > Products, action menu of the master > Add Variant.';
$_['check_product_broken_reference_title']        = 'Products referencing deleted records';
$_['check_product_broken_reference_desc']         = 'The product points to a tax class, weight or length class, stock status or manufacturer that no longer exists; the field and the missing id are listed.';
$_['check_product_broken_reference_hint']         = 'Open the product and select an existing value for the listed field (Data tab; manufacturer on the Links tab).';
$_['check_product_broken_attribute_option_title'] = 'Broken attribute and option links';
$_['check_product_broken_attribute_option_desc']  = 'The product has an attribute, option or option value that was deleted from the catalog; such entries are not shown and options cannot be selected.';
$_['check_product_broken_attribute_option_hint']  = 'Open the product and remove or replace the broken entries on the Attribute and Option tabs.';
$_['check_product_empty_content_title']           = 'Products with an empty name or description';
$_['check_product_empty_content_desc']            = 'The product has an empty name or a description without any visible text (only empty editor markup) in an enabled language; the language and the empty field are listed.';
$_['check_product_empty_content_hint']            = 'Open the product and fill in the listed field on the General tab for the listed language.';
$_['check_product_incomplete_description_title']  = 'Products not translated to every language';
$_['check_product_incomplete_description_desc']   = 'The product has no description record for an enabled language; in that language the storefront shows it without a name.';
$_['check_product_incomplete_description_hint']   = 'Open the product, fill in the General tab for the listed language and save.';
