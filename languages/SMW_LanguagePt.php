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
 * Portuguese language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Semíramis Herszon, Terry A. Hurlbut, Jaideraf
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguagePt extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Página', // name of page datatype
		'_txt' => 'Texto',  // name of the text type
		'_cod' => 'Código',  // name of the (source) code type
		'_boo' => 'Booleano',  // name of the boolean type
		'_num' => 'Número',  // name for the datatype of numbers
		'_geo' => 'Coordenadas geográficas', // name of the geocoord type
		'_tem' => 'Temperatura',  // name of the temperature type
		'_dat' => 'Data',  // name of the datetime (calendar) type
		'_ema' => 'Email',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Número de telefone',  // name of the telephone (URI) type
		'_rec' => 'Registro', // name of record data type
		'_qty' => 'Quantidade', // name of the number type with units of measurement
		'_mlt_rec' => 'Monolingual text',
	);

	protected $m_DatatypeAliases = array(
		'Entidade'              => '_wpg',
		'Ponto flutuante'       => '_num',
		'Inteiro'               => '_num',
		'Coordenadas'           => '_geo',
		'E-mail'                => '_ema',
		'Anotação de URI'       => '_uri',
		'Telefone'              => '_tel',
		'URI'                   => '_uri',
		'Número inteiro'        => '_num',
		'Folga'                 => '_num',
		'Variável Booléen'      => '_boo',
		'Enumeração'            => '_txt',
		'Cadeia'                => '_txt',  // old name of the string type
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Possui tipo',
		'_URI'  => 'URI equivalente ',
		'_SUBP' => 'Subpropriedade de',
		'_SUBC' => 'Subcategoria de',
		'_UNIT' => 'Unidades de exibição',
		'_IMPO' => 'Importada de',
		'_CONV' => 'Corresponde a',
		'_SERV' => 'Fornece serviço',
		'_PVAL' => 'Perminte valor',
		'_MDAT' => 'Data de modificação',
		'_CDAT' => 'Data de criação',
		'_NEWP' => 'É uma nova página',
		'_LEDT' => 'O ultimo editor é',
		'_ERRP' => 'Possui valor impróprio para',
		'_LIST' => 'Possui campos',
		'_SOBJ' => 'Possui subobjeto',
		'_ASK'  => 'Possui consulta',
		'_ASKST'=> 'Consulta',
		'_ASKFO'=> 'Formato da consulta',
		'_ASKSI'=> 'Tamanho da consulta',
		'_ASKDE'=> 'Profundidade da consulta',
		'_ASKDU'=> 'Query duration', // TODO: translate
		'_MEDIA'=> 'Media type',
		'_MIME' => 'Mime type',
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
		'Exibe unidade' => '_UNIT',
		'É do tipo' => '_TYPE',
		'Possui URI equivalente' => '_URI',
		'Importado de' => '_IMPO',
		'Corresponde ao' => '_CONV',
		'Tem o tipo' => '_TYPE',
		'Unidade de amostra' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Propriedade",
		SMW_NS_PROPERTY_TALK  => "Discussão_propriedade",
		SMW_NS_TYPE           => "Tipo",
		SMW_NS_TYPE_TALK      => "Discussão_tipo",
		SMW_NS_CONCEPT        => 'Conceito',
		SMW_NS_CONCEPT_TALK   => 'Discussão_conceito'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro" );

	protected $m_monthsshort = array( "Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez" );

}
