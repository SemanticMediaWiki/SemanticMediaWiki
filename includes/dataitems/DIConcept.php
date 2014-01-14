<?php

namespace SMW;

use SMWDataItem;
use SMWDataItemException;

/**
 * This class implements Concept data items.
 *
 * @note These special data items for storing concept declaration data in SMW
 * should vanish at some point since Container values could encode this data
 * just as well.
 *
 * @since 1.6
 *
 * @ingroup SMWDataItems
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class DIConcept extends \SMWDataItem {

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
	 * Status
	 * @var integer
	 */
	protected $cacheStatus;

	/**
	 * Date
	 * @var integer
	 */
	protected $cacheDate;

	/**
	 * Count
	 * @var integer
	 */
	protected $cacheCount;

	/**
	 * @param string $concept the concept query string
	 * @param string $docu user documentation
	 * @param integer $queryefeatures flags about query features
	 * @param integer $size concept query size
	 * @param integer $depth concept query depth
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
	 * Sets cache status
	 *
	 * @since 1.9
	 *
	 * @param string
	 */
	public function setCacheStatus( $status ) {
		$this->cacheStatus = $status;
	}

	/**
	 * Sets cache date
	 *
	 * @since 1.9
	 *
	 * @param string
	 */
	public function setCacheDate( $date ) {
		$this->cacheDate = $date;
	}

	/**
	 * Sets cache count
	 *
	 * @since 1.9
	 *
	 * @param int
	 */
	public function setCacheCount( $count ) {
		$this->cacheCount = $count;
	}

	/**
	 * Returns cache status
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheStatus() {
		return $this->cacheStatus;
	}

	/**
	 * Returns cache date
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheDate() {
		return $this->cacheDate;
	}

	/**
	 * Returns cache count
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function getCacheCount() {
		return $this->cacheCount;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return DIConcept
	 */
	public static function doUnserialize( $serialization ) {
		$result = unserialize( $serialization );
		if ( $result === false ) {
			throw new DataItemException( "Unserialization failed." );
		}
		return $result;
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_CONCEPT ) {
			return false;
		}
		return $di->getSerialization() === $this->getSerialization();
	}

}
