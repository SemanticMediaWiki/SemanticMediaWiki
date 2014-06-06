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
 * Egyptian Arabic language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Meno25
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageArz extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'الصفحة', // name of page datatype
		'_txt' => 'نص',  // name of the text type
		'_cod' => 'كود',  // name of the (source) code type
		'_boo' => 'منطقى',  // name of the boolean type
		'_num' => 'عدد',  // name for the datatype of numbers
		'_geo' => 'الإحداثيات الجغرافية', // name of the geocoord type
		'_tem' => 'الحرارة',  // name of the temperature type
		'_dat' => 'التاريخ',  // name of the datetime (calendar) type
		'_ema' => 'البريد الإلكترونى',  // name of the email type
		'_uri' => 'مسار',  // name of the URL type
		'_anu' => 'التعليق علي معرف الموارد الموحد',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
		'_qty' => 'Quantity', // name of the number type with units of measurement //TODO: translate
	);

	protected $m_DatatypeAliases = array(
		'URI'         => '_uri',
		'Float'       => '_num',
		'Integer'     => '_num',
		/*LTR hint for text editors*/  'سلسلة' => '_txt',  // old name of the string type
		'Enumeration' => '_txt'
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE'  => 'لديه نوع',
		'_URI'   => 'معرف الموارد الموحد معادلة',
		'_SUBP' => 'الخاصية الفرعية ل',
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_INST' => 'Category', // TODO: translate
		'_UNIT' => 'عرض الوحدات',
		'_IMPO' => 'المستوردة من',
		'_CONV' => 'يقابل',
		'_SERV' => 'يوفر الخدمة',
		'_PVAL' => 'يسمح بالقيمة',
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
		'_ASKDU'=> 'Query duration', // TODO: translate
		'_MEDIA'=> 'Media type',
		'_MIME' => 'Mime type'
	);

	protected $m_SpecialPropertyAliases = array(
		'عرض الوحدة' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'خاصية',
		SMW_NS_PROPERTY_TALK  => 'نقاش_الخاصية',
		SMW_NS_TYPE           => 'نوع',
		SMW_NS_TYPE_TALK      => 'نقاش_النوع',
		SMW_NS_CONCEPT        => 'مبدأ',
		SMW_NS_CONCEPT_TALK   => 'نقاش_المبدأ'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر" );

	protected $m_monthsshort = array( "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر" );

}




