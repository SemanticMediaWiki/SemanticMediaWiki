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
	'_anu' => 'URI adnotacji'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Liczba zmiennoprzecinkowa' => '_num',
	'Liczba całkowita'      => '_num',
	'Wyliczenie'            => '_str',
	// support English aliases:
	'URI'                   => '_uri',
	'Float'                 => '_num',
	'Integer'               => '_num',
	'Enumeration'           => '_str',
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
	'_TYPE' => 'Ma typ',
	'_URI'  => 'Równoważne URI',
	'_SUBP' => 'Jest podwłasnością',
	'_UNIT' => 'Wyświetlane jednostki',
	'_IMPO' => 'Zaimportowane z',
	'_CONV' => 'Odpowiada',
	'_SERV' => 'Zapewnia usługę',
	'_PVAL' => 'Dopuszcza wartość',
	'_MDAT' => 'Modification date',  // TODO: translate
	'_ERRP' => 'Has improper value for' // TODO: translate
);

protected $m_SpecialPropertyAliases = array(
	'Wyświetlana jednostka' => '_UNIT',
	// support English aliases for special properties
	'Has type'          => '_TYPE',
	'Equivalent URI'    => '_URI',
	'Subproperty of'    => '_SUBP',
	'Display units'     => '_UNIT',
	'Imported from'     => '_IMPO',
	'Corresponds to'    => '_CONV',
	'Provides service'  => '_SERV',
	'Allows value'      => '_PVAL',
	'Modification date' => '_MDAT',
	'Has improper value for' => '_ERRP'
);


protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Relacja',
	SMW_NS_RELATION_TALK  => 'Dyskusja_relacji',
	SMW_NS_PROPERTY       => 'Atrybut',
	SMW_NS_PROPERTY_TALK  => 'Dyskusja_atrybutu',
	SMW_NS_TYPE           => 'Typ',
	SMW_NS_TYPE_TALK      => 'Dyskusja_typu',
	SMW_NS_CONCEPT        => 'Pojęcie',
	SMW_NS_CONCEPT_TALK   => 'Dyskusja pojęcia'
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

protected $m_dateformats = array(array(SMW_Y), array(SMW_MY,SMW_YM), array(SMW_MDY,SMW_DMY,SMW_YMD,SMW_YDM));

protected $m_months = array("styczeń", "luty", "marsz", "kwiecień", "maj", "czerwiec", "lipiec", "sierpień", "wrzesień", "październik", "listopad", "grudzień");

protected $m_monthsshort = array("sty", "lut", "mar", "kwi", "maj", "cze", "lip", "sie", "wrz", "paź", "lis", "gru");

}

