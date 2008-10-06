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
 * Simplified Chinese language labels for important SMW labels (namespaces, datatypes,...).
 * This file is translated from Tradition Chinese by using an electronic dictionary. Please
 * contribute any corrections to the SMW project.
 *
 * @author 張致信 (Roc Michael roc.no1\@gmail.com)
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageZh_cn extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => '页面',	//'Page', // name of page datatype
	'_str' => '字串',	//'String',  // name of the string type
	'_txt' => '文字',	//'Text',  // name of the text type
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => '布林',	//'Boolean',  // name of the boolean type
	'_num' => '数字',	//'Number',  // name for the datatype of numbers
	'_geo' => '地理学的座标',	//'Geographic coordinate', // name of the geocoord type
	'_tem' => '温度',	//'Temperature',  // name of the temperature type
	'_dat' => '日期',	//'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email',	//'Email',  // name of the email type
	'_uri' => 'URL',	//'URL',  // name of the URL type
	'_anu' => 'URI的注解',	//'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'浮点数'       => '_num',	//'_num',
	'整数'         => '_num' ,	//'_num',
	 '列举'        => '_str',	//'_str'
	// SMW0.7 compatibility:
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str',
	'URI'         => '_uri',
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
	SMW_SP_HAS_TYPE  => '设有型态',	//'Has type',
	SMW_SP_HAS_URI   => '对应的URI',	//'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => '所属的子性质',	//'Subproperty of',
    SMW_SP_DISPLAY_UNITS => '显示单位',      //Display unit
    SMW_SP_IMPORTED_FROM => '输入来源',     //Imported from
    SMW_SP_CONVERSION_FACTOR => '符合于',  //Corresponds to
    SMW_SP_SERVICE_LINK => '提供服务',      //Provides service
    SMW_SP_POSSIBLE_VALUE => '允许值'      //Allows value
);


protected $m_SpecialPropertyAliases = array(
	'Display unit' => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => '关联',	//'Relation',
	SMW_NS_RELATION_TALK  => '关联讨论',	//'Relation_talk',
	SMW_NS_PROPERTY       => '性质',	//'Property',
	SMW_NS_PROPERTY_TALK  => '性质讨论',	//'Property_talk',
	SMW_NS_TYPE           => '型态',	//'Type',
	SMW_NS_TYPE_TALK      => '型态讨论',	//'Type_talk'
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
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
