<?php

namespace SMW;

use SMWDataItem;

/**
 * Before a new set of data (type, constraints etc.) is stored about a property
 * the class tries to compare old and new specifications (values about that property)
 * and notifies a dispatcher about a change.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class PropertySpecificationChangeNotifier {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var array
	 */
	private $propertyList = array();

	/**
	 * @var boolean
	 */
	private $hasDiff = false;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function hasDiff() {
		return $this->hasDiff;
	}

	/**
	 * @since 2.5
	 */
	public function notify() {

		if ( !$this->hasDiff() || $this->semanticData === null ) {
			return false;
		}

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'subject', $this->semanticData->getSubject() );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'property.specification.change',
			$dispatchContext
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $propertyList
	 */
	public function setPropertyList( array $propertyList ) {
		$this->propertyList = $propertyList;
	}

	/**
	 * Compare and detect differences between the invoked semantic data
	 * and the current stored data
	 *
	 * @note Compare on extra properties from `smwgDeclarationProperties`
	 * (e.g '_PLIST') to find a possible specification change
	 *
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function detectChangesOn( SemanticData $semanticData ) {

		$this->semanticData = $semanticData;

		if ( $this->semanticData->getSubject()->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$this->doCompare( '_TYPE' );
		$this->doCompare( '_CONV' );
		$this->doCompare( '_UNIT' );
		$this->doCompare( '_PREC' );
		$this->doCompare( '_PDESC' );

		foreach ( $this->propertyList as $property ) {
			$this->doCompare( $property );
		}
	}

	private function doCompare( $propertyKey ) {

		if ( $this->hasDiff() ) {
			return;
		}

		$property = new DIProperty( $propertyKey );

		$newValues = $this->semanticData->getPropertyValues( $property );

		$oldValues = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$property
		);

		$this->setDiff( !$this->isEqual( $oldValues, $newValues ) );
	}

	private function setDiff( $addJob = true ) {
		if ( $addJob && !$this->hasDiff ) {
			$this->hasDiff = true;
		}
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 *
	 * @param SMWDataItem[] $oldDataValue
	 * @param SMWDataItem[] $newDataValue
	 *
	 * @return boolean
	 */
	private function isEqual( array $oldDataValue, array $newDataValue ) {

		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = array();
		foreach ( $oldDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$oldDataValueHash = implode( '___', $values );

		$values = array();
		foreach ( $newDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$newDataValueHash = implode( '___', $values );

		return $oldDataValueHash == $newDataValueHash;
	}

}
