<?php
/**
 * @author Dmitry Khoroshev
 */

class SMW_LanguageRu {

/* private */ var $smwContentMessages = array(
	'smw_edithelp' => 'Редактирование справки по отношениям и атрибутам',
	'smw_helppage' => 'Отношение',
	'smw_viewasrdf' => 'RDF источник',
	'smw_finallistconjunct' => ' и', //used in "A, B, and C"
	'smw_factbox_head' => 'Факты о $1 &mdash; Кликните <span class="smwsearchicon">+</span> чтобы найти похожие страницы.',
	'smw_att_head' => 'Значения атрибута',
	'smw_rel_head' => 'Отношения к другим страницам',
	'smw_spec_head' => 'Специальные свойства',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Извините, но URI "$1" не доступны из этого места.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "<span class='smwwarning'>Извините, но встроенные запросы отключены для этого сайта.</span>",
	'smw_iq_moreresults' => '&hellip; следующие результаты',
	'smw_iq_nojs' => 'Используйте браузер с поддержкой JavaScript для просмотра этого элемента, или <a href="$1">просмотрите результат в виде списка</a>.',
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => '[Извините, но функции импорта не доступны для пространства имен "$1".]',
	'smw_nonright_importtype' => '[Извините, но $1 может быть использован только для статей с пространством имен "$2"]',
	'smw_wrong_importtype' => '[Извините, но $1 не может быть использован для статей с пространством имен "$2"]',
	'smw_no_importelement' => '[Извините, но элемент "$1" не доступен для импорта.]',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => ',',
	'smw_kiloseparator' => ' ',
	'smw_unknowntype' => '[Упс! Тип атрибута "$1" не поддерживается ]',
	'smw_noattribspecial' => '[Упс! Специальное свойство "$1" не является атрибутом (используйте "::" вместо ":=")]',
	'smw_notype' => '[Упс! Атрибуту не задан тип]',
	'smw_manytypes' => '[Упс! Более одного типа определено для атрибута]',
	'smw_emptystring' => '[Упс! Пустые строки не принимаются]',
	'smw_maxstring' => '[Извините, но строчное представление числа $1 слишком длинное для этого сайта.]',
	'smw_nopossiblevalues' => '[Упс! возможные значения для этого перечисления не заданы]',
	'smw_notinenum' => '[Упс! "$1" не входит в список допустимых значений ($2) для этого атрибута]',
	'smw_noboolean' => '[Упс! "$1" не является булевым значением (да/нет)]',
	'smw_true_words' => 't,yes,да,д,истина,и',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'f,no,n,нет,н,ложь,л',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nointeger' => '[Упс! "$1" не является целым числом]',
	'smw_nofloat' => '[Упс! "$1" не является десятичным числом]',
	'smw_infinite' => '[Извините, но такие длинные числа как $1 не поддерживаются этим сайтом.]',
	'smw_infinite_unit' => '[Извините, но конвертация значения в $1 привело к слишком длинному числу для этого сайта.]',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this attribute supports no unit conversion',
	'smw_unsupportedprefix' => 'префиксы ("$1") не поддерживаются в настоящее время',
	'smw_unsupportedunit' => 'конвертация единиц измерения для "$1" не поддерживается',
	// Messages for geo coordinates parsing
	'smw_err_latitude' => 'Значения для широты (N, S) должны находится в диапазоне от 0 до 90. "$1" не удовлетворяет этому условию!',
	'smw_err_longitude' => 'Значения для долготы (E, W) должны находится в диапазоне от 0 до 180. "$1" не удовлетворяет этому условию!',
	'smw_err_noDirection' => '[Упс! Что-то не так со значением "$1"]',
	'smw_err_parsingLatLong' => '[Упс! Что-то не так со значением "$1". Здесь ожидается значение вида "1°2?3.4?? W"!]',
	'smw_err_wrongSyntax' => '[Упс! Что-то не так со значением "$1". Здесь ожидается значение вида "1°2?3.4?? W, 5°6?7.8?? N"!]',
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
	'smw_service_online_maps' => " найти&nbsp;на&nbsp;карте|http://kvaleberg.com/extensions/mapsources/?params=\$1_\$3_\$5_\$7_\$2_\$4_\$6_\$8_region:EN_type:city\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => '[Упс! Дата "$1" не распознана. Как бы то ни было, в настоящее время поддержка дат находится в разработке.]'
);


/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'Эта функция в настоящее время находится в разработке. Сделайте резервную копию прежде чем продолжать.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Атрибуты типа “$1”',
	'smw_typearticlecount' => 'Отображается $1 атрибутов этого типа.',
	'smw_attribute_header' => 'Страницы, использующие атрибут “$1”',
	'smw_attributearticlecount' => '<p>Отображается $1 страниц, использующих этот атрибут.</p>',
	'smw_relation_header' => 'Страницы, использующие отношение “$1”',
	'smw_relationarticlecount' => '<p>Отображается $1 страниц, использующих это отношение.</p>',
	// Messages for Export RDF Special
    'exportrdf' => 'Экспорт страниц в RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Эта страница позволяет экспортировать части статьи в формате RDF. Наберите названия необходимых статей по одному на строку.</p>',
	'smw_exportrdf_recursive' => 'Рекурсивный экспорт всех связанных страниц. Результат этой операции может быть очень большим!',
	'smw_exportrdf_backlinks' => 'Также экспортировать все страницы, которые ссылаются на экспортируемые страницы. Генерирует RDF с поддержкой полноценной навигации.',
	// Messages for Search Triple Special
	'searchtriple' => 'Простой семантический поиск', //name of this special
	'smw_searchtriple_docu' => "<p>Для поиска отношений используйте верхнюю строку. Для поиска атрибутов используйте нижнюю строку. Для пустого поля будут выведены все варианты. Если значение атрибута задано, то имя атрибута также должно быть задано. Значения атрибутов могут быть заданы с единицой измерения.</p>",
	'smw_searchtriple_subject' => 'Страница-субъект:',
	'smw_searchtriple_relation' => 'Имя отношения:',
	'smw_searchtriple_attribute' => 'Имя атрибута:',
	'smw_searchtriple_object' => 'Страница-объект:',
	'smw_searchtriple_attvalue' => 'Значение атрибута:',
	'smw_searchtriple_searchrel' => 'Искать Отношения',
	'smw_searchtriple_searchatt' => 'Искать Атрибуты',
	'smw_searchtriple_resultrel' => 'Результаты поиска (отношения)',
	'smw_searchtriple_resultatt' => 'Результаты поиска (атрибуты)',
	// Messages for Relations Special
	'relations' => 'Отношения',
	'smw_relations_docu' => 'Существуют следующие отношения.',
	// Messages for WantedRelations Special
	'wantedrelations' => 'Отношения без страниц',
	'smw_wanted_relations' => 'Следующие отношения не имеют страниц с описанием, хотя и используются для описания других страниц.',
	// Messages for Attributes Special
	'attributes' => 'Атрибуты',
	'smw_attributes_docu' => 'Существуют следующие атрибуты.',
	'smw_attr_type_join' => ' с типом $1',
	// Messages for Unused Relations Special
	'unusedrelations' => 'Неиспользуемые отношения',
	'smw_unusedrelations_docu' => 'Следующие отношения не используются.',
	// Messages for Unused Attributes Special
	'unusedattributes' => 'Неиспользуемые атрибуты',
	'smw_unusedattributes_docu' => 'Следующие атрибуты не используются.',
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


