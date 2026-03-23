<?php

namespace SMW\Property;

use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\SerializerFactory;
use SMW\Store;

/**
 * Before a new set of data (type, constraints etc.) is stored about a property
 * the class tries to compare old and new specifications (values about that property)
 * and notifies a dispatcher about a change.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ChangePropagationNotifier {

	/**
	 * @var array
	 */
	private $propertyList = [];

	/**
	 * @var bool
	 */
	private $hasDiff = false;

	/**
	 * @var bool
	 */
	private $isTypePropagation = false;

	/**
	 * @var bool
	 */
	private $isCommandLineMode = false;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private readonly Store $store,
		private readonly SerializerFactory $serializerFactory,
	) {
	}

	/**
	 * @since 2.5
	 *
	 * @param array $propertyList
	 */
	public function setPropertyList( array $propertyList ): void {
		$this->propertyList = $propertyList;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode.
	 *
	 * @since 3.0
	 *
	 * @param bool $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ): void {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function hasDiff() {
		return $this->hasDiff;
	}

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 */
	public function notify( WikiPage $subject ) {
		if ( !$this->hasDiff() || !$this->inNamespace( $subject ) ) {
			return false;
		}

		$params = [];

		if ( $this->isTypePropagation ) {
			$params['isTypePropagation'] = true;
		}

		return ChangePropagationDispatchJob::planAsJob( $subject, $params );
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function inNamespace( WikiPage $subject ): bool {
		return $subject->getNamespace() === SMW_NS_PROPERTY || $subject->getNamespace() === NS_CATEGORY;
	}

	/**
	 * Compare and detect differences between the invoked semantic data
	 * and the current stored data
	 *
	 * @note Compare on extra properties from `smwgChangePropagationWatchlist`
	 * (e.g '_PLIST') to find a possible specification change
	 *
	 * @since 1.9
	 */
	public function checkAndNotify( SemanticData &$semanticData ): void {
		if ( !$this->inNamespace( $semanticData->getSubject() ) ) {
			return;
		}

		$this->hasDiff = false;

		// Check the type first
		$propertyList = array_merge(
			[
				'_TYPE',
				'_CONV',
				'_UNIT',
				'_REDI'
			],
			$this->propertyList
		);

		foreach ( $propertyList as $key ) {

			// No need to keep comparing once a diff has been
			// detected
			if ( $this->hasDiff() ) {
				break;
			}

			$this->doCompare( $semanticData, $key );
		}

		$this->doNotifyAndPostpone( $semanticData );
	}

	private function doCompare( SemanticData $semanticData, $key ): void {
		$property = new Property( $key );

		$newValues = $semanticData->getPropertyValues( $property );

		$oldValues = $this->store->getPropertyValues(
			$semanticData->getSubject(),
			$property
		);

		$this->setDiff( !$this->isEqual( $oldValues, $newValues ), $key );
	}

	private function setDiff( bool $hasDiff, $key ): void {
		if ( !$hasDiff || $this->hasDiff ) {
			return;
		}

		$this->hasDiff = true;
		$this->isTypePropagation = $key === '_TYPE';
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 *
	 * @param DataItem[] $oldDataValue
	 * @param DataItem[] $newDataValue
	 *
	 * @return bool
	 */
	private function isEqual( array $oldDataValue, array $newDataValue ): bool {
		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = [];
		foreach ( $oldDataValue as $v ) {
			$values[] = htmlspecialchars_decode( $v->getHash() );
		}

		sort( $values );
		$oldDataValueHash = implode( '___', $values );

		$values = [];
		foreach ( $newDataValue as $v ) {
			$values[] = htmlspecialchars_decode( $v->getHash() );
		}

		sort( $values );
		$newDataValueHash = implode( '___', $values );

		return $oldDataValueHash == $newDataValueHash;
	}

	private function doNotifyAndPostpone( SemanticData &$semanticData ): void {
		if ( !$this->hasDiff() ) {
			return;
		}

		$this->notify( $semanticData->getSubject() );

		// If executed from the commandLine (cronJob etc.), do not
		// suspend the update
		if ( $this->isCommandLineMode === true ) {
			return;
		}

		$previous = $this->store->getSemanticData(
			$semanticData->getSubject()
		);

		$semanticDataSerializer = $this->serializerFactory->newSemanticDataSerializer();

		$new = $semanticDataSerializer->serialize(
			$semanticData
		);

		// Encode and store the new version of the SemanticData and suspend
		// the update until ChangePropagationDispatchJob was able to select
		// all connected entities
		$previous->addPropertyObjectValue(
			new Property( Property::TYPE_CHANGE_PROP ),
			new Blob( json_encode( $new ) )
		);

		$semanticData = $previous;
	}

}
