<?php
/**
 * @file SMW_LanguageHu.php
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
 * Hungarian language labels for important SMW labels (datatypes, special properties, ...).
 *
 * Main translations:
 * - "type" / "datatype" --> "Típus", "Adattípus"
 * - "property" --> "Tulajdonság"
 * - "special property" --> "Speciális tulajdonság"
 * - "query" --> "Lekérdezés"
 * - "subquery" --> "Részlekérdezés"
 * - "query description" --> "Lekérdezés leírása"
 * - "printout statement" --> "Megjelenítés"
 *
 * @author Ronkay János Péter
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageHu extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Oldal', // name of the page datatype
		'_txt' => 'Szöveg', // name of the text datatype
		'_cod' => 'Forráskód', // name of the (source) code datatype
		'_boo' => 'Logikai', // name of the boolean datatype
		'_num' => 'Szám', // name for the number datatype
		'_geo' => 'Földrajzi koordináta', // name of the geocoordinates datatype
		'_tem' => 'Hőmérséklet', // name of the temperature datatype
		'_dat' => 'Dátum', // name of the datetime (calendar) datatype
		'_ema' => 'E-Mail', // name of the e-mail datatype
		'_uri' => 'URL', // name of the URL datatype
		'_anu' => 'Annotált URI', // name of the annotation URI datatype (OWL annotation property)
		'_tel' => 'Telefonszám', // name of the telephone number URI datatype
		'_rec' => 'Rekord', // name of the record datatype
		'_qty' => 'Mennyiség', // name of the quantity datatype
		'_mlt_rec' => 'Monolingual text',
	);

	protected $m_DatatypeAliases = array(
		'URI'			=> '_uri',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Típusa',
		'_URI'  => 'Megegyező URL',
		'_SUBP' => 'Altulajdonsága',
		'_SUBC' => 'Alkategóriája',
		'_UNIT' => 'Mértékegysége',
		'_IMPO' => 'Importálva',
		'_CONV' => 'Egyenértékű',
		'_SERV' => 'Nyújtott szolgáltatása',
		'_PVAL' => 'Lehetséges értéke',
		'_MDAT' => 'Utolsó módosítása',
		'_CDAT' => 'Létrehozva',
		'_NEWP' => 'Új oldal',
		'_LEDT' => 'Utolsó szerkesztője',
		'_ERRP' => 'Érvénytelen értéke',
		'_LIST' => 'Mezője',
		'_SOBJ' => 'Alobjektuma',
		'_ASK'  => 'Lekérdezése',
		'_ASKST'=> 'Lekérdezése szövege',
		'_ASKFO'=> 'Lekérdezése formátuma',
		'_ASKSI'=> 'Lekérdezése nagysága',
		'_ASKDE'=> 'Lekérdezése mélysége',
		'_ASKDU'=> 'Lekérdezése időtartama',
		'_MEDIA'=> 'Médiatípusa',
		'_MIME' => 'MIME-Típusa',
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
		'Adattípusa' => '_TYPE',
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Tulajdonság",
		SMW_NS_PROPERTY_TALK  => "Tulajdonságvita",
		SMW_NS_TYPE           => "Típus",
		SMW_NS_TYPE_TALK      => "Típusvita",
		SMW_NS_CONCEPT        => 'Koncepció',
		SMW_NS_CONCEPT_TALK   => 'Koncepcióvita',
	);

	//protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );
	protected $m_dateformats = array( array( SMW_Y ), array( SMW_YM ), array( SMW_YMD ) );

	protected $m_months = array( "Január", "Február", "Március", "Április", "Május", "Június", "Július", "Augusztus", "Szeptember", "Október", "November", "December" );

	protected $m_monthsshort = array( "Jan", "Feb", "Már", "Ápr", "Máj", "Jún", "Júl", "Aug", "Sze", "Okt", "Nov", "Dec" );

}
