<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * This group contains all parts of SMW that relate to localisation and
 * translation.
 * @defgroup SMWLanguage SMWLanguage
 * @ingroup SMW
 */

/**
 * Base class for all SMW language classes.
 * @author Markus Krötzsch
 * @ingroup SMWLanguage
 * @ingroup Language
 */
abstract class SMWLanguage {

	// the special message arrays ...
	protected $m_DatatypeLabels;
	protected $m_DatatypeAliases = array();
	protected $m_SpecialProperties;    // Maps property ids to property names.
	protected $m_SpecialPropertyIds;   // Maps property names to property ids.
	protected $m_SpecialPropertyAliases = array();
	protected $m_Namespaces;
	protected $m_NamespaceAliases = array();
	/// Twelve strings naming the months. English is always supported in Type:Date, but
	/// we still need the English defaults to ensure that labels are returned by getMonthLabel()
	protected $m_months = array( "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" );
	/// Twelve strings briefly naming the months. English is always supported in Type:Date, so
	/// the default is simply empty (no labels in addition to English)
	protected $m_monthsshort = array();
	/// Preferred interpretations for dates with 1, 2, and 3 components. There is an array for
	/// each case, and the constants define the obvious order (e.g. SMW_YDM means "first Year,
	/// then Day, then Month). Unlisted combinations will not be accepted at all.
	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );
	/// Should English default aliases be used in this language?
	protected $m_useEnDefaultAliases = true;
	/// Default English aliases for namespaces (typically used in all languages)
	static protected $enNamespaceAliases = array(
		'Property'      => SMW_NS_PROPERTY,
		'Property_talk' => SMW_NS_PROPERTY_TALK,
		'Type'          => SMW_NS_TYPE,
		'Type_talk'     => SMW_NS_TYPE_TALK,
		'Concept'       => SMW_NS_CONCEPT,
		'Concept_talk'  => SMW_NS_CONCEPT_TALK
	);
	/// Default English aliases for namespaces (typically used in all languages)
	static protected $enDatatypeAliases = array(
		'URL'                   => '_uri',
		'Page'                  => '_wpg',
		'String'                => '_str',
		'Text'                  => '_txt',
		'Code'                  => '_cod',
		'Boolean'               => '_boo',
		'Number'                => '_num',
		'Geographic coordinates'=> '_geo',
		'Geographic coordinate' => '_geo', // deprecated, see Bug 30990
		'Temperature'           => '_tem',
		'Quantity'              => '_qty',
		'Date'                  => '_dat',
		'Email'                 => '_ema',
		'Annotation URI'        => '_anu',
		'Telephone number'      => '_tel',
		'Record'                => '_rec'
	);
	/// Default English aliases for special property names (typically used in all languages)
	static protected $enPropertyAliases = array(
		'Has type'          => '_TYPE',
		'Equivalent URI'    => '_URI',
		'Subproperty of'    => '_SUBP',
		'Subcategory of'    => '_SUBC',
		'Display units'     => '_UNIT',
		'Imported from'     => '_IMPO',
		'Corresponds to'    => '_CONV',
		'Provides service'  => '_SERV',
		'Allows value'      => '_PVAL',
		'Modification date' => '_MDAT',
		'Creation date'     => '_CDAT',
		'Has improper value for' => '_ERRP',
		'Has fields'        => '_LIST',
		'Has subobject'     => '_SOBJ',
	);

	public function __construct() {
		// `$this->m_SpecialProperties' is set in descendants.
		// Let us initialize reverse mapping.
		foreach ( $this->m_SpecialProperties as $propId => $propName ) {
			$this->m_SpecialPropertyIds[ $propName ] = $propId;
		}
	}


	/**
	 * Function that returns an array of namespace identifiers.
	 */
	function getNamespaces() {
		global $smwgHistoricTypeNamespace;
		$namespaces = $this->m_Namespaces;
		if ( !$smwgHistoricTypeNamespace ) {
			unset( $namespaces[SMW_NS_TYPE] );
			unset( $namespaces[SMW_NS_TYPE_TALK] );
		}
		return $namespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any.
	 */
	function getNamespaceAliases() {
		global $smwgHistoricTypeNamespace;

		$namespaceAliases = $this->m_NamespaceAliases;
		if ( $this->m_useEnDefaultAliases ) {
			$namespaceAliases = $namespaceAliases + SMWLanguage::$enNamespaceAliases;
		}

		if ( !$smwgHistoricTypeNamespace ) {
			foreach ($namespaceAliases as $alias => $namespace) {
				if ( $namespace == SMW_NS_TYPE || $namespace == SMW_NS_TYPE_TALK ) {
					unset( $namespaceAliases[$alias] );
				}
			}
		}

		return $namespaceAliases;
	}

	/**
	 * Return all labels that are available as names for built-in datatypes. Those
	 * are the types that users can access via [[has type::...]] (more built-in
	 * types may exist for internal purposes but the user won't need to
	 * know this). The returned array is indexed by (internal) type ids.
	 */
	function getDatatypeLabels() {
		return $this->m_DatatypeLabels;
	}

	/**
	 * Return an array that maps aliases to internal type ids. All ids used here
	 * should also have a primary label defined in m_DatatypeLabels.
	 */
	function getDatatypeAliases() {
		return $this->m_useEnDefaultAliases ?
		       $this->m_DatatypeAliases + SMWLanguage::$enDatatypeAliases :
		       $this->m_DatatypeAliases;
	}

	/**
	 * Function that returns the labels for predefined properties.
	 */
	function getPropertyLabels() {
		return $this->m_SpecialProperties;
	}

	/**
	 * Aliases for predefined properties, if any.
	 */
	function getPropertyAliases() {
		return $this->m_useEnDefaultAliases ?
		       $this->m_SpecialPropertyAliases + SMWLanguage::$enPropertyAliases :
		       $this->m_SpecialPropertyAliases;
	}

	/**
	 * Function receives property name (for example, `Modificatino date') and returns property id
	 * (for example, `_MDAT'). Property name may be localized one. If property name is not
	 * recognized, null value returned.
	 */
	function getPropertyId( $propName ) {
		if ( isset( $this->m_SpecialPropertyIds[$propName] ) ) {
			return $this->m_SpecialPropertyIds[$propName];
		};
		if ( isset( $this->m_SpecialPropertyAliases[$propName] ) ) {
			return $this->m_SpecialPropertyAliases[$propName];
		}
		if ( $this->m_useEnDefaultAliases && isset( SMWLanguage::$enPropertyAliases[$propName] ) ) {
			return SMWLanguage::$enPropertyAliases[$propName];
		} 
		return null;
	}

	/**
	 * Function that returns the preferred date formats
	 */
	function getDateFormats() {
		return $this->m_dateformats;
	}

	/**
	 * Function looks up a month and returns the corresponding number.
	 * @todo Should we add functionality to ignore case here?
	 * @todo Should there be prefix string matching instead of two arrays for full and short names?
	 */
	function findMonth( $label ) {
		$id = array_search( $label, $this->m_months );
		if ( $id !== false ) {
			return $id + 1;
		}
		$id = array_search( $label, $this->m_monthsshort );
		if ( $id !== false ) {
			return $id + 1;
		}
		return false;
	}

	/**
	 * Return the name of the month with the given number.
	 */
	function getMonthLabel( $number ) {
		return ( ( $number >= 1 ) && ( $number <= 12 ) ) ? $this->m_months[(int)( $number - 1 )] : '';
	}

}


