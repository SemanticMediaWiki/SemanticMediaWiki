<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

global $smwgIP;
include_once ( $smwgIP . 'languages/SMW_Language.php' );

/**
 * Simplified Chinese language labels for important SMW labels (namespaces, datatypes,...).
 * This file is originally translated from Tradition Chinese by using an electronic dictionary.
 * Then, it is manually reviewed and updated (August 18, 2014).
 * Please contribute any corrections to the SMW project.
 *
 * @author 張致信 (Roc Michael)
 * @author 张林 (Lin Zhang)
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageZh_cn extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => '页面型', // name of page datatype
		'_txt' => '文本型', // name of the text type
		'_cod' => '代码型', // name of the (source) code type
		'_boo' => '布尔型', // name of the boolean type
		'_num' => '数值型', // name for the datatype of numbers
		'_geo' => '地理坐标型',	// name of the geocoord type
		'_tem' => '温度型', // name of the temperature type
		'_dat' => '日期型', // name of the datetime (calendar) type
		'_ema' => '电子邮件地址型', // name of the email type
		'_uri' => 'URL型', // name of the URL type
		'_anu' => '注释URI型', // name of the annotation URI type (OWL annotation property)
		'_tel' => '电话号码型', // name of the telephone (URI) type
		'_rec' => '记录型', // name of record data type
		'_qty' => '数量型' // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'浮点型'      => '_num',
		'整数型'      => '_num',
		'枚举型'      => '_txt',
		'字符串型'    => '_txt', // old name of the string type
		// SMW0.7 compatibility:
		'Float'       => '_num',
		'Integer'     => '_num',
		'Enumeration' => '_txt',
		'URI'         => '_uri'
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => '具有类型', // Has type
		'_URI'  => '等价URI', // Equivalent URI
		'_SUBP' => '是……的子属性', // Subproperty of (to be reviewed)
		'_SUBC' => '是……的子分类', // Subcategory of (to be reviewed)
		'_UNIT' => '显示单位', // Display unit
		'_IMPO' => '导入自', // Imported from
		'_CONV' => '对应于', // Corresponds to
		'_SERV' => '提供服务', // Provides service
		'_PVAL' => '允许取值', // Allows value
		'_MDAT' => '修改日期', // Modification date
		'_CDAT' => '创建日期', // Creation date
		'_NEWP' => '是一个新页面', // Is a new page
		'_LEDT' => '最后编者为', // Last editor is
		'_ERRP' => '具有……的不当取值', // Has improper value for (to be reviewed)
		'_LIST' => '具有字段', // Has fields
		'_SOBJ' => '具有子对象', // Has subobject
		'_ASK'  => '具有查询', // Has query
		'_ASKST'=> '查询字符串', // Query string
		'_ASKFO'=> '查询格式', // Query format
		'_ASKSI'=> '查询大小', // Query size
		'_ASKDE'=> '查询深度', // Query depth
		'_ASKDU'=> '查询持续时间', // Query duration
		'_MEDIA'=> '媒体类型', //Media type
		'_MIME' => 'MIME类型' //Mime type
	);

	protected $m_SpecialPropertyAliases = array(
		'显示计量单位' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => '属性', // Property
		SMW_NS_PROPERTY_TALK  => '属性讨论', // Property_talk
		SMW_NS_TYPE           => '类型', // Type
		SMW_NS_TYPE_TALK      => '类型讨论', // Type_talk
		SMW_NS_CONCEPT        => '概念', // Concept
		SMW_NS_CONCEPT_TALK   => '概念讨论' // Concept_talk
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" );

}
