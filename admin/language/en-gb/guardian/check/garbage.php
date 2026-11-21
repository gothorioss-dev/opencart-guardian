<?php
// Heading
$_['heading_title'] = 'Leftover data';

// Checks
$_['check_garbage_orphan_description_title']                  = 'Names and descriptions of deleted records';
$_['check_garbage_orphan_description_desc']                   = 'The name or description of a product, category, page or other record is still stored, but the record itself has been deleted.';
$_['check_garbage_orphan_description_impact']                 = 'Customers will not notice: nothing shows these texts; they only take up space in the database.';
$_['check_garbage_orphan_description_hint']                   = 'The admin cannot show or delete these texts. Remove them with a database tool after making a backup, or leave them: they do no harm.';
$_['check_garbage_description_bad_language_title']            = 'Texts in a deleted language';
$_['check_garbage_description_bad_language_desc']             = 'The record exists, but one of its names or descriptions belongs to a language that has been deleted.';
$_['check_garbage_description_bad_language_impact']           = 'Customers will not notice: the storefront shows texts only in installed languages; these only take up space in the database.';
$_['check_garbage_description_bad_language_hint']             = 'Open the record in its admin section (for example Catalog > Products) and save it: only texts in installed languages are kept.';

// Column
$_['column_type']                                             = 'Record type';
$_['column_record_id']                                        = 'Deleted record ID';
$_['column_name']                                             = 'Name';
$_['column_languages']                                        = 'Languages';
$_['check_garbage_description_bad_language_column_record_id'] = 'Record ID';
$_['column_language_id']                                      = 'Deleted language ID';

// Value
$_['value_type_product']                                      = 'Product';
$_['value_type_category']                                     = 'Category';
$_['value_type_information']                                  = 'Information page';
$_['value_type_attribute']                                    = 'Attribute';
$_['value_type_attribute_group']                              = 'Attribute group';
$_['value_type_option']                                       = 'Option';
$_['value_type_option_value']                                 = 'Option value';
$_['value_type_filter']                                       = 'Filter';
$_['value_type_filter_group']                                 = 'Filter group';
$_['value_type_download']                                     = 'Download';
$_['value_type_article']                                      = 'Article';
$_['value_type_topic']                                        = 'Topic';
$_['value_type_customer_group']                               = 'Customer group';
$_['value_type_custom_field']                                 = 'Custom field';
$_['value_type_custom_field_value']                           = 'Custom field value';
$_['value_type_country']                                      = 'Country';
$_['value_type_zone']                                         = 'Zone';
$_['value_type_length_class']                                 = 'Length class';
$_['value_type_weight_class']                                 = 'Weight class';
$_['value_type_subscription_plan']                            = 'Subscription plan';
