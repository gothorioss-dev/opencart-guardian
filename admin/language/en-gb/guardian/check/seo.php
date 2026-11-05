<?php
// Heading
$_['heading_title']                             = 'SEO';

// Checks
$_['check_seo_duplicate_keyword_title']         = 'SEO URLs leading to different pages';
$_['check_seo_duplicate_keyword_desc']          = 'The same SEO URL keyword is set in one store for several different pages (products, categories, manufacturers or information pages).';
$_['check_seo_duplicate_keyword_impact']        = 'The storefront opens only one of these pages by that address; the others cannot be reached by their SEO URL, and links to them lead to the wrong page.';
$_['check_seo_duplicate_keyword_hint']          = 'Give each page its own keyword in Design > SEO URL or on the SEO tab of the product, category, manufacturer or information page.';
$_['check_seo_empty_keyword_title']             = 'SEO URLs with an empty keyword';
$_['check_seo_empty_keyword_desc']              = 'An SEO URL record has an empty keyword.';
$_['check_seo_empty_keyword_impact']            = 'With SEO URLs enabled, links to this page lead to the store\'s home page; the page also cannot be saved in the admin until a keyword is entered.';
$_['check_seo_empty_keyword_hint']              = 'Enter a keyword in Design > SEO URL or on the SEO tab of the page.';
$_['check_seo_invalid_keyword_title']           = 'SEO URLs with invalid characters';
$_['check_seo_invalid_keyword_desc']            = 'The keyword contains characters other than letters, digits, / - and _ (for example a space, ?, %, #, & or a dot).';
$_['check_seo_invalid_keyword_impact']          = 'Browsers encode such characters or treat them as part of the address syntax (? starts parameters, # an anchor), so the link may open a "not found" page or look like %20 codes; the page also cannot be saved in the admin until the keyword is fixed.';
$_['check_seo_invalid_keyword_hint']            = 'Change the keyword in Design > SEO URL or on the SEO tab of the page, using only letters, digits, - and _.';
$_['check_seo_orphan_url_title']                = 'SEO URLs of deleted pages';
$_['check_seo_orphan_url_desc']                 = 'The SEO URL points to a product, category, manufacturer, information page or article that no longer exists.';
$_['check_seo_orphan_url_impact']               = 'Its address opens a "not found" page, and the keyword stays taken: the admin will not let you give it to another page of the store.';
$_['check_seo_orphan_url_hint']                 = 'Delete the record in Design > SEO URL, or point it at an existing page.';
$_['check_seo_orphan_store_language_title']     = 'SEO URLs of a deleted store or language';
$_['check_seo_orphan_store_language_desc']      = 'The SEO URL belongs to a store or language that no longer exists.';
$_['check_seo_orphan_store_language_impact']    = 'Customers will not notice; the record only clutters Design > SEO URL, and in its store the keyword stays taken for other pages.';
$_['check_seo_orphan_store_language_hint']      = 'Delete the record in Design > SEO URL.';
$_['check_seo_missing_url_title']               = 'Products without an SEO URL';
$_['check_seo_missing_url_desc']                = 'The enabled product is available in a store with SEO URLs turned on, but has no SEO URL there in any language.';
$_['check_seo_missing_url_impact']              = 'Customers and search engines see this product at a technical address with parameters instead of a readable one; the page itself still opens.';
$_['check_seo_missing_url_hint']                = 'Enter a keyword on the SEO tab of the product for this store.';
$_['check_seo_urls_disabled_title']             = 'Stores with SEO URLs turned off';
$_['check_seo_urls_disabled_desc']              = 'SEO URLs are turned off in this store, so its products are not checked for missing SEO URLs.';
$_['check_seo_urls_disabled_impact']            = 'All store pages open at technical addresses with parameters, and the keywords you entered are not used.';
$_['check_seo_urls_disabled_hint']              = 'If this is not intended, turn on Use SEO URLs in Settings, Server tab. The web server must support URL rewriting: on Apache, the mod-rewrite module and a .htaccess file.';

// Column
$_['column_store_id']                           = 'Store';
$_['column_language']                           = 'Language';
$_['column_keyword']                            = 'Keyword';
$_['column_key']                                = 'Key';
$_['column_value']                              = 'Value';
$_['column_targets']                            = 'Pages with this keyword';
$_['column_language_id']                        = 'Language ID';
$_['column_name']                               = 'Name';
