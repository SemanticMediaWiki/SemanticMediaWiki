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
 * Traditional Chinese language labels for important SMW labels (namespaces, datatypes,...).
 * Manually reviewed and updated (August 18, 2014).
 * Please contribute any corrections to the SMW project.
 *
 * @author 張致信 (Roc Michael)
 * @author 張林 (Lin Zhang)
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageZh_tw extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => '頁面型', // name of page type
		'_txt' => '文字型', // name of the text type
		'_cod' => '代碼型', // name of the (source) code type
		'_boo' => '布爾型', // name of the boolean type
		'_num' => '數值型', // name for the number type
		'_geo' => '地理坐標型',	// name of the geocoord type
		'_tem' => '溫度型', // name of the temperature type
		'_dat' => '日期型', // name of the datetime type
		'_ema' => '電子郵件地址型', // name of the email type
		'_uri' => 'URL型', // name of the URL type
		'_anu' => '注釋URI型', // name of the annotation URI type (OWL annotation property)
		'_tel' => '電話號碼型',  // name of the telephone (URI) type
		'_rec' => '記錄型', // name of record type
		'_qty' => '數量型', // name of the number type with units of measurement
		'_mlt_rec' => 'Monolingual text',
	);

	protected $m_DatatypeAliases = array(
		'浮點型'      => '_num',
		'整數型'      => '_num',
		'枚舉型'      => '_txt',
		'字串型'      => '_txt', // old name of the string type
		// SMW0.7 compatibility:
		'Float'       => '_num',
		'Integer'     => '_num',
		'Enumeration' => '_txt',
		'URI'         => '_uri'
	);

	protected $m_SpecialProperties = array(
		'_TYPE' => '具有類型', // Has type
		'_URI'  => '等價URI', // Equivalent URI
		'_SUBP' => '是……的子屬性', // Subproperty of (to be reviewed)
		'_SUBC' => '是……的子分類', // Subcategory of (to be reviewed)
		'_UNIT' => '顯示單位', // Display unit
		'_IMPO' => '導入自', // Imported from
		'_CONV' => '對應於', // Corresponds to
		'_SERV' => '提供服務', // Provides service
		'_PVAL' => '允許取值', // Allows value
		'_MDAT' => '修改日期', // Modification date
		'_CDAT' => '創建日期', // Creation date
		'_NEWP' => '是一個新頁面', // Is a new page
		'_LEDT' => '最後編者為', //Last editor is
		'_ERRP' => '具有……的不當取值', // Has improper value for (to be reviewed)
		'_LIST' => '具有欄位', // Has fields
		'_SOBJ' => '具有子對象', // Has subobject
		'_ASK'  => '具有查詢', // Has query
		'_ASKST'=> '查詢字串', // Query string
		'_ASKFO'=> '查詢格式', // Query format
		'_ASKSI'=> '查詢大小', // Query size
		'_ASKDE'=> '查詢深度', // Query depth
		'_ASKDU'=> '查詢持續時間', // Query duration
		'_MEDIA'=> '媒體類型', // Media type
		'_MIME' => 'MIME類型', // MIME type
		'_ERRC' => 'Has processing error',
		'_ERRT' => 'Has processing error text',
		'_PREC'  => 'Display precision of',
		'_LCODE' => 'Language code',
		'_TEXT'  => 'Text',
		'_PDESC' => 'Has property description',
		'_PVAP'  => 'Allows pattern',
		'_DTITLE' => 'Display title of',
		'_PVUC' => 'Has uniqueness constraint',
	);

	protected $m_SpecialPropertyAliases = array(
		'顯示計量單位' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => '屬性',	// 'Property',
		SMW_NS_PROPERTY_TALK  => '屬性討論',	// 'Property_talk',
		SMW_NS_TYPE           => '類型',	// 'Type',
		SMW_NS_TYPE_TALK      => '類型討論',	// 'Type_talk'
		SMW_NS_CONCEPT        => '概念',	// 'Concept'
		SMW_NS_CONCEPT_TALK   => '概念討論'	// 'Concept_talk'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" );

	protected $preferredDateFormatsByPrecision = array(
		'SMW_PREC_Y'    => 'Y年',
		'SMW_PREC_YM'   => 'Y年n月',
		'SMW_PREC_YMD'  => 'Y年n月j日 (D)',
		'SMW_PREC_YMDT' => 'Y年n月j日 (D) H:i:s'
	);

}
