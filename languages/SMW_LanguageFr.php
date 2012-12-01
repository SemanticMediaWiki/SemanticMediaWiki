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
 * French language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Pierre Matringe
 * @author LIMAFOX76
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageFr extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Page', // name of page datatype
		'_str' => 'Chaîne de caractères',  // name of the string type
		'_txt' => 'Texte',  // name of the text type (very long strings)
		'_cod' => 'Code',  // name of the (source) code type
		'_boo' => 'Booléen',  // name of the boolean type
		'_num' => 'Nombre', // name for the datatype of numbers
		'_geo' => 'Coordonnées géographiques', // name of the geocoord type
		'_tem' => 'Température',  // name of the temperature type
		'_dat' => 'Date',  // name of the datetime (calendar) type
		'_ema' => 'Adresse électronique',  // name of the email type
		'_uri' => 'URL',  // name of the URI type
		'_anu' => 'Annotation-URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Numéro de téléphone',  // name of the telephone (URI) type
		'_rec' => 'Enregistrement', // name of record data type
		'_qty' => 'Quantité', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Nombre entier'         => '_num',
		'Nombre décimal'        => '_num',
		'Énumeration'           => '_str',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'A le type',
		'_URI'  => 'URI équivalente',
		'_SUBP' => 'Sous-propriété de',
		'_SUBC' => 'Sous-catégorie de',
		'_UNIT' => 'Unités de mesure',
		'_IMPO' => 'Importé de',
		'_CONV' => 'Correspond à',
		'_SERV' => 'Fournit le service',
		'_PVAL' => 'Valeur possible',
		'_MDAT' => 'Date de modification',
		'_CDAT' => 'Date de création',
		'_NEWP' => 'Est une nouvelle page',
		'_LEDT' => 'Le dernier contributeur est',
		'_ERRP' => 'A une valeur incorrecte pour',
		'_LIST' => 'A le champ',
		'_SOBJ' => 'Possède un sous-objet',
		'_ASK'  => 'Possède une requête',
		'_ASKST'=> 'Champ de requête',
		'_ASKFO'=> 'Format de requête',
		'_ASKSI'=> 'Taille de la requête',
		'_ASKDE'=> 'Profondeur de la requête',
	);

	protected $m_SpecialPropertyAliases = array(
		'Unité de mesure'   => '_UNIT',
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Attribut",
		SMW_NS_PROPERTY_TALK  => "Discussion_attribut",
		SMW_NS_TYPE           => "Type",
		SMW_NS_TYPE_TALK      => "Discussion_type",
		SMW_NS_CONCEPT        => 'Concept',
		SMW_NS_CONCEPT_TALK   => 'Discussion_concept'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" );

	protected $m_monthsshort = array( "jan", "fév", "mar", "avr", "mai", "jun", "jul", "aoû", "sep", "oct", "nov", "déc" );
}


