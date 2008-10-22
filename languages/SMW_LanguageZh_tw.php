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
 * Traditional Chinese language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author 張致信 (Roc Michael roc.no1\@gmail.com)
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageZh_tw extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => '頁面',	//'Page', // name of page datatype
	'_str' => '字串',	//'String',  // name of the string type
	'_txt' => '文字',	//'Text',  // name of the text type
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => '布林',	//'Boolean',  // name of the boolean type
	'_num' => '數字',	//'Number',  // name for the datatype of numbers
	'_geo' => '地理學的座標',	//'Geographic coordinate', // name of the geocoord type
	'_tem' => '溫度',	//'Temperature',  // name of the temperature type
	'_dat' => '日期',	//'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email',	//'Email',  // name of the email type
	'_uri' => 'URL',	//'URL',  // name of the URL type
	'_anu' => 'URI的註解',	//'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'浮點數'       => '_num',	//'_num',
	'整數'         => '_num' ,	//'_num',
	 '列舉'        => '_str',	//'_str'
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
	'_TYPE' => '設有型態',	//'Has type',
	'_URI'  => '對應的URI',	//'Equivalent URI',
	'_SUBP' => '所屬的子性質',	//'Subproperty of',
	'_UNIT' => '顯示單位',      //Display unit
	'_IMPO' => '輸入來源',     //Imported from
	'_CONV' => '符合於',  //Corresponds to
	'_SERV' => '提供服務',      //Provides service
	'_PVAL' => '允許值'      //Allows value
);


protected $m_SpecialPropertyAliases = array(
	'Display unit' => '_UNIT',
// support English aliases for special properties
	'Has type'          => '_TYPE',
	'Equivalent URI'    => '_URI',
	'Subproperty of'    => '_SUBP',
	'Display units'     => '_UNIT',
	'Imported from'     => '_IMPO',
	'Corresponds to'    => '_CONV',
	'Provides service'  => '_SERV',
	'Allows value'      => '_PVAL'
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => '關聯',	//'Relation',
	SMW_NS_RELATION_TALK  => '關聯討論',	//'Relation_talk',
	SMW_NS_PROPERTY       => '性質',	//'Property',
	SMW_NS_PROPERTY_TALK  => '性質討論',	//'Property_talk',
	SMW_NS_TYPE           => '型態',	//'Type',
	SMW_NS_TYPE_TALK      => '型態討論',	//'Type_talk'
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





