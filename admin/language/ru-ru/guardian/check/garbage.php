<?php
// Heading
$_['heading_title'] = 'Остаточные данные';

// Checks
$_['check_garbage_orphan_description_title']                  = 'Названия и описания удалённых записей';
$_['check_garbage_orphan_description_desc']                   = 'Название или описание товара, категории, страницы или другой записи по-прежнему хранится, хотя сама запись удалена.';
$_['check_garbage_orphan_description_impact']                 = 'Покупатели этого не заметят: эти тексты нигде не показываются, они только занимают место в базе данных.';
$_['check_garbage_orphan_description_hint']                   = 'В админке эти тексты не видны и не удаляются. Удалите их инструментом для работы с базой данных, предварительно сделав резервную копию, или оставьте: вреда от них нет.';
$_['check_garbage_description_bad_language_title']            = 'Тексты на удалённом языке';
$_['check_garbage_description_bad_language_desc']             = 'Запись существует, но одно из её названий или описаний относится к языку, который удалён.';
$_['check_garbage_description_bad_language_impact']           = 'Покупатели этого не заметят: витрина показывает тексты только на установленных языках, а эти лишь занимают место в базе данных.';
$_['check_garbage_description_bad_language_hint']             = 'Откройте запись в её разделе админки (например, «Каталог → Товары») и сохраните: сохраняются только тексты на установленных языках.';
$_['check_garbage_orphan_link_title']                         = 'Связи удалённых записей';
$_['check_garbage_orphan_link_desc']                          = 'Удалённый товар, категория, страница, купон или другая запись по-прежнему привязаны к магазинам, категориям, макетам или другим данным.';
$_['check_garbage_orphan_link_impact']                        = 'Покупатели этого не заметят: эти связи нигде не используются, они только занимают место в базе данных.';
$_['check_garbage_orphan_link_hint']                          = 'В админке эти связи не видны и не удаляются. Удалите их инструментом для работы с базой данных, предварительно сделав резервную копию, или оставьте: вреда от них нет.';
$_['check_garbage_link_deleted_target_title']                 = 'Связи с удалёнными магазинами, категориями и товарами';
$_['check_garbage_link_deleted_target_desc']                  = 'Товар, категория, страница, производитель, статья, тема или купон по-прежнему связаны с магазином, категорией, товаром или фильтром, который удалён.';
$_['check_garbage_link_deleted_target_impact']                = 'Покупатели этих связей не видят. Купон, ограниченный несуществующими товарами или категориями, может не приниматься при оформлении заказа или применяться не к тем товарам.';
$_['check_garbage_link_deleted_target_hint']                  = 'Откройте запись и сохраните её: удалённые магазины, категории, товары и фильтры не показываются в форме, и их связи удаляются при сохранении (товары — вкладка «Связи», категории — «Данные», купоны — «Общие»).';

// Column
$_['column_type']                                             = 'Тип записи';
$_['column_record_id']                                        = 'ID удалённой записи';
$_['column_name']                                             = 'Название';
$_['column_languages']                                        = 'Языков';
$_['check_garbage_description_bad_language_column_record_id'] = 'ID записи';
$_['column_language_id']                                      = 'ID удалённого языка';
$_['column_links']                                            = 'Связей';
$_['check_garbage_link_deleted_target_column_record_id']      = 'ID записи';
$_['column_target_id']                                        = 'ID удалённого объекта';

// Value
$_['value_type_product']                                      = 'Товар';
$_['value_type_category']                                     = 'Категория';
$_['value_type_information']                                  = 'Информационная страница';
$_['value_type_attribute']                                    = 'Атрибут';
$_['value_type_attribute_group']                              = 'Группа атрибутов';
$_['value_type_option']                                       = 'Опция';
$_['value_type_option_value']                                 = 'Значение опции';
$_['value_type_filter']                                       = 'Фильтр';
$_['value_type_filter_group']                                 = 'Группа фильтров';
$_['value_type_download']                                     = 'Файл для скачивания';
$_['value_type_article']                                      = 'Статья';
$_['value_type_topic']                                        = 'Тема';
$_['value_type_customer_group']                               = 'Группа покупателей';
$_['value_type_custom_field']                                 = 'Дополнительное поле';
$_['value_type_custom_field_value']                           = 'Значение дополнительного поля';
$_['value_type_country']                                      = 'Страна';
$_['value_type_zone']                                         = 'Регион';
$_['value_type_length_class']                                 = 'Единица длины';
$_['value_type_weight_class']                                 = 'Единица веса';
$_['value_type_subscription_plan']                            = 'План подписки';
$_['value_type_product_to_category']                          = 'Категории товара';
$_['value_type_product_to_store']                             = 'Магазины товара';
$_['value_type_product_to_download']                          = 'Файлы для скачивания товара';
$_['value_type_product_to_layout']                            = 'Макеты товара';
$_['value_type_category_to_store']                            = 'Магазины категории';
$_['value_type_category_to_layout']                           = 'Макеты категории';
$_['value_type_category_filter']                              = 'Фильтры категории';
$_['value_type_category_path']                                = 'Путь категории';
$_['value_type_information_to_store']                         = 'Магазины информационной страницы';
$_['value_type_information_to_layout']                        = 'Макеты информационной страницы';
$_['value_type_manufacturer_to_store']                        = 'Магазины производителя';
$_['value_type_manufacturer_to_layout']                       = 'Макеты производителя';
$_['value_type_article_to_store']                             = 'Магазины статьи';
$_['value_type_article_to_layout']                            = 'Макеты статьи';
$_['value_type_topic_to_store']                               = 'Магазины темы';
$_['value_type_topic_to_layout']                              = 'Макеты темы';
$_['value_type_coupon_product']                               = 'Товары купона';
$_['value_type_coupon_category']                              = 'Категории купона';
$_['value_type_coupon_history']                               = 'История использования купона';
$_['value_type_tax_rate_to_customer_group']                   = 'Группы покупателей налоговой ставки';
$_['value_type_zone_to_geo_zone']                             = 'Зоны географической зоны';
$_['value_type_custom_field_customer_group']                  = 'Группы покупателей дополнительного поля';
$_['value_type_banner_image']                                 = 'Изображения баннера';
$_['value_type_article_comment']                              = 'Комментарии к статье';
$_['value_type_marketing_report']                             = 'Переходы кампании';
$_['value_type_download_report']                              = 'Отчёт о скачиваниях';
$_['value_type_product_filter']                               = 'Фильтры товара';