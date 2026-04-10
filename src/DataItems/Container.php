<?php

namespace SMW\DataItems;

use SMW\DataModel\ContainerSemanticData;
use SMW\Exception\DataItemException;

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
 * SMWSemanticData object of class ContainerSemanticData. This class allows
 * the subject to be kept anonymous if not known (if no context page is
 * available for finding a suitable subobject name). See the repsective
 * documentation for details.
 *
 * Being a mere placeholder/template for other data, a Container is not
 * immutable as the other basic data items. New property-value pairs can always
 * be added to the internal ContainerSemanticData.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup DataItems
 */
class Container extends DataItem {

	/**
	 * Internal value.
	 *
	 * @var SMWSemanticData
	 */
	protected $m_semanticData;

	/**
	 * Constructor. The given ContainerSemanticData object will be owned
	 * by the constructed object afterwards, and in particular will not
	 * allow further changes.
	 *
	 * @param $semanticData ContainerSemanticData
	 */
	public function __construct( ContainerSemanticData $semanticData ) {
		$this->m_semanticData = $semanticData;
	}

	public function getDIType(): int {
		return DataItem::TYPE_CONTAINER;
	}

	public function getSemanticData(): ContainerSemanticData {
		return $this->m_semanticData;
	}

	public function getSortKey(): string {
		return '';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $sortKey
	 */
	public function setSortKey( string $sortKey ): void {
		$this->m_semanticData->addPropertyObjectValue(
			new Property( '_SKEY' ),
			new Blob( $this->m_semanticData->getSubject()->getSortKey() . '#' . $sortKey )
		);
	}

	public function getSerialization(): string {
		return serialize( $this->m_semanticData );
	}

	/**
	 * Get a hash string for this data item.
	 *
	 * @return string
	 */
	public function getHash(): string {
		$hash = $this->getValueHash( $this->m_semanticData );
		sort( $hash );

		return md5( implode( '#', $hash ) );

		// We want a value hash, not an entity hash!!
		// return $this->m_semanticData->getHash();
	}

	/**
	 * @return mixed[]
	 */
	private function getValueHash( $semanticData ): array {
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
	 * @return Container
	 */
	public static function doUnserialize( $serialization ): Container {
		/// TODO May issue an E_NOTICE when problems occur; catch this
		$data = unserialize( $serialization );
		if ( !( $data instanceof ContainerSemanticData ) ) {
			throw new DataItemException( "Could not unserialize Container from the given string." );
		}
		return new Container( $data );
	}

	public function equals( DataItem $di ): bool {
		if ( $di->getDIType() !== DataItem::TYPE_CONTAINER ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( Container::class, 'SMWDIContainer' );
