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

// Column
$_['column_name']                                       = 'Name';
$_['column_status']                                     = 'Status';
$_['column_parent_id']                                  = 'Parent ID';
$_['column_path']                                       = 'Stored path (IDs)';
$_['check_category_broken_parent_column_parent_id']     = 'Missing parent ID';
