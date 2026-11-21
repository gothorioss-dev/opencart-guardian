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

// Column
$_['column_type']                                             = 'Тип записи';
$_['column_record_id']                                        = 'ID удалённой записи';
$_['column_name']                                             = 'Название';
$_['column_languages']                                        = 'Языков';
$_['check_garbage_description_bad_language_column_record_id'] = 'ID записи';
$_['column_language_id']                                      = 'ID удалённого языка';

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
