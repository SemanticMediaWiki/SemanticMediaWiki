<?php
/**
 * @author Dmitry Khoroshev
 * @author cnit@uniyar.ac.ru
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageRu extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Редактирование справки по свойствам',
	'smw_viewasrdf' => 'RDF источник',
	'smw_finallistconjunct' => ' и', //used in "A, B, and C"
	'smw_factbox_head' => 'Факты: $1',
	'smw_isspecprop' => 'Это свойство является специальным для данного сайта.',
	'smw_isknowntype' => 'Этот тип данных принадлежит к стандартным типам данных данного сайта.',
	'smw_isaliastype' => 'Этот тип данных является альтернативным именем типа данных “$1”.',
	'smw_isnotype' => 'Тип данных “$1” не был определен.',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Извините, но ссылки из диапазона "$1" не доступны отсюда.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "Извините, но встроенные запросы отключены для этого сайта.",
	'smw_iq_moreresults' => '&hellip; следующие результаты',
	'smw_iq_nojs' => 'Используйте браузер с поддержкой JavaScript для просмотра этого элемента.', // TODO: check if this is a sentence (Markus pruned it ;-)
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Ошибка: Функции импорта не доступны для пространства имен "$1".',
	'smw_nonright_importtype' => 'Ошибка: $1 может быть использован только для статей с пространством имен "$2".',
	'smw_wrong_importtype' => 'Ошибка: $1 не может быть использован для статей с пространством имен "$2".',
	'smw_no_importelement' => 'Ошибка: Элемент "$1" не доступен для импорта.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => ',',
	'smw_kiloseparator' => ' ',
	'smw_notitle' => '“$1” не может быть использован как заголовок статьи на данном сайте.',
	'smw_unknowntype' => 'Тип "$1" не поддерживается для данного свойства.',
	'smw_manytypes' => 'Более одного типа определено для свойства.',
	'smw_emptystring' => 'Пустые строки недопустимы.',
	'smw_maxstring' => 'Ошибка: Строковое представление $1 слишком длинное для этого сайта.',
	'smw_notinenum' => '"$1" не входит в список допустимых значений ($2) для этого свойства.',
	'smw_noboolean' => '"$1" не является булевым значением (да/нет).',
	'smw_true_words' => 'да,t,yes,д,истина,и,true',	// comma-separated synonyms for boolean TRUE besides '1', principal value first
	'smw_false_words' => 'нет,f,no,n,н,ложь,л,false',	// comma-separated synonyms for boolean FALSE besides '0', principal value first
	'smw_nofloat' => '"$1" не является числом.',
	'smw_infinite' => 'Ошибка: Столь длинные числа как $1 не поддерживаются этим сайтом.',
	'smw_infinite_unit' => 'Ошибка: Преобразование значения в единицы измерения “$1” привело к слишком длинному числу для этого сайта.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this attribute supports no unit conversion',
	'smw_unsupportedprefix' => 'Префиксы для чисел ("$1") не поддерживаются в настоящее время.',
	'smw_unsupportedunit' => 'Преобразование единиц измерения для "$1" не поддерживается.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'Числовое значение перед символом “$1” отсутствует.', // $1 is something like °
	'smw_bad_latlong' => 'Широта и долгота должны быть заданы только один раз, и с корректными координатами.',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	'smw_label_latitude' => 'Широта:',
	'smw_label_longitude' => 'Долгота:',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " найти&nbsp;на&nbsp;карте|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=ru&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'Дата "$1" не распознана (поддержка дат находится в разработке).',
	// Errors and notices related to queries
	'smw_toomanyclosing' => 'Ошибка: Слишком много вхождений “$1” в данном запросе.',
	'smw_noclosingbrackets' => 'Ошибка: Открывающаяся пара скобок “[&#x005B;” не была закрыта парой соответствующих ей закрывающих скобок “]]” в данном запросе.',
	'smw_misplacedsymbol' => 'Ошибка: Использование символа “$1” в данном месте лишено смысла.',
	'smw_unexpectedpart' => 'Ошибка: Часть “$1” запроса не была распознана. Результаты могут отличаться от ожидаемых.',
	'smw_emptysubquery' => 'Ошибка: В одном из подзапросов не указано правильного знака условия.',
	'smw_misplacedsubquery' => 'Ошибка: Подзапрос используется в месте, где подзапросы не разрешены.',
	'smw_valuesubquery' => 'Ошибка: Подзапросы не поддерживаются для значений свойства “$1”.',
	'smw_overprintoutlimit' => 'Ошибка: Запрос содержит слишком много требований вывода.',
	'smw_badprintout' => 'Ошибка: Некоторое выражение вывода в запросе неправильно составлено.',
	'smw_badtitle' => 'Извините, но “$1” не является правильным заголовком статьи.',
	'smw_badqueryatom' => 'Ошибка: Часть запроса “[&#x005B;&hellip;]]” не была разобрана.',
	'smw_propvalueproblem' => 'Ошибка: Значение свойства “$1” не разобрано.',
	'smw_nodisjunctions' => 'Ошибка: Дизъюнкции (логическое ИЛИ) не поддерживаются данным сайтом, поэтому использующая их часть запроса была проигнорирована ($1).',
	'smw_querytoolarge' => 'Ошибка: Указанные условия запроса “$1” не могут быть выполнены из-за ограничения на глубину или размер запроса.'
);


protected $m_UserMessages = array(
	'smw_devel_warning' => 'Эта функция в настоящее время находится в разработке. Сделайте резервную копию прежде чем её использовать.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Свойства типа “$1”',
	'smw_typearticlecount' => 'Отображается $1 свойств этого типа.',
	'smw_attribute_header' => 'Страницы, использующие свойство “$1”',
	'smw_attributearticlecount' => '<p>Отображается $1 страниц, использующих это свойство.</p>',
	// Messages for Export RDF Special
    'exportrdf' => 'Экспорт страниц в RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Эта страница позволяет экспортировать части статьи в формате RDF. Наберите заголовки необходимых статей по одному на строку.</p>',
	'smw_exportrdf_recursive' => 'Рекурсивный экспорт всех связанных страниц. Результат этой операции может быть очень большим!',
	'smw_exportrdf_backlinks' => 'Также экспортировать все страницы, которые ссылаются на экспортируемые страницы. Генерирует RDF с поддержкой полноценной навигации.',
	'smw_exportrdf_lastdate' => 'Не экспортировать страницы, которые не менялись с указанной даты.',
	// Messages for Properties Special
	'properties' => 'Свойства',
	'smw_properties_docu' => 'Следующие свойства используются на данном сайте.',
	'smw_property_template' => '$1 имеет тип $2, количество использований ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'Каждое свойство должно иметь свою страницу описания!',
	'smw_propertylackstype' => 'Данному свойству не сопоставлен тип данных (по умолчанию будет использоваться тип $1).',
	'smw_propertyhardlyused' => 'Это свойство изначально предопределено для данного сайта!',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Неиспользуемые свойства',
	'smw_unusedproperties_docu' => 'Следующие свойства определены, но не используются ни в одной из статей.',
	'smw_unusedproperty_template' => '$1 имеет тип $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Неописанные свойства',
	'smw_wantedproperties_docu' => 'Следующие свойства используются в статьях данного сайта, но не имеют соответствующих им страниц описаний.',
	'smw_wantedproperty_template' => '$1 ($2 использований)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => 'Нажмите здесь для обновления всех запросов и шаблонов на этой странице',
	'purge' => 'Обновить',
	// Messages for Import Ontology Special
	'ontologyimport' => 'Импорт онтологии',
	'smw_oi_docu' => 'Это специальная страница для импорта онтологий. Формат онтологии приведен на <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">странице справки</a>.',
	'smw_oi_action' => 'Импорт',
	'smw_oi_return' => 'Вернуться к <a href="$1">Импорту онтологий</a>.',
	'smw_oi_noontology' => 'Онтология не задана или не может быть загружена.',
	'smw_oi_select' => 'Пожалуйста, выберите утверждения для импорта и нажмите кнопку Импорт.',
	'smw_oi_textforall' => 'Текст заголовка для импорта (может быть пустым):',
	'smw_oi_selectall' => 'Включите/отключите все утверждения',
	'smw_oi_statementsabout' => 'Утверждения о',
	'smw_oi_mapto' => 'Отобразить сущность на',
	'smw_oi_comment' => 'Добавьте текст:',
	'smw_oi_thisissubcategoryof' => 'Является подкатегорией для',
	'smw_oi_thishascategory' => 'Является частью',
	'smw_oi_importedfromontology' => 'Импортировать из онтологии',
	// Messages for (data)Types Special
	'types' => 'Типы',
	'smw_types_docu' => 'Список поддерживаемых типов свойств. Каждый тип имеет страницу, на которую можно поместить его расширенное описание.',
	'smw_typeunits' => 'Единицы измерения типа “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Семантическая статистика',
	'smw_semstats_text' => 'Данный сайт содержит <b>$1</b> значений свойств, общее количество различных <a href="$3">свойств</a> равно <b>$2</b>. <b>$4</b> свойств имеют страницу описания. Определенный тип данных задан на соответствующей странице описания для <b>$5</b> из общего числа свойств. Некоторые из существующих свойств могут <a href="$6">не использоваться</a>. Свойства, для которых не созданы страницы описания, могут быть найдены по специальной ссылке <a href="$7">список неописанных свойств</a>.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Поврежденные свойства',
	'smw_fattributes' => 'Статьи, указанные ниже, содержат неправильно определенные свойства. Количество неверных свойств указано в скобках.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'Преобразователь URI',
	'smw_uri_doc' => '<p>Преобразователь URI осуществляет <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C поиск http тэгов с использованием Range-14</a>. Данная возможность упрощает поиск семантической информации.</p>',
	// Messages for ask Special
	'ask' => 'Семантический поиск',
	'smw_ask_doculink' => 'Семантический поиск',
	'smw_ask_sortby' => 'Сортировать по столбцу',
	'smw_ask_ascorder' => 'По возрастанию',
	'smw_ask_descorder' => 'По убыванию',
	'smw_ask_submit' => 'Найти',
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => 'Искать по свойству',
	'smw_sbv_docu' => '<p>Искать все страницы, которые содержат указаннок свойство и значение.</p>',
	'smw_sbv_noproperty' => '<p>Укажите свойство.</p>',
	'smw_sbv_novalue' => '<p>Укажите значение или просмотрите все значения свойства $1.</p>',
	'smw_sbv_displayresult' => 'Список всех страниц, которые содержат свойство $1 со значением $2.',
	'smw_sbv_property' => 'Свойство',
	'smw_sbv_value' => 'значение',
	'smw_sbv_submit' => 'Найти',
	// Messages for the browsing system
	'browse' => 'Просмотреть сайт',
	'smw_browse_article' => 'Введите имя страницы для начала просмотра.',
	'smw_browse_go' => 'Перейти',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Страница поиска свойств',
	'smw_pp_docu' => 'Искать все значения свойства на указанной странице. Пожалуйста введите имя страницы и имя свойства.',
	'smw_pp_from' => 'Со страницы',
	'smw_pp_type' => 'Свойство',
	'smw_pp_submit' => 'Поиск результатов',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Предыдущая',
	'smw_result_next' => 'Следующая',
	'smw_result_results' => 'Результаты',
	'smw_result_noresults' => 'Извините, но ничего не найдено.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Страница', // name of page datatype
	'_str' => 'Строка',  // name of the string type
	'_txt' => 'Текст',  // name of the text type (very long strings)
	'_boo' => 'Булево',  // name of the boolean type
	'_num' => 'Число', // name for the datatype of numbers
	'_geo' => 'Географическая координата', // name of the geocoord type
	'_tem' => 'Температура',  // name of the temperature type
	'_dat' => 'Дата',  // name of the datetime (calendar) type
	'_ema' => 'Почта',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'URI аннотации'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Целое'                 => '_num',
	'Десятичное'            => '_num',
	'Плавающее'             => '_num',
	'Перечисление'          => '_str',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Boolean'               => '_boo',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Имеет тип',
	SMW_SP_HAS_URI   => 'Эквивалентный URI',
	SMW_SP_SUBPROPERTY_OF => 'Подчиненное свойству',
	SMW_SP_DISPLAY_UNITS => 'Отображаемые единицы',
	SMW_SP_IMPORTED_FROM => 'Импортировано из',
	SMW_SP_CONVERSION_FACTOR => 'Относится к',
	SMW_SP_SERVICE_LINK => 'Предоставляет сервис',
	SMW_SP_POSSIBLE_VALUE => 'Допустимое значение'
);

protected $m_SpecialPropertyAliases = array(
	'Отображаемая единица' => SMW_SP_DISPLAY_UNITS,
	// support English aliases for special properties
	'Has type'          => SMW_SP_HAS_TYPE,
	'Equivalent URI'    => SMW_SP_HAS_URI,
	'Subproperty of'    => SMW_SP_SUBPROPERTY_OF,
	'Display units'     => SMW_SP_DISPLAY_UNITS,
	'Imported from'     => SMW_SP_IMPORTED_FROM,
	'Corresponds to'    => SMW_SP_CONVERSION_FACTOR,
	'Provides service'  => SMW_SP_SERVICE_LINK,
	'Allows value'      => SMW_SP_POSSIBLE_VALUE
);


protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Отношение',
	SMW_NS_RELATION_TALK  => 'Отношение_дискуссия',
	SMW_NS_PROPERTY       => 'Свойство',
	SMW_NS_PROPERTY_TALK  => 'Свойство_дискуссия',
	SMW_NS_TYPE           => 'Тип',
	SMW_NS_TYPE_TALK      => 'Тип_дискуссия'
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK
);

}
