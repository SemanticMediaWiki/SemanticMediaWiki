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
 * Spanish language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Javier Calzada Prado, Carmen Jorge García-Reyes, Universidad Carlos III de Madrid, Jesús Espino García
 * @author Toniher
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageEs extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Página', // name of page datatype
		'_txt' => 'Texto',  // name of the text type (very long strings)
		'_cod' => 'Código',  // name of the (source) code type
		'_boo' => 'Booleano',  // name of the boolean type
		'_num' => 'Número', // name for the datatype of numbers
		'_geo' => 'Coordenadas geográficas', // name of the geocoord type
		'_tem' => 'Temperatura',  // name of the temperature type
		'_dat' => 'Fecha',  // name of the datetime (calendar) type
		'_ema' => 'Dirección electrónica',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'Anotación-URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Número de teléfono',  // name of the telephone (URI) type
		'_rec' => 'Registro', // name of record data type
		'_qty' => 'Cantidad', // name of the number type with units of measurement
		'_mlt_rec' => 'Monolingual text',
	);

	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Número entero'         => '_num',
		'Número con coma'       => '_num',
		'Cadena de caracteres'  => '_txt',  // old name of the string type
		'Enumeración'           => '_txt',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Tiene tipo de datos',
		'_URI'  => 'URI equivalente',
		'_SUBP' => 'Subpropiedad de',
		'_SUBC' => 'Subcategoría de',
		'_UNIT' => 'Unidades de medida',
		'_IMPO' => 'Importado de',
		'_CONV' => 'Corresponde a',
		'_SERV' => 'Provee servicio',
		'_PVAL' => 'Permite el valor',
		'_MDAT' => 'Fecha de modificación',
		'_CDAT' => 'Fecha de creación',
		'_NEWP' => 'Es página nueva',
		'_LEDT' => 'Último editor es',
		'_ERRP' => 'Tiene valor incorrecto para',
		'_LIST' => 'Tiene campos',
		'_SOBJ' => 'Tiene subobjeto',
		'_ASK'  => 'Tiene consulta',
		'_ASKST'=> 'Cadena de consulta',
		'_ASKFO'=> 'Formato de consulta',
		'_ASKSI'=> 'Tamaño de consulta',
		'_ASKDE'=> 'Profundidad de consulta',
		'_ASKDU'=> 'Duración de consulta',
		'_MEDIA'=> 'Tipo Media',
		'_MIME' => 'Tipo MIME',
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
		'Unidad de medida'  => '_UNIT',
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Atributo",
		SMW_NS_PROPERTY_TALK  => "Atributo_discusión",
		SMW_NS_TYPE           => "Tipo",
		SMW_NS_TYPE_TALK      => "Tipo_discusión",
		SMW_NS_CONCEPT        => 'Concepto',
		SMW_NS_CONCEPT_TALK   => 'Concepto_discusión'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre" );

	protected $m_monthsshort = array( "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic" );

	protected $preferredDateFormatsByPrecision = array(
		'SMW_PREC_Y'    => 'Y',
		'SMW_PREC_YM'   => 'M Y',
		'SMW_PREC_YMD'  => 'j M Y',
		'SMW_PREC_YMDT' => 'H:i:s j M Y'
	);
}


