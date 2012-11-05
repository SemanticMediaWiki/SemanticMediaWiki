<?php
/**
 * @file
 * @ingroup Language
 * @ingroup SMWLanguage
 * @author Ivan Lanin
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) die();

global $smwgIP;
include_once( $smwgIP . 'languages/SMW_Language.php' );


/**
 * Indonesian language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @ingroup Language
 * @ingroup SMWLanguage
 * @author Ivan Lanin
 */
class SMWLanguageId extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Halaman', // name of page datatype
		'_str' => 'String',  // name of the string type
		'_txt' => 'Teks',  // name of the text type
		'_cod' => 'Kode',  // name of the (source) code type
		'_boo' => 'Boole',  // name of the boolean type
		'_num' => 'Angka',  // name for the datatype of numbers
		'_geo' => 'Koordinat geografis', // name of the geocoord type
		'_tem' => 'Suhu',  // name of the temperature type
		'_dat' => 'Tanggal',  // name of the datetime (calendar) type
		'_ema' => 'Surel',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI anotasi',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Nomor telepon',  // name of the telephone (URI) type
		'_rec' => 'Rekaman', // name of record data type
		'_qty' => 'Quantity', // name of the number type with units of measurement //TODO: translate
	);

	protected $m_DatatypeAliases = array(
		'URI'           => '_uri',
		'Enumerasi'     => '_str',
		'Nomor telepon' => '_tel',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Memiliki tipe',
		'_URI'  => 'URI ekuivalen',
		'_SUBP' => 'Subproperti dari',
		'_SUBC' => 'Subkategori dari',
		'_UNIT' => 'Unit tampilan',
		'_IMPO' => 'Diimpor dari',
		'_CONV' => 'Berhubungan dengan',
		'_SERV' => 'Memberikan layanan',
		'_PVAL' => 'Mengizinkan nilai',
		'_MDAT' => 'Tanggal modifikasi',
		'_CDAT' => 'Creation date', // TODO: translate
		'_NEWP' => 'Is a new page', // TODO: translate
		'_LEDT' => 'Last editor is', // TODO: translate
		'_ERRP' => 'Memiliki nilai yang tidak tepat untuk',
		'_LIST' => 'Memiliki bidang',
		'_SOBJ' => 'Has subobject', // TODO: translate
		'_ASK'  => 'Has query', // TODO: translate
		'_ASKST'=> 'Query string', // TODO: translate
		'_ASKFO'=> 'Query format', // TODO: translate
		'_ASKSI'=> 'Query size', // TODO: translate
		'_ASKDE'=> 'Query depth', // TODO: translate
	);

	protected $m_SpecialPropertyAliases = array(
		'Unit tampilan' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Properti',
		SMW_NS_PROPERTY_TALK  => 'Pembicaraan_Properti',
		SMW_NS_TYPE           => 'Tipe',
		SMW_NS_TYPE_TALK      => 'Pembicaraan_Tipe',
		SMW_NS_CONCEPT        => 'Konsep',
		SMW_NS_CONCEPT_TALK   => 'Pembicaraan_Konsep'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember" );

	protected $m_monthsshort = array( "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des" );

}
