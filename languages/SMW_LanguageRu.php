<?php
/**
 * @author Dmitry Khoroshev
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageRu extends SMW_Language {

protected $smwContentMessages = array(
	'smw_edithelp' => 'Редактирование справки по отношениям и атрибутам',
	'smw_helppage' => 'Отношение',
	'smw_viewasrdf' => 'RDF источник',
	'smw_finallistconjunct' => ' и', //used in "A, B, and C"
	'smw_factbox_head' => 'Факты о $1 &mdash; Кликните <span class="smwsearchicon">+</span> чтобы найти похожие страницы.',
	'smw_spec_head' => 'Специальные свойства',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Извините, но URI "$1" не доступны из этого места.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "<span class='smwwarning'>Извините, но встроенные запросы отключены для этого сайта.</span>",
	'smw_iq_moreresults' => '&hellip; следующие результаты',
	'smw_iq_nojs' => 'Используйте браузер с поддержкой JavaScript для просмотра этого элемента, или <a href="$1">просмотрите результат в виде списка</a>.',
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Но функции импорта не доступны для пространства имен "$1".',
	'smw_nonright_importtype' => 'Но $1 может быть использован только для статей с пространством имен "$2".',
	'smw_wrong_importtype' => 'Но $1 не может быть использован для статей с пространством имен "$2".',
	'smw_no_importelement' => 'Но элемент "$1" не доступен для импорта.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => ',',
	'smw_kiloseparator' => ' ',
	'smw_unknowntype' => 'Тип атрибута "$1" не поддерживается.',
	'smw_manytypes' => 'Более одного типа определено для атрибута.',
	'smw_emptystring' => 'Пустые строки не принимаются.',
	'smw_maxstring' => 'Но строчное представление числа $1 слишком длинное для этого сайта.',
	'smw_nopossiblevalues' => 'Возможные значения для этого перечисления не заданы.',
	'smw_notinenum' => '"$1" не входит в список допустимых значений ($2) для этого атрибута.',
	'smw_noboolean' => '"$1" не является булевым значением (да/нет).',
	'smw_true_words' => 't,yes,да,д,истина,и',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'f,no,n,нет,н,ложь,л',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nointeger' => '"$1" не является целым числом.',
	'smw_nofloat' => '"$1" не является десятичным числом.',
	'smw_infinite' => 'Но такие длинные числа как $1 не поддерживаются этим сайтом.',
	'smw_infinite_unit' => 'Но конвертация значения в $1 привело к слишком длинному числу для этого сайта.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this attribute supports no unit conversion',
	'smw_unsupportedprefix' => 'Префиксы ("$1") не поддерживаются в настоящее время.',
	'smw_unsupportedunit' => 'Конвертация единиц измерения для "$1" не поддерживается.',
	// Messages for geo coordinates parsing
	'smw_err_latitude' => 'Значения для широты (N, S) должны находится в диапазоне от 0 до 90. "$1" не удовлетворяет этому условию!',
	'smw_err_longitude' => 'Значения для долготы (E, W) должны находится в диапазоне от 0 до 180. "$1" не удовлетворяет этому условию!',
	'smw_err_noDirection' => 'Что-то не так со значением "$1".',
	'smw_err_parsingLatLong' => 'Что-то не так со значением "$1". Здесь ожидается значение вида "1°2?3.4?? W"!',
	'smw_err_wrongSyntax' => 'Что-то не так со значением "$1". Здесь ожидается значение вида "1°2?3.4?? W, 5°6?7.8?? N"!',
	'smw_err_sepSyntax' => 'Значение "$1" похоже на правильное, но широта и долгота должна быть разделена символом "," или ";".',
	'smw_err_notBothGiven' => 'Вам следует указать правильное значение как для широты (E, W) так и для долготы (N, S)! Одного не хватает!',
	// additionals ...
	'smw_label_latitude' => 'Широта:',
	'smw_label_longitude' => 'Долгота:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " найти&nbsp;на&nbsp;карте|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=ru&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'Дата "$1" не распознана. Как бы то ни было, в настоящее время поддержка дат находится в разработке.',
	// Errors and notices related to queries // TODO: translate
	'smw_toomanyclosing' => 'There appear to be too many occurrences of “$1” in the query.',
	'smw_noclosingbrackets' => 'Some use of “[&#x005B;” in your query was not closed by a matching “]]”.',
	'smw_misplacedsymbol' => 'The symbol “$1” was used in a place where it is not useful.',
	'smw_unexpectedpart' => 'The part “$1” of the query was not understood. Results might not be as expected.',
	'smw_emtpysubquery' => 'Some subquery has no valid condition.',
	'smw_misplacedsubquery' => 'Some subquery was used in a place where no subqueries are allowed.',
	'smw_valuesubquery' => 'Subqueries not supported for values of property “$1”.',
	'smw_overprintoutlimit' => 'The query contains too many printout requests.',
	'smw_badprintout' => 'Some print statement in the query was misshaped.',
	'smw_badtitle' => 'Sorry, but “$1” is no valid page title.',
	'smw_badqueryatom' => 'Some part “[#x005B;&hellip]]” of the query was not understood.',
	'smw_propvalueproblem' => 'The value of property “$1” was not understood.',
	'smw_nodisjunctions' => 'Disjunctions in queries are not supported in this wiki and part of the query was dropped ($1).',
	'smw_querytoolarge' => 'The following query conditions could not be considered due to the wikis restrictions in query size or depth: $1.'
);


protected $smwUserMessages = array(
	'smw_devel_warning' => 'Эта функция в настоящее время находится в разработке. Сделайте резервную копию прежде чем продолжать.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Атрибуты типа “$1”',
	'smw_typearticlecount' => 'Отображается $1 атрибутов этого типа.',
	'smw_attribute_header' => 'Страницы, использующие атрибут “$1”',
	'smw_attributearticlecount' => '<p>Отображается $1 страниц, использующих этот атрибут.</p>',
	// Messages for Export RDF Special
    'exportrdf' => 'Экспорт страниц в RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Эта страница позволяет экспортировать части статьи в формате RDF. Наберите названия необходимых статей по одному на строку.</p>',
	'smw_exportrdf_recursive' => 'Рекурсивный экспорт всех связанных страниц. Результат этой операции может быть очень большим!',
	'smw_exportrdf_backlinks' => 'Также экспортировать все страницы, которые ссылаются на экспортируемые страницы. Генерирует RDF с поддержкой полноценной навигации.',
	'smw_exportrdf_lastdate' => 'Do not export pages that were not changed since the given point in time.', // TODO: translate
	// Messages for Properties Special
	'properties' => 'Properties', //TODO: translate
	'smw_properties_docu' => 'The following properties are used in the wiki.', //TODO: translate
	'smw_property_template' => '$1 of type $2 ($3)', // <propname> of type <type> (<count>) //TODO: translate
	'smw_propertylackspage' => 'All properties should be described by a page!', //TODO: translate
	'smw_propertylackstype' => 'No type was specified for this property (assuming type $1 for now).', //TODO: translate
	'smw_propertyhardlyused' => 'This property is hardly used within the wiki!', //TODO: translate
	'smw_propertyspecial' => 'This is a special property with a reserved meaning in the wiki.', // TODO: translate
	// Messages for Unused Properties Special
	'unusedproperties' => 'Unused Properties', //TODO: translate
	'smw_unusedproperties_docu' => 'The following properties exist although no other page makes use of them.', //TODO: translate
	'smw_unusedproperty_template' => '$1 of type $2', // <propname> of type <type> //TODO: translate
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Wanted Properties', //TODO: translate
	'smw_wantedproperties_docu' => 'The following properties are used in the wiki but do not yet have a page for describing them.', //TODO: translate
	'smw_wantedproperty_template' => '$1 ($2 uses)', // <propname> (<count> uses) //TODO: translate
//// Note to translators:
//// The following messages in comments were kept for reference to facilitate the translation of the property messages above.
//// Delete them when no longer needed.
// 	// Messages for Relations Special
// 	'relations' => 'Отношения',
// 	'smw_relations_docu' => 'Существуют следующие отношения.',
// 	// Messages for WantedRelations Special
// 	'wantedrelations' => 'Отношения без страниц',
// 	'smw_wanted_relations' => 'Следующие отношения не имеют страниц с описанием, хотя и используются для описания других страниц.',
// 	// Messages for Attributes Special
// 	'attributes' => 'Атрибуты',
// 	'smw_attributes_docu' => 'Существуют следующие атрибуты.',
// 	'smw_attr_type_join' => ' с типом $1',
// 	// Messages for Unused Relations Special
// 	'unusedrelations' => 'Неиспользуемые отношения',
// 	'smw_unusedrelations_docu' => 'Следующие отношения не используются.',
// 	// Messages for Unused Attributes Special
// 	'unusedattributes' => 'Неиспользуемые атрибуты',
// 	'smw_unusedattributes_docu' => 'Следующие атрибуты не используются.',
	// Messages for the refresh button
	'tooltip-purge' => 'Нажмите здесь для обновления всех шаблонов на этой странице',
	'purge' => 'Обновить',
	// Messages for Import Ontology Special
	'ontologyimport' => 'Импорт онтологии',
	'smw_oi_docu' => 'Это специальная страница для импорта онтологий. Формат онтологии приведен на <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">странице справки</a>.',
	'smw_oi_action' => 'Импорт',
	'smw_oi_return' => 'Вернутся к <a href="$1">Импорту онтологий</a>.',
	'smw_oi_noontology' => 'Онтология не задана или не может быть загружена.',
	'smw_oi_select' => 'Пожалуйста, выберите утверждения для импорта и нажмите кнопку импорта.',
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
	'smw_types_docu' => 'Список поддерживаемых типов атрибутов. Каждый тип имеет страницу с информацией.',
	'smw_types_units' => 'Стандартный тип: $1; поддерживаемые типы: $2',
	'smw_types_builtin' => 'Встроенные типы',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantic Statistics', // TODO: translate
	'smw_semstats_text' => 'This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.', // TODO: translate
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Properties', // TODO: translate
	'smw_fattributes' => 'The pages listed below have an incorrectly defined property. The number of incorrect properties is given in the brackets.', // TODO: translate
	// Messages for ask Special
	'ask' => 'Семантический поиск',
	'smw_ask_docu' => '<p>Наберите запрос в форме поиска. Формат запроса приведен на <a href="$1">странице справки</a>.</p>',
	'smw_ask_doculink' => 'Семантический поиск',
	'smw_ask_sortby' => 'Сортировать по столбцу',
	'smw_ask_ascorder' => 'По возрастанию',
	'smw_ask_descorder' => 'По убыванию',
	'smw_ask_submit' => 'Найти',
	// Messages for the search by property special
	'searchbyproperty' => 'Искать по атрибуту',
	'smw_sbv_docu' => '<p>Искать все страницы, которые содержат указанный атрибут и значение.</p>',
	'smw_sbv_noproperty' => '<p>Укажите атрибут.</p>',
	'smw_sbv_novalue' => '<p>Укажите значение или просмотрите все значения атрибута $1.</p>',
	'smw_sbv_displayresult' => 'Список всех страниц, которые содержат атрибут $1 со значением $2.',
	'smw_sbv_property' => 'Атрибут',
	'smw_sbv_value' => 'значение',
	'smw_sbv_submit' => 'Найти',
	// Messages for the browsing system
	'browse' => 'Browse wiki', //TODO: translate
	'smw_browse_article' => 'Enter the name of the page to start browsing from.', //TODO: translate
	'smw_browse_go' => 'Go', //TODO: translate
	'smw_browse_more' => '&hellip;', //TODO: translate
	// Messages for the page property special
	'pageproperty' => 'Page property search', // TODO: translate
	'smw_pp_docu' => 'Search for all the fillers of a property on a given page. Please enter both a page and a property.', // TODO: translate
	'smw_pp_from' => 'From page', // TODO: translate
	'smw_pp_type' => 'Property', // TODO: translate
	'smw_pp_submit' => 'Find results', // TODO: translate
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Предыдущая',
	'smw_result_next' => 'Следующая',
	'smw_result_results' => 'Результаты',
	'smw_result_noresults' => 'Извините, но ничего не найдено.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'Строка',  // name of the string type
	'_txt' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'_enu' => 'Перечисление',  // name of the enum type
	'_boo' => 'Булево',  // name of the boolean type
	'_int' => 'Целое',  // name of the int type
	'_flt' => 'Десятичное',  // name of the floating point type
	'_geo' => 'Географическая координата', // name of the geocoord type
	'_tem' => 'Температура',  // name of the temperature type
	'_dat' => 'Дата',  // name of the datetime (calendar) type
	'_ema' => 'Почта',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'URI аннотации'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Integer'               => '_int',
	'Float'                 => '_flt',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Имеет тип',
	SMW_SP_HAS_URI   => 'Эквивалентный URI',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_MAIN_DISPLAY_UNIT => 'Основная отображаемая единица',
	SMW_SP_DISPLAY_UNIT => 'Отображаемая единица',
	SMW_SP_IMPORTED_FROM => 'Импортировано из',
	SMW_SP_CONVERSION_FACTOR => 'Относится к',
	SMW_SP_SERVICE_LINK => 'Предоставляет сервис',
	SMW_SP_POSSIBLE_VALUE => 'Возможные значения' // TODO: check translation, should be "Allowed value" (singular)
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	public function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Отношение',
			SMW_NS_RELATION_TALK  => 'Отношение_дискуссия',
			SMW_NS_PROPERTY       => 'Атрибут',
			SMW_NS_PROPERTY_TALK  => 'Атрибут_дискуссия',
			SMW_NS_TYPE           => 'Тип',
			SMW_NS_TYPE_TALK      => 'Тип_дискуссия'
		);
	}
}
