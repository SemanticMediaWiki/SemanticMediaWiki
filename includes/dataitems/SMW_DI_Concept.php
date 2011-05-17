<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This class implements Concept data items.
 * These special data items for storing concept declaration data in SMW may
 * well vanish at some point since Container values could encode this data
 * just as well.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIConcept extends SMWDataItem {

	/**
	 * Query string for this concept. Possibly long.
	 * @var string
	 */
	protected $m_concept;
	/**
	 * Documentation for this concept. Possibly long.
	 * @var string
	 */
	protected $m_docu;
	/**
	 * Flags of query features.
	 * @var integer
	 */
	protected $m_features;
	/**
	 * Size of the query.
	 * @var integer
	 */
	protected $m_size;
	/**
	 * Depth of the query.
	 * @var integer
	 */
	protected $m_depth;

	/**
	 * Initialise the concept data.
	 * @param $concept the concept query string
	 * @param $docu string with user documentation
	 * @param $queryefeatures integer flags about query features
	 * @param $size integer concept query size
	 * @param $depth integer concept query depth
	 */
	public function __construct( $concept, $docu, $queryfeatures, $size, $depth ) {
		$this->m_concept  = $concept;
		$this->m_docu     = $docu;
		$this->m_features = $queryfeatures;
		$this->m_size     = $size;
		$this->m_depth    = $depth;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_CONCEPT;
	}

	public function getConceptQuery() {
		return $this->m_concept;
	}

	public function getDocumentation() {
		return $this->m_docu;
	}

	public function getQueryFeatures() {
		return $this->m_features;
	}

	public function getSize() {
		return $this->m_size;
	}

	public function getDepth() {
		return $this->m_depth;
	}

	public function getSortKey() {
		return $this->m_docu;
	}

	public function getSerialization() {
		return serialize( $this );
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIConcept
	 */
	public static function doUnserialize( $serialization ) {
		$result = unserialize( $serialization );
		if ( $result === false ) {
			throw new SMWDataItemException( "Unserialization failed." );
		}
		return $result;
	}

}
