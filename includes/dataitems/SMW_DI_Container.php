<?php
/**
 * @ingroup SMWDataItems
 */

use SMW\DIProperty;
use SMW\Exception\DataItemException;
use SMWDIBlob as DIBlob;

/**
 * This class implements container data items that can store SMWSemanticData
 * objects. Containers are not dataitems in the proper sense: they do not
 * represent a single, opaque value that can be assigned to a property. Rather,
 * a container represents a "subobject" with a number of property-value
 * assignments. When a container is stored, these individual data assignments
 * are stored -- the data managed by SMW never contains any "container", just
 * individual property assignments for the subobject. Likewise, when a container
 * is used in search, it is interpreted as a patterns of possible property
 * assignments, and this pattern is searched for.
 *
 * The data encapsulated in a container data item is essentially an
 * SMWSemanticData object of class SMWContainerSemanticData. This class allows
 * the subject to be kept anonymous if not known (if no context page is
 * available for finding a suitable subobject name). See the repsective
 * documentation for details.
 *
 * Being a mere placeholder/template for other data, an SMWDIContainer is not
 * immutable as the other basic data items. New property-value pairs can always
 * be added to the internal SMWContainerSemanticData.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIContainer extends SMWDataItem {

	/**
	 * Internal value.
	 *
	 * @var SMWSemanticData
	 */
	protected $m_semanticData;

	/**
	 * Constructor. The given SMWContainerSemanticData object will be owned
	 * by the constructed object afterwards, and in particular will not
	 * allow further changes.
	 *
	 * @param $semanticData SMWContainerSemanticData
	 */
	public function __construct( SMWContainerSemanticData $semanticData ) {
		$this->m_semanticData = $semanticData;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_CONTAINER;
	}

	public function getSemanticData() {
		return $this->m_semanticData;
	}

	public function getSortKey() {
		return '';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $sortKey
	 */
	public function setSortKey( $sortKey ) {
		$this->m_semanticData->addPropertyObjectValue(
			new DIProperty( '_SKEY' ),
			new DIBlob( $this->m_semanticData->getSubject()->getSortKey() . '#' . $sortKey )
		);
	}

	public function getSerialization() {
		return serialize( $this->m_semanticData );
	}

	/**
	 * Get a hash string for this data item.
	 *
	 * @return string
	 */
	public function getHash() {

		$hash = $this->getValueHash( $this->m_semanticData );
		sort( $hash );

		return md5( implode( '#', $hash ) );

		// We want a value hash, not an entity hash!!
		// return $this->m_semanticData->getHash();
	}

	private function getValueHash( $semanticData ) {

		$hash = [];

		foreach ( $semanticData->getProperties() as $property ) {
			$hash[] = $property->getKey();

			foreach ( $semanticData->getPropertyValues( $property ) as $di ) {
				$hash[] = $di->getHash();
			}
		}

		foreach ( $semanticData->getSubSemanticData() as $data ) {
			$hash[] = $this->getValueHash( $data );
		}

		return $hash;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 *
	 * @return SMWDIContainer
	 */
	public static function doUnserialize( $serialization ) {
		/// TODO May issue an E_NOTICE when problems occur; catch this
		$data = unserialize( $serialization );
		if ( !( $data instanceof SMWContainerSemanticData ) ) {
			throw new DataItemException( "Could not unserialize SMWDIContainer from the given string." );
		}
		return new SMWDIContainer( $data );
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_CONTAINER ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}
}
