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
	 * @var boolean
	 */
	private $hasDiff = false;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function __construct( Store $store, SemanticData $semanticData ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
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
	 * Compare and compute the difference between invoked semantic data
	 * and the current store data
	 *
	 * @note Compare on extra properties from `smwgDeclarationProperties`
	 * (e.g '_PLIST', see $ ) to find a possible specification change
	 *
	 * @since 1.9
	 *
	 * @param array $propertiesToCompare
	 */
	public function compareWith( array $propertiesToCompare ) {

		if ( $this->semanticData->getSubject()->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$this->compareWithKey( '_TYPE' );
		$this->compareWithKey( '_CONV' );
		$this->compareWithKey( '_UNIT' );
		$this->compareWithKey( '_PREC' );
		$this->compareWithKey( '_PDESC' );

		foreach ( $propertiesToCompare as $propertyKey ) {
			$this->compareWithKey( $propertyKey );
		}
	}

	private function compareWithKey( $propertyKey ) {

		if ( $this->hasDiff() ) {
			return;
		}

		$property = new DIProperty( $propertyKey );

		$newValues = $this->semanticData->getPropertyValues( $property );

		$oldValues = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$property
		);

		$this->notifyDispatcher( !$this->isEqual( $oldValues, $newValues ) );
	}

	private function notifyDispatcher( $addJob = true ) {
		if ( $addJob && !$this->hasDiff ) {

			$dispatchContext = EventHandler::getInstance()->newDispatchContext();
			$dispatchContext->set( 'subject', $this->semanticData->getSubject() );

			EventHandler::getInstance()->getEventDispatcher()->dispatch(
				'property.specification.change',
				$dispatchContext
			);

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
