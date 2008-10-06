<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

/**
 * Russian language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Dmitry Khoroshev cnit\@uniyar.ac.ru
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageRu extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Страница', // name of page datatype
	'_str' => 'Строка',  // name of the string type
	'_txt' => 'Текст',  // name of the text type (very long strings)
	'_cod' => 'Код',  // name of the (source) code type
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
	SMW_NS_RELATION_TALK  => 'Обсуждение_отношения',
	SMW_NS_PROPERTY       => 'Свойство',
	SMW_NS_PROPERTY_TALK  => 'Обсуждение_свойства',
	SMW_NS_TYPE           => 'Тип',
	SMW_NS_TYPE_TALK      => 'Обсуждение_типа',
	SMW_NS_CONCEPT        => 'Концепция',
	SMW_NS_CONCEPT_TALK   => 'Обсуждение_концепции'
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK,
	'Concept'       => SMW_NS_CONCEPT,
	'Concept_talk'  => SMW_NS_CONCEPT_TALK
);

}
