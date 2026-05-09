<?php

namespace SMW\DataItems;

use MediaWiki\Json\JsonDeserializer;
use SMW\Exception\DataItemException;

/**
 * This class implements Concept data items.
 *
 * @note These special data items for storing concept declaration data in SMW
 * should vanish at some point since Container values could encode this data
 * just as well.
 *
 * @since 1.6
 *
 * @ingroup DataItems
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class Concept extends DataItem {

	/**
	 * Status
	 * @var string
	 */
	protected $cacheStatus;

	/**
	 * Date
	 * @var string
	 */
	protected $cacheDate;

	/**
	 * Count
	 * @var int
	 */
	protected $cacheCount;

	public function __construct(
		protected $m_concept,
		protected $m_docu,
		protected $m_features,
		protected $m_size,
		protected $m_depth,
	) {
	}

	public function getDIType(): int {
		return DataItem::TYPE_CONCEPT;
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

	public function getSerialization(): string {
		return serialize( $this );
	}

	/**
	 * Sets cache status
	 *
	 * @since 1.9
	 *
	 * @param string $status
	 */
	public function setCacheStatus( $status ): void {
		$this->cacheStatus = $status;
	}

	/**
	 * Sets cache date
	 *
	 * @since 1.9
	 *
	 * @param string $date
	 */
	public function setCacheDate( $date ): void {
		$this->cacheDate = $date;
	}

	/**
	 * Sets cache count
	 *
	 * @since 1.9
	 *
	 * @param int $count
	 */
	public function setCacheCount( $count ): void {
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
	 */
	public static function doUnserialize( $serialization ): mixed {
		$result = unserialize( $serialization );
		if ( $result === false ) {
			throw new DataItemException( "Unserialization failed." );
		}
		return $result;
	}

	public function equals( DataItem $di ): bool {
		if ( $di->getDIType() !== DataItem::TYPE_CONCEPT ) {
			return false;
		}
		return $di->getSerialization() === $this->getSerialization();
	}

	/**
	 * Implements JsonSerializable.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		$json = parent::jsonSerialize();
		$json['cacheStatus'] = $this->cacheStatus;
		$json['cacheDate'] = $this->cacheDate;
		$json['cacheCount'] = $this->cacheCount;
		return $json;
	}

	/**
	 * Implements JsonDeserializable.
	 *
	 * @since 4.0.0
	 *
	 * @return static
	 */
	public static function newFromJsonArray( JsonDeserializer $deserializer, array $json ) {
		$obj = parent::newFromJsonArray( $deserializer, $json );
		$obj->cacheStatus = $json['cacheStatus'];
		$obj->cacheDate = $json['cacheDate'];
		$obj->cacheCount = $json['cacheCount'];
		return $obj;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( Concept::class, 'SMW\DIConcept' );
