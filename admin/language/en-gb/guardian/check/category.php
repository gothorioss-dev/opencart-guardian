<?php
// Heading
$_['heading_title']                                     = 'Category tree integrity';

// Checks
$_['check_category_broken_parent_title']                = 'Categories with a missing parent';
$_['check_category_broken_parent_desc']                 = 'The category\'s parent category no longer exists; such categories come from imports or direct database edits.';
$_['check_category_broken_parent_impact']               = 'It does not appear in the store menu, the category module or the parent\'s subcategory list: customers can open it only by a direct link.';
$_['check_category_broken_parent_hint']                 = 'Open the category and choose an existing category in the Parent field on the Data tab, or clear the field to make it a top-level category.';
$_['check_category_path_mismatch_title']                = 'Categories with a broken path';
$_['check_category_path_mismatch_desc']                 = 'The category\'s stored path from the top level does not match its chain of parent categories.';
$_['check_category_path_mismatch_impact']               = 'Menu product counts, search in subcategories and coupons limited to a category miss or wrongly include its products; in the admin category list it shows a wrong full name or is missing.';
$_['check_category_path_mismatch_hint']                 = 'Click Rebuild in the category list. If the category is still reported, its chain of parents loops back on itself: open it and choose a correct category in the Parent field on the Data tab, then rebuild again.';
$_['check_category_incomplete_description_title']       = 'Categories not translated to every language';
$_['check_category_incomplete_description_desc']        = 'The category has no description record for an enabled language.';
$_['check_category_incomplete_description_impact']      = 'In that language the storefront does not show it at all: it is missing from the menu and category lists, and its page cannot be opened.';
$_['check_category_incomplete_description_hint']        = 'Open the category, fill in the General tab for the listed language and save.';
$_['check_category_no_store_title']                     = 'Categories not assigned to a store';
$_['check_category_no_store_desc']                      = 'The category is not assigned to any store.';
$_['check_category_no_store_impact']                    = 'It is not shown on any storefront: it is missing from the menu and category lists, and its page cannot be opened.';
$_['check_category_no_store_hint']                      = 'Tick at least one store on the category Data tab.';
$_['check_category_empty_title']                        = 'Empty categories';
$_['check_category_empty_desc']                         = 'The category is enabled, but neither it nor its subcategories contain an enabled product.';
$_['check_category_empty_impact']                       = 'Customers who open it from the menu or a link find no products, only subcategories that are empty too or the message that there are no products.';
$_['check_category_empty_hint']                         = 'Add products to the category or its subcategories, or disable it on the Data tab if it is not needed yet.';
$_['check_category_duplicate_name_title']               = 'Categories with a duplicate name';
$_['check_category_duplicate_name_desc']                = 'Several categories with the same parent have the same name in one language (ignoring case and surrounding spaces).';
$_['check_category_duplicate_name_impact']              = 'Customers see identical entries in the menu and subcategory lists, and in the admin the category picker of the product form shows identical paths, so products are easily assigned to the wrong one.';
$_['check_category_duplicate_name_hint']                = 'Rename one of the categories on its General tab, or move its products to the other one and delete it.';

// Column
$_['column_name']                                       = 'Name';
$_['column_status']                                     = 'Status';
$_['column_parent_id']                                  = 'Parent ID';
$_['column_path']                                       = 'Stored path (IDs)';
$_['column_language']                                   = 'Language';
$_['column_duplicate']                                  = 'Duplicate name';
$_['column_group_size']                                 = 'Categories with this name';
$_['check_category_broken_parent_column_parent_id']     = 'Missing parent ID';
