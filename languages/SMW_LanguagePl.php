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
 * Polish language labels for important SMW labels (namespaces, datatypes,...).
 *
 * To further translators: some key terms appear * in multiple strings.
 * If you wish to change them, please be consistent.  The following
 * translations are currently used:
 *
 *   relation = relacja
 *   attribute = atrybut
 *   property = własność
 *   subject article = artykuł podmiotowy
 *   object article = artykuł przedmiotowy
 *   statement = zdanie
 *   conversion = konwersja
 *   search (n) = szukanie
 *   sorry, oops ~ niestety, ojej
 *
 * These ones may need to be refined:
 *   to support = wspierać
 *   on this site = w tym miejscu
 *
 * @author Łukasz Bolikowski
 * @version 0.3
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguagePl extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Strona', // name of page datatype
		'_str' => 'Łańcuch znaków',  // name of the string type
		'_txt' => 'Tekst',  // name of the text type (very long strings)
		'_cod' => 'Kod',  // name of the (source) code type
		'_boo' => 'Wartość logiczna',  // name of the boolean type
		'_num' => 'Liczba', // name for the datatype of numbers
		'_geo' => 'Współrzędne geograficzne', // name of the geocoord type
		'_tem' => 'Temperatura',  // name of the temperature type
		'_dat' => 'Data',  // name of the datetime (calendar) type
		'_ema' => 'Email',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI adnotacji',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
		'_qty' => 'Quantity', // name of the number type with units of measurement //TODO: translate
	);

	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Liczba zmiennoprzecinkowa' => '_num',
		'Liczba całkowita'      => '_num',
		'Wyliczenie'            => '_str',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Ma typ',
		'_URI'  => 'Równoważne URI',
		'_SUBP' => 'Jest podwłasnością',
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_UNIT' => 'Wyświetlane jednostki',
		'_IMPO' => 'Zaimportowane z',
		'_CONV' => 'Odpowiada',
		'_SERV' => 'Zapewnia usługę',
		'_PVAL' => 'Dopuszcza wartość',
		'_MDAT' => 'Modification date',  // TODO: translate
		'_ERRP' => 'Has improper value for', // TODO: translate
		'_LIST' => 'Has fields', // TODO: translate
		'_SOBJ' => 'Has subobject', // TODO: translate
	);

	protected $m_SpecialPropertyAliases = array(
		'Wyświetlana jednostka' => '_UNIT',
	);


	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Atrybut',
		SMW_NS_PROPERTY_TALK  => 'Dyskusja_atrybutu',
		SMW_NS_TYPE           => 'Typ',
		SMW_NS_TYPE_TALK      => 'Dyskusja_typu',
		SMW_NS_CONCEPT        => 'Pojęcie',
		SMW_NS_CONCEPT_TALK   => 'Dyskusja pojęcia'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "styczeń", "luty", "marsz", "kwiecień", "maj", "czerwiec", "lipiec", "sierpień", "wrzesień", "październik", "listopad", "grudzień" );

	protected $m_monthsshort = array( "sty", "lut", "mar", "kwi", "maj", "cze", "lip", "sie", "wrz", "paź", "lis", "gru" );

}

