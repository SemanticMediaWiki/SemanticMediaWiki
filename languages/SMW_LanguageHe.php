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
 * Hebrew language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Udi Oron אודי אורון
 * @author eranroz
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageHe extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'עמוד', // name of page datatype
		'_txt' => 'טקסט',  // name of the text type (very long strings)
		'_cod' => 'קוד',  // name of the (source) code type
		'_boo' => 'נכוןלאנכון',  // name of the boolean type
		'_num' => 'מספר', // name for the datatype of numbers
		'_geo' => 'קורדינטות גיאוגרפיות', // name of the geocoord type
		'_tem' => 'טמפרטורה',  // name of the temperature type
		'_dat' => 'תאריך',  // name of the datetime (calendar) type
		'_ema' => 'דואל',  // name of the email (URI) type
		'_uri' => 'כתובת כללית',  // name of the URL type
		'_anu' => 'מזהה יחודי לפירוש',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
		'_qty' => 'Quantity', // name of the number type with units of measurement //TODO: translate
	);

	protected $m_DatatypeAliases = array(
		/*LTR hint for text editors*/ 'מזהה יחודי' => '_uri',
		/*LTR hint for text editors*/ 'שלם' => '_num',
		/*LTR hint for text editors*/ 'נקודהצפה' => '_num',
		/*LTR hint for text editors*/ 'מחרוזת' => '_txt', // old name of the string type
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'מטיפוס',
		'_URI'  => 'מזהה יחודי תואם',
		'_SUBP' => 'רכוש כפוף ל',
		'_SUBC' => 'תת קטגוריה של',
		'_UNIT' => 'יחידות מידה',
		'_IMPO' => 'יובא מ',
		'_CONV' => 'מתורגם ל',
		'_SERV' => 'מספק שירות',
		'_PVAL' => 'ערך אפשרי',
		'_MDAT' => 'תאריך לשינוי',
		'_CDAT' => 'תאריך יצירה',
		'_NEWP' => 'האם הדף חדש',
		'_LEDT' => 'העורך האחרון ',
		'_ERRP' => 'יש ערך תקין בשביל',
		'_LIST' => 'Has fields', // TODO: translate
		'_SOBJ' => 'Has subobject', // TODO: translate
		'_ASK'  => 'Has query', // TODO: translate
		'_ASKST'=> 'מחרוזת השאילתא',
		'_ASKFO'=> 'פורמט השאילתא',
		'_ASKSI'=> 'גודל השאילתא',
		'_ASKDE'=> 'עומק שאילתא',
		'_ASKDU'=> 'Query duration', // TODO: translate
		'_MEDIA'=> 'Media type',
		'_MIME' => 'Mime type'
	);

	protected $m_SpecialPropertyAliases = array(
		'יחידת הצגה'
		                    => '_UNIT',
	);


	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'תכונה',
		SMW_NS_PROPERTY_TALK  => 'שיחת_תכונה',
		SMW_NS_TYPE           => 'טיפוס',
		SMW_NS_TYPE_TALK      => 'שיחת_טיפוס',
		SMW_NS_CONCEPT        => 'רעיון',
		SMW_NS_CONCEPT_TALK   => 'שיחת_רעיון'
	);


	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני", "יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר" );

	protected $m_monthsshort = array( "ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני", "יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר" );

}
