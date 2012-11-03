<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) die();

global $smwgIP;
include_once( $smwgIP . 'languages/SMW_Language.php' );

/**
 * Traditional Chinese language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author 張致信 (Roc Michael roc.no1\@gmail.com)
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageZh_tw extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => '頁面',	// 'Page', // name of page datatype
		'_str' => '字串',	// 'String',  // name of the string type
		'_txt' => '文字',	// 'Text',  // name of the text type
		'_cod' => 'Code',  // name of the (source) code type //TODO: translate
		'_boo' => '布林',	// 'Boolean',  // name of the boolean type
		'_num' => '數字',	// 'Number',  // name for the datatype of numbers
		'_geo' => '地理學的座標',	// 'Geographic coordinates', // name of the geocoord type
		'_tem' => '溫度',	// 'Temperature',  // name of the temperature type
		'_dat' => '日期',	// 'Date',  // name of the datetime (calendar) type
		'_ema' => 'Email',	// 'Email',  // name of the email type
		'_uri' => 'URL',	// 'URL',  // name of the URL type
		'_anu' => 'URI的註解',	// 'Annotation URI'  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
		'_qty' => 'Quantity', // name of the number type with units of measurement //TODO: translate
	);

	protected $m_DatatypeAliases = array(
		'浮點數'       => '_num',	// '_num',
		'整數'         => '_num' ,	// '_num',
		 '列舉'        => '_str',	// '_str'
		// SMW0.7 compatibility:
		'Float'       => '_num',
		'Integer'     => '_num',
		'Enumeration' => '_str',
		'URI'         => '_uri',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => '設有型態',	// 'Has type',
		'_URI'  => '對應的URI',	// 'Equivalent URI',
		'_SUBP' => '所屬的子性質',	// 'Subproperty of',
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_UNIT' => '顯示單位',      // Display unit
		'_IMPO' => '輸入來源',     // Imported from
		'_CONV' => '符合於',  // Corresponds to
		'_SERV' => '提供服務',      // Provides service
		'_PVAL' => '允許值',      // Allows value
		'_MDAT' => 'Modification date',  // TODO: translate
		'_CDAT' => 'Creation date', // TODO: translate
		'_NEWP' => 'Is a new page', // TODO: translate
		'_LEDT' => 'Last editor is', // TODO: translate
		'_ERRP' => 'Has improper value for', // TODO: translate
		'_LIST' => 'Has fields', // TODO: translate
		'_SOBJ' => 'Has subobject', // TODO: translate
		'_ASK'  => 'Has query', // TODO: translate
		'_ASKST'=> 'Query string', // TODO: translate
		'_ASKFO'=> 'Query format', // TODO: translate
		'_ASKSI'=> 'Query size', // TODO: translate
		'_ASKDE'=> 'Query depth', // TODO: translate
	);


	protected $m_SpecialPropertyAliases = array(
		'Display unit' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => '性質',	// 'Property',
		SMW_NS_PROPERTY_TALK  => '性質討論',	// 'Property_talk',
		SMW_NS_TYPE           => '型態',	// 'Type',
		SMW_NS_TYPE_TALK      => '型態討論',	// 'Type_talk'
		SMW_NS_CONCEPT        => '概念',	 // 'Concept'
		SMW_NS_CONCEPT_TALK   => '概念討論',	// 'Concept_talk'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" );

}