	// Messages for ask Special
	'ask' => 'Семантический поиск',
	'smw_ask_docu' => '<p>Наберите запрос в форме поиска. Формат запроса приведен на <a href="$1">странице справки</a>.</p>',
	'smw_ask_doculink' => 'Семантический поиск',
	'smw_ask_sortby' => 'Сортировать по столбцу',
	'smw_ask_ascorder' => 'По возрастанию',
	'smw_ask_descorder' => 'По убыванию',
	'smw_ask_submit' => 'Найти',
	// Messages for search by relation Special
	'searchbyrelation' => 'Искать по отношению',
	'smw_tb_docu' => '<p>Искать все страницы, которые содержат указанное отношение к заданной странице.</p>',
	'smw_tb_notype' => '<p>Укажите отношение или <a href="$2">просмотрите все ссылки на $1.</a></p>',
	'smw_tb_notarget' => '<p>Укажите страницу или просмотрите все отношения $1.</p>',
	'smw_tb_displayresult' => 'Список всех страниц, которые содержат отношение $1 к странице $2.',
	'smw_tb_linktype' => 'Отношение',
	'smw_tb_linktarget' => 'к',
	'smw_tb_submit' => 'Найти',
	// Messages for the search by attribute special
	'searchbyattribute' => 'Искать по атрибуту',
	'smw_sbv_docu' => '<p>Искать все страницы, которые содержат указанный атрибут и значение.</p>',
	'smw_sbv_noattribute' => '<p>Укажите атрибут.</p>',
	'smw_sbv_novalue' => '<p>Укажите значение или просмотрите все значения атрибута $1.</p>',
	'smw_sbv_displayresult' => 'Список всех страниц, которые содержат атрибут $1 со значением $2.',
	'smw_sbv_attribute' => 'Атрибут',
	'smw_sbv_value' => 'значение',
	'smw_sbv_submit' => 'Найти',
	// Messages for the browsing system
	'browse' => 'Browse wiki', //TODO: translate
	'smw_browse_article' => 'Enter the name of the page to start browsing from.', //TODO: translate
	'smw_browse_go' => 'Go', //TODO: translate
	'smw_browse_more' => '&hellip;', //TODO: translate
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Предыдущая',
	'smw_result_next' => 'Следующая',
	'smw_result_results' => 'Результаты',
	'smw_result_noresults' => 'Извините, но ничего не найдено.'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'Строка',  // name of the string type
	'smw_enum' => 'Перечисление',  // name of the enum type
	'smw_bool' => 'Булево',  // name of the boolean type
	'smw_int' => 'Целое',  // name of the int type
	'smw_float' => 'Десятичное',  // name of the floating point type
	'smw_length' => 'Длинна',  // name of the length type
	'smw_area' => 'Площадь',  // name of the area type
	'smw_geolength' => 'Географическое расстояние',  // OBSOLETE name of the geolength type
	'smw_geoarea' => 'Географическая площадь',  // OBSOLETE name of the geoarea type
	'smw_geocoordinate' => 'Географическая координата', // name of the geocoord type
	'smw_mass' => 'Масса',  // name of the mass type
	'smw_time' => 'Время',  // name of the time (duration) type
	'smw_temperature' => 'Температура',  // name of the temperature type
	'smw_datetime' => 'Дата',  // name of the datetime (calendar) type
	'smw_email' => 'Почта',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'URI аннотации'  // name of the annotation URI type (annotation property)
);

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Имеет тип',
	SMW_SP_HAS_URI   => 'Эквивалентный URI',
	SMW_SP_IS_SUBRELATION_OF   => 'Является подчиненным отношением для',
	SMW_SP_IS_SUBATTRIBUTE_OF   => 'Является подчиненным атрибутом для',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Основная отображаемая единица',
	SMW_SP_DISPLAY_UNIT => 'Отображаемая единица',
	SMW_SP_IMPORTED_FROM => 'Импортировано из',
	SMW_SP_CONVERSION_FACTOR => 'Относится к',
	SMW_SP_CONVERSION_FACTOR_SI => 'Corresponds to SI', // TODO translate
	SMW_SP_SERVICE_LINK => 'Предоставляет сервис',
	SMW_SP_POSSIBLE_VALUE => 'Возможные значения' // TODO: check translation, should be "Allowed value" (singular)
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Отношение',
			SMW_NS_RELATION_TALK  => 'Отношение_дискуссия',
			SMW_NS_ATTRIBUTE      => 'Атрибут',
			SMW_NS_ATTRIBUTE_TALK => 'Атрибут_дискуссия',
			SMW_NS_TYPE           => 'Тип',
			SMW_NS_TYPE_TALK      => 'Тип_дискуссия'
		);
	}

	/**
	 * Function that returns the localized label for a datatype.
	 */
	function getDatatypeLabel($msgid) {
		return $this->smwDatatypeLabels[$msgid];
	}

	/**
	 * Function that returns the labels for the special relations and attributes.
	 */
	function getSpecialPropertiesArray() {
		return $this->smwSpecialProperties;
	}

	/**
	 * Function that returns all content messages (those that are stored
	 * in some article, and can thus not be translated to individual users).
	 */
	function getContentMsgArray() {
		return $this->smwContentMessages;
	}

	/**
	 * Function that returns all user messages (those that are given only to
	 * the current user, and can thus be given in the individual user language).
	 */
	function getUserMsgArray() {
		return $this->smwUserMessages;
	}
}

?>
