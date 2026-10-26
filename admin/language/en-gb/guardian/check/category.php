<?php
// Heading
$_['heading_title']                       = 'Category tree integrity';

// Checks
$_['check_category_broken_parent_title']  = 'Categories with a missing parent';
$_['check_category_broken_parent_desc']   = 'The category\'s parent category no longer exists; such categories come from imports or direct database edits.';
$_['check_category_broken_parent_impact'] = 'It does not appear in the store menu, the category module or the parent\'s subcategory list: customers can open it only by a direct link.';
$_['check_category_broken_parent_hint']   = 'Open the category and choose an existing category in the Parent field on the Data tab, or clear the field to make it a top-level category.';

// Column
$_['column_name']                         = 'Name';
$_['column_status']                       = 'Status';
$_['column_parent_id']                    = 'Missing parent ID';
