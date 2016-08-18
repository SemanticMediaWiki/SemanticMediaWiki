<?php
/**
 * @file SMW_LanguageDe.php
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
 * German language labels for important SMW labels (datatypes, special properties, ...).
 *
 * Main translations:
 * - "type" / "datatype" --> "Datentyp"
 * - "property" --> "Attribut"
 * - "special property" --> "Spezialattribut" / ("Besonderes Attribut")
 * - "query" --> "Abfrage" / ("Anfrage")
 * - "subquery" --> "Teilabfrage" / ("Teilanfrage")
 * - "query description" --> "Abfragebeschreibung"
 * - "printout statement" --> "Ausgabeanweisung"
 *
 * @author Markus Krötzsch
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageDe extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Seite', // name of the page datatype
		'_txt' => 'Text', // name of the text datatype
		'_cod' => 'Quellcode', // name of the (source) code datatype
		'_boo' => 'Wahrheitswert', // name of the boolean datatype
		'_num' => 'Zahl', // name for the number datatype
		'_geo' => 'Geografische Koordinaten', // name of the geocoordinates datatype
		'_tem' => 'Temperatur', // name of the temperature datatype
		'_dat' => 'Datum', // name of the datetime (calendar) datatype
		'_ema' => 'E-Mail', // name of the e-mail datatype
		'_uri' => 'URL', // name of the URL datatype
		'_anu' => 'URI-Annotation', // name of the annotation URI datatype (OWL annotation property)
		'_tel' => 'Telefonnummer', // name of the telephone number URI datatype
		'_rec' => 'Verbund', // name of the record datatype
		'_qty' => 'Maß', // name of the quantity datatype
		'_mlt_rec' => 'Einsprachiger Text',
		'_eid' => 'Externe Kennung'
	);

	protected $m_DatatypeAliases = array(
		'URI' => '_uri',
		'Ganze Zahl' => '_num',
		'Dezimalzahl' => '_num',
		'Aufzählung' => '_txt',
		'Zeichenkette' => '_txt',
		'Menge'	=> '_qty'
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Datentyp',
		'_URI'  => 'Gleichwertige URI',
		'_SUBP' => 'Unterattribut von',
		'_SUBC' => 'Unterkategorie von',
		'_UNIT' => 'Einheiten',
		'_IMPO' => 'Importiert aus',
		'_CONV' => 'Entspricht',
		'_SERV' => 'Bietet Service',
		'_PVAL' => 'Erlaubt Wert',
		'_MDAT' => 'Zuletzt geändert',
		'_CDAT' => 'Erstellt',
		'_NEWP' => 'Ist eine neue Seite',
		'_LEDT' => 'Letzter Bearbeiter ist',
		'_ERRP' => 'Hat unpassenden Wert für',
		'_LIST' => 'Hat Komponenten',
		'_SOBJ' => 'Hat Unterobjekt',
		'_ASK'  => 'Hat Abfrage',
		'_ASKST'=> 'Abfragetext',
		'_ASKFO'=> 'Abfrageformat',
		'_ASKSI'=> 'Abfragegröße',
		'_ASKDE'=> 'Abfragetiefe',
		'_ASKDU'=> 'Abfragedauer',
		'_MEDIA'=> 'Medientyp',
		'_MIME' => 'MIME-Typ',
		'_ERRC' => 'Verarbeitungsfehler',
		'_ERRT' => 'Verarbeitungsfehlerhinweis',
		'_PREC' => 'Anzeigegenauigkeit',
		'_LCODE' => 'Sprachcode',
		'_TEXT' => 'Text',
		'_PDESC' => 'Attributbeschreibung',
		'_PVAP' => 'Erlaubt Muster',
		'_DTITLE' => 'Anzeigetitel',
		'_PVUC' => 'Erlaubt Wert einmal',
		'_PEID' => 'Kennung zur externen URI',
		'_PEFU' => 'Formatierungsanweisung zur externen URI'
	);

	protected $m_SpecialPropertyAliases = array(
		'Hat Datentyp' => '_TYPE',
		'Hat erlaubten Wert' => '_PVAL',
		'Hat Einheiten' => '_UNIT',
		'Hat Medientyp' => '_MEDIA',
		'Hat MIME-Typ' => '_MIME',
		'Abfrage' => '_ASK',
		'Unterobjekt' => '_SOBJ',
		'Komponenten' => '_LIST',
		'Neue Seite' => '_NEWP',
		'Letzer Bearbeiter' => '_LEDT',
		'Ausgabeeinheit' => '_UNIT',
		'Gleichwertige URI von' => '_URI',
		'Unpassender Wert' => '_ERRP',
		'Hat Sprachcode' => '_LCODE',
		'Hat Anzeigegenauigkeit' => '_PREC',
		'Hat Attributbeschreibung' => '_PDESC',
		'Hat Anzeigetitel' => '_DTITLE',
		'Hat einmal erlaubten Wert' => '_PVUC',
		'Externe Kennung' => '_PEID'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY      => 'Attribut',
		SMW_NS_PROPERTY_TALK => 'Attribut_Diskussion',
		SMW_NS_TYPE          => 'Datentyp',
		SMW_NS_TYPE_TALK     => 'Datentyp_Diskussion',
		SMW_NS_CONCEPT       => 'Konzept',
		SMW_NS_CONCEPT_TALK  => 'Konzept_Diskussion'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );

	protected $m_monthsshort = array( 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' );
	
	protected $preferredDateFormatsByPrecision = array(
		'SMW_PREC_Y' => 'Y',
		'SMW_PREC_YM' => 'F Y',
		'SMW_PREC_YMD' => 'j. F Y',
		'SMW_PREC_YMDT' => 'j. F Y, H:i:s Uhr'
	);

}
