<?php

namespace SMW\DataModel;

use MediaWiki\Json\JsonDeserializable;
use MediaWiki\Json\JsonDeserializer;
use SMW\DataItems\Container;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\Exception\SemanticDataImportException;
use SMW\Exception\SubSemanticDataException;
use SMW\HashBuilder;
use SMW\Localizer\Localizer;
use SMW\Options;
use SMW\ProcessingErrorMsgHandler;

/**
 * Class for representing chunks of semantic data for one given
 * subject. This consists of property-value pairs, grouped by property,
 * and possibly by SemanticData objects about subobjects.
 *
 * Data about subobjects can be added in two ways: by directly adding it
 * using addSubSemanticData() or by adding a property value of type
 * Container.
 *
 * By its very design, the container is unable to hold inverse properties.
 * For one thing, it would not be possible to identify them with mere keys.
 * Since SMW cannot annotate pages with inverses, this is not a limitation.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SemanticData implements JsonDeserializable {

	/**
	 * Returns the last modified timestamp the data were stored to the Store or
	 * have been fetched from cache.
	 */
	const OPT_LAST_MODIFIED = 'opt.last.modified';

	/**
	 * @see $smwgCheckForRemnantEntities
	 */
	const OPT_CHECK_REMNANT_ENTITIES = 'opt.check.remnant.entities';

	/**
	 * Identifies that a data block was created by a user.
	 */
	const PROC_USER = 'proc.user';

	/**
	 * Identifies that a data block was initiated by a delete request.
	 */
	const PROC_DELETE = 'proc.delete';

	/**
	 * Cache for the localized version of the namespace prefix "Property:".
	 *
	 * @var string
	 */
	protected static $mPropertyPrefix = '';

	/**
	 * States whether this is a stub object. Stubbing might happen on
	 * serialisation to save DB space.
	 *
	 * @todo Check why this is public and document this here. Or fix it.
	 *
	 * @var bool
	 */
	public $stubObject;

	/**
	 * Array mapping property keys (string) to arrays of DataItem
	 * objects.
	 *
	 * @var DataItem[][]
	 */
	protected $mPropVals = [];

	/**
	 * Array mapping property keys (string) to Property objects.
	 *
	 * @var Property[]
	 */
	protected $mProperties = [];

	/**
	 * States whether the container holds any normal properties.
	 *
	 * @var bool
	 */
	protected $mHasVisibleProps = false;

	/**
	 * States whether the container holds any displayable predefined
	 * $mProperties (as opposed to predefined properties without a display
	 * label). For some settings we need this to decide if a Factbox is
	 * displayed.
	 *
	 * @var bool
	 */
	protected $mHasVisibleSpecs = false;

	/**
	 * WikiPage object that is the subject of this container.
	 * Subjects can never be null (and this is ensured in all methods setting
	 * them in this class).
	 */
	protected WikiPage $mSubject;

	/**
	 * Semantic data associated to subobjects of the subject of this
	 * SemanticData.
	 * These key-value pairs of subObjectName (string) =>SemanticData.
	 *
	 * @since 1.8
	 */
	protected ?SubSemanticData $subSemanticData = null;

	/**
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Cache the hash to ensure a minimal impact in case of repeated usage. Any
	 * removal or insert action will reset the hash to null to ensure it is
	 * recreated in corresponds to changed nature of the data.
	 *
	 * @var string|null
	 */
	private $hash = null;

	/**
	 * @var Options
	 */
	protected $options;

	/**
	 * @var array
	 */
	protected $extensionData = [];

	/**
	 * @var array[]
	 */
	protected $sequenceMap = [];

	/**
	 * @var array of int|array
	 */
	protected $countMap = [];

	/**
	 * This is kept public to keep track of the depth during a recursive processing
	 * when accessed through the SubSemanticData instance.
	 *
	 * @var int
	 */
	public $subContainerDepthCounter = 0;

	/**
	 * Constructor.
	 */
	public function __construct(
		WikiPage $subject,
		protected $mNoDuplicates = true,
	) {
		$this->clear();
		$this->mSubject = $subject;
		$this->subSemanticData = new SubSemanticData( $subject, $this->mNoDuplicates );
	}

	/**
	 * This object is added to the parser output of MediaWiki, but it is
	 * not useful to have all its data as part of the parser cache since
	 * the data is already stored in more accessible format in SMW. Hence
	 * this implementation of __sleep() makes sure only the subject is
	 * serialised, yielding a minimal stub data container after
	 * unserialisation. This is a little safer than serialising nothing:
	 * if, for any reason, SMW should ever access an unserialised parser
	 * output, then the Semdata container will at least look as if properly
	 * initialised (though empty).
	 *
	 * @return array
	 */
	public function __sleep(): array {
		return [
			'mSubject', 'mPropVals', 'mProperties', 'subSemanticData', 'mHasVisibleProps', 'mHasVisibleSpecs', 'options', 'extensionData', 'sequenceMap', 'countMap'
		];
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function isStub(): bool {
		return false;
	}

	/**
	 * Return subject to which the stored semantic annotations refer to.
	 *
	 * @return WikiPage subject
	 */
	public function getSubject(): WikiPage {
		return $this->mSubject;
	}

	/**
	 * Returns a hashed value map
	 *
	 * @since 3.1
	 *
	 * @return array[]
	 */
	public function getSequenceMap(): array {
		return $this->sequenceMap;
	}

	/**
	 * Returns a map of property value counts
	 *
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getCountMap(): array {
		return $this->countMap;
	}

	/**
	 * Get the array of all properties that have stored values.
	 *
	 * @return Property[]
	 */
	public function getProperties(): array {
		ksort( $this->mProperties, SORT_STRING );
		return $this->mProperties;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function hasProperty( Property $property ): bool {
		return isset( $this->mProperties[$property->getKey()] ) || array_key_exists( $property->getKey(), $this->mProperties );
	}

	/**
	 * Get the array of all stored values for some property.
	 *
	 * @param Property $property
	 * @return DataItem[]
	 */
	public function getPropertyValues( Property $property ): array {
		if ( $property->isInverse() ) { // we never have any data for inverses
			return [];
		}

		if ( array_key_exists( $property->getKey(), $this->mPropVals ) ) {
			return array_values( $this->mPropVals[$property->getKey()] );
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setExtensionData( $key, $value ): void {
		$this->extensionData[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function getExtensionData( $key ) {
		if ( !isset( $this->extensionData[$key] ) ) {
			return null;
		}

		return $this->extensionData[$key];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {
		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		if ( $this->options->has( $key ) ) {
			return $this->options->get( $key );
		}

		return $default;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setOption( $key, $value ): void {
		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Adds an error array
	 *
	 * @since  1.9
	 */
	public function addError( $error ): void {
		$this->errors = array_merge( $this->errors, (array)$error );
	}

	/**
	 * Generate a hash value to simplify the comparison of this data
	 * container with other containers. Subdata is taken into account.
	 *
	 * The hash uses PHP's md5 implementation, which is among the fastest
	 * hash algorithms that PHP offers.
	 *
	 * @note This function may be used to obtain keys for SemanticData
	 * objects or to do simple equality tests. Equal hashes with very
	 * high probability indicate equal data.
	 *
	 * @return string
	 */
	public function getHash(): string {
		if ( $this->hash !== null ) {
			return $this->hash;
		}

		$this->hash = HashBuilder::createFromSemanticData( $this );
		return $this->hash;
	}

	/**
	 * @see SubSemanticData::getSubSemanticData
	 *
	 * @since 1.8
	 *
	 * @return SemanticData[]
	 */
	public function getSubSemanticData(): array {
		// Remove the check in 3.0
		$subSemanticData = $this->subSemanticData;

		// Avoids an issue where the serialized array from a previous usage is
		// returned from a __wakeup, where now a SubSemanticData (#2177) is expected.
		if ( !$subSemanticData instanceof SubSemanticData ) {
			$this->subSemanticData = new SubSemanticData( $this->mSubject, $this->mNoDuplicates );
			$this->subSemanticData->copyDataFrom( $subSemanticData );
		}

		return $this->subSemanticData->getSubSemanticData();
	}

	/**
	 * @since 2.5
	 */
	public function clearSubSemanticData(): void {
		if ( $this->subContainerDepthCounter > 0 ) {
			$this->subContainerDepthCounter--;
		}

		if ( $this->subSemanticData !== null ) {
			$this->subSemanticData->clear();
		}
	}

	/**
	 * Return true if there are any visible properties.
	 *
	 * @note While called "visible" this check actually refers to the
	 * function Property::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return bool
	 */
	public function hasVisibleProperties(): bool {
		return $this->mHasVisibleProps;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 *
	 * @note While called "visible" this check actually refers to the
	 * function Property::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return bool
	 */
	public function hasVisibleSpecialProperties(): bool {
		return $this->mHasVisibleSpecs;
	}

	/**
	 * Store a value for a property identified by its DataItem object.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param Property $property
	 * @param DataItem $dataItem
	 */
	public function addPropertyObjectValue( Property $property, DataItem $dataItem ): void {
		$this->hash = null;

		if ( $dataItem instanceof Container ) {
			$this->addSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $property->getKey() === Property::TYPE_MODIFICATION_DATE ) {
			$this->setOption( self::OPT_LAST_MODIFIED, $dataItem->getMwTimestamp() );
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		$key = $property->getKey();

		if ( !isset( $this->countMap[$key] ) ) {
			$this->countMap[$key] = $key === '_INST' ? [] : 0;
		}

		if ( !array_key_exists( $property->getKey(), $this->mPropVals ) ) {
			$this->mPropVals[$property->getKey()] = [];
			$this->mProperties[$property->getKey()] = $property;

			if ( SequenceMap::canMap( $property ) ) {
				$this->sequenceMap[$property->getKey()] = [];
			}
		}

		$hash = md5( $dataItem->getHash() );

		// Only store a map for values that are allowed to
		if (
			$this->mNoDuplicates &&
			isset( $this->sequenceMap[$property->getKey()] ) &&
			!isset( $this->mPropVals[$property->getKey()][$hash] ) ) {
			$this->sequenceMap[$property->getKey()][] = $hash;
		}

		if ( !isset( $this->mPropVals[$property->getKey()][$hash] ) ) {

			// Count categories differently
			if ( $key === '_INST' ) {
				$this->countMap[$key][$dataItem->getDBKey()] = 1;
			} else {
				$this->countMap[$key]++;
			}
		}

		if ( $this->mNoDuplicates ) {
			$this->mPropVals[$property->getKey()][$hash] = $dataItem;
		} else {
			$this->mPropVals[$property->getKey()][] = $dataItem;
		}

		if ( !$property->isUserDefined() ) {
			if ( $property->isShown() ) {
				$this->mHasVisibleSpecs = true;
				$this->mHasVisibleProps = true;
			}
		} else {
			$this->mHasVisibleProps = true;
		}

		// Account for things like DISPLAYTITLE or DEFAULTSORT which are only set
		// after #subobject has been processed therefore keep them in-memory
		// for a post process
		if ( $this->mSubject->getSubobjectName() === '' && $property->getKey() === Property::TYPE_SORTKEY ) {
			foreach ( $this->getSubSemanticData() as $subSemanticData ) {
				$subSemanticData->setExtensionData( 'sort.extension', $dataItem->getString() );
			}
		}
	}

	/**
	 * Store a value for a given property identified by its text label
	 * (without namespace prefix).
	 *
	 * @param $propertyName string
	 * @param $dataItem DataItem
	 */
	public function addPropertyValue( string $propertyName, DataItem $dataItem ): void {
		$propertyKey = smwfNormalTitleDBKey( $propertyName );

		if ( array_key_exists( $propertyKey, $this->mProperties ) ) {
			$property = $this->mProperties[$propertyKey];
		} else {
			if ( self::$mPropertyPrefix === '' ) {
				self::$mPropertyPrefix = Localizer::getInstance()->getNsText( SMW_NS_PROPERTY ) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]

			$propertyDV = DataValueFactory::getInstance()->newPropertyValueByLabel( self::$mPropertyPrefix . $propertyName );

			if ( !$propertyDV->isValid() ) { // error, maybe illegal title text
				return;
			}

			$property = $propertyDV->getDataItem();
		}

		$this->addPropertyObjectValue( $property, $dataItem );
	}

	/**
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 */
	public function addDataValue( DataValue $dataValue ): void {
		if ( !$dataValue->getProperty() instanceof Property || !$dataValue->isValid() ) {

			$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
				$this->getSubject()
			);

			$processingErrorMsgHandler->addToSemanticData(
				$this,
				$processingErrorMsgHandler->newErrorContainerFromDataValue( $dataValue )
			);

			$this->addError( $dataValue->getErrors() );
			return;
		}

		$this->addPropertyObjectValue(
			$dataValue->getProperty(),
			$dataValue->getDataItem()
		);
	}

	/**
	 * @since 2.1
	 *
	 * @param Subobject $subobject
	 */
	public function addSubobject( Subobject $subobject ): void {
		$this->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);
	}

	/**
	 * Remove a value for a property identified by its DataItem object.
	 * This method removes a property-value specified by the property and
	 * dataitem. If there are no more property-values for this property it
	 * also removes the property from the mProperties.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param Property $property
	 * @param DataItem $dataItem
	 *
	 * @since 1.8
	 */
	public function removePropertyObjectValue( Property $property, DataItem $dataItem ): void {
		$this->hash = null;

		// delete associated subSemanticData
		if ( $dataItem instanceof Container ) {
			$this->removeSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		$key = $property->getKey();

		if (
			!array_key_exists( $property->getKey(), $this->mPropVals ) ||
			!array_key_exists( $property->getKey(), $this->mProperties ) ) {
			return;
		}

		if ( $this->mNoDuplicates ) {
			// this didn't get checked for my tests, but should work
			unset( $this->mPropVals[$property->getKey()][md5( $dataItem->getHash() )] );

			if ( isset( $this->countMap[$key] ) && $key === '_INST' ) {
				unset( $this->countMap[$key][$dataItem->getDBKey()] );
			} elseif ( isset( $this->countMap[$key] ) ) {
				$this->countMap[$key]--;
			}
		} else {
			foreach ( $this->mPropVals[$property->getKey()] as $index => $di ) {
				if ( $di->equals( $dataItem ) ) {
					unset( $this->mPropVals[$property->getKey()][$index] );
				}

				if ( isset( $this->countMap[$key] ) && $key === '_INST' ) {
					unset( $this->countMap[$key][$dataItem->getDBKey()] );
				} elseif ( isset( $this->countMap[$key] ) ) {
					$this->countMap[$key]--;
				}
			}

			$this->mPropVals[$property->getKey()] = array_values( $this->mPropVals[$property->getKey()] );
		}

		if ( isset( $this->countMap[$key] ) && $this->countMap[$key] == 0 ) {
			unset( $this->countMap[$key] );
		}

		if ( $this->mPropVals[$property->getKey()] === [] ) {
			unset( $this->mProperties[$property->getKey()] );
			unset( $this->mPropVals[$property->getKey()] );
		}
	}

	/**
	 * Removes a property and all the values associated with this property.
	 *
	 * @since 2.5
	 *
	 * @param Property $property
	 */
	public function removeProperty( Property $property ): void {
		$this->hash = null;
		$key = $property->getKey();

		 // Inverse properties cannot be used for an annotation
		if ( $property->isInverse() ) {
			return;
		}

		if ( !isset( $this->mProperties[$key] ) || !isset( $this->mPropVals[$key] ) ) {
			return;
		}

		// Find and remove associated assignments (e.g. _ASK as subobject
		// contains _ASKSI ...)
		foreach ( $this->mPropVals[$key] as $dataItem ) {
			$this->removePropertyObjectValue( $property, $dataItem );
		}

		unset( $this->mPropVals[$key] );
		unset( $this->mProperties[$key] );
		unset( $this->sequenceMap[$key] );
		unset( $this->countMap[$key] );
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear(): void {
		$this->mPropVals = [];
		$this->mProperties = [];
		$this->mHasVisibleProps = false;
		$this->mHasVisibleSpecs = false;
		$this->stubObject = false;
		$this->clearSubSemanticData();
		$this->hash = null;
		$this->options = null;
	}

	/**
	 * Return true if this SemanticData is empty.
	 * This is the case when the subject has neither property values nor
	 * data for subobjects.
	 *
	 * @since 1.8
	 */
	public function isEmpty(): bool {
		return $this->getProperties() === [] && $this->getSubSemanticData() === [];
	}

	/**
	 * Add all data from the given SemanticData.
	 * Only works if the imported SemanticData has the same subject as
	 * this SemanticData; an exception is thrown otherwise.
	 *
	 * @since 1.7
	 *
	 * @param SemanticData $semanticData object to copy from
	 *
	 * @throws SemanticDataImportException
	 */
	public function importDataFrom( SemanticData $semanticData ): void {
		if ( !$this->mSubject->equals( $semanticData->getSubject() ) ) {
			throw new SemanticDataImportException( "SemanticData can only represent data about one subject. Importing data for another subject is not possible." );
		}

		$this->hash = null;

		// Shortcut when copying into empty objects that don't ask for
		// more duplicate elimination:
		if ( count( $this->mProperties ) == 0 &&
			 ( $semanticData->mNoDuplicates >= $this->mNoDuplicates ) ) {

			$this->mProperties = $semanticData->getProperties();
			$this->sequenceMap = $semanticData->getSequenceMap();
			$this->countMap = $semanticData->getCountMap();
			$this->mPropVals = [];

			foreach ( $this->mProperties as $property ) {
				$key = $property->getKey();
				$this->mPropVals[$key] = $semanticData->getPropertyValues( $property );

				if ( isset( $this->sequenceMap[$key] ) && SequenceMap::canMap( $property ) ) {
					$sequenceMap = array_flip( $this->sequenceMap[$key] );

					usort( $this->mPropVals[$key], static function ( $a, $b ) use( $sequenceMap ): int {
						$pos_a = $sequenceMap[md5( $a->getHash() )];
						$pos_b = $sequenceMap[md5( $b->getHash() )];

						return ( $pos_a < $pos_b ) ? -1 : 1;
					} );
				}
			}

			$this->mHasVisibleProps = $semanticData->hasVisibleProperties();
			$this->mHasVisibleSpecs = $semanticData->hasVisibleSpecialProperties();
		} else {
			foreach ( $semanticData->getProperties() as $property ) {
				$values = $semanticData->getPropertyValues( $property );

				foreach ( $values as $dataItem ) {
					$this->addPropertyObjectValue( $property, $dataItem );
				}
			}
		}

		// Postpone the import and avoid resolving `SubSemanticData` for
		// a `StubSemanticData` objects that would otherwise create the entire
		// object graph recursively even though it might not be necessary as in
		// case when retrieving data via `Special:Browse`
		//
		// Subobject references are part of the value representation and assigned
		// to the relevant property which may be resolved at a later point
		if ( !$semanticData->isStub() ) {
			foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
				$this->addSubSemanticData( $subSemanticData );
			}
		}
	}

	/**
	 * Removes data from the given SemanticData.
	 * If the subject of the data that is to be removed is not equal to the
	 * subject of this SemanticData, it will just be ignored (nothing to
	 * remove). Likewise, removing data that is not present does not change
	 * anything.
	 *
	 * @since 1.8
	 *
	 * @param SemanticData $semanticData
	 */
	public function removeDataFrom( SemanticData $semanticData ): void {
		if ( !$this->mSubject->equals( $semanticData->getSubject() ) ) {
			return;
		}

		foreach ( $semanticData->getProperties() as $property ) {
			$this->removeProperty( $property );
		}

		foreach ( $semanticData->getSubSemanticData() as $semData ) {
			$this->removeSubSemanticData( $semData );
		}
	}

	/**
	 * @see SubSemanticData::hasSubSemanticData
	 * @since 1.9
	 *
	 * @param string|null $subobjectName
	 *
	 * @return bool
	 */
	public function hasSubSemanticData( $subobjectName = null ) {
		return $this->subSemanticData->hasSubSemanticData( $subobjectName );
	}

	/**
	 * @see SubSemanticData::findSubSemanticData
	 * @since 1.9
	 *
	 * @param string $subobjectName
	 *
	 * @return ContainerSemanticData|null
	 */
	public function findSubSemanticData( $subobjectName ) {
		return $this->subSemanticData->findSubSemanticData( $subobjectName );
	}

	/**
	 * @see SubSemanticData::addSubSemanticData
	 * @since 1.8
	 *
	 * @param SemanticData $semanticData
	 * @throws SubSemanticDataException
	 */
	public function addSubSemanticData( SemanticData $semanticData ): void {
		$this->hash = null;
		$this->subSemanticData->addSubSemanticData( $semanticData );
	}

	/**
	 * @see SubSemanticData::removeSubSemanticData
	 * @since 1.8
	 *
	 * @param SemanticData $semanticData
	 */
	public function removeSubSemanticData( SemanticData $semanticData ): void {
		$this->hash = null;
		$this->subSemanticData->removeSubSemanticData( $semanticData );
	}

	/**
	 * Implements JsonSerializable.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		# T312589 explicitly calling jsonSerialize() will be unnecessary
		# in the future.
		$json = [
			'stubObject' => $this->stubObject,
			'mPropVals' => array_map( static function ( array $x ): array {
					return array_map( static function ( DataItem $y ): array {
						return $y->jsonSerialize();
					}, $x );
			}, $this->mPropVals ),
			'mProperties' => array_map( static function ( Property $x ): array {
					return $x->jsonSerialize();
			}, $this->mProperties ),
			'mHasVisibleProps' => $this->mHasVisibleProps,
			'mHasVisibleSpecs' => $this->mHasVisibleSpecs,
			'mNoDuplicates' => $this->mNoDuplicates,
			'mSubject' => $this->mSubject ? $this->mSubject->jsonSerialize() : null,
			'subSemanticData' => $this->subSemanticData ? $this->subSemanticData->jsonSerialize() : null,
			'errors' => $this->errors,
			'hash' => $this->hash,
			'options' => $this->options ? $this->options->jsonSerialize() : null,
			'extensionData' => $this->extensionData,
			'sequenceMap' => $this->sequenceMap,
			'countMap' => $this->countMap,
			'_type_' => get_class( $this ),
		];
		return $json;
	}

	/**
	 * Implements JsonDeserializable.
	 *
	 * @since 4.0.0
	 *
	 * @param JsonDeserializer $deserializer
	 * @param array $json JSON to be deserialized
	 *
	 * @return self
	 */
	public static function newFromJsonArray( JsonDeserializer $deserializer, array $json ): self {
		$obj = new self(
			$deserializer->deserialize( $json['mSubject'] ),
			$json['mNoDuplicates']
		);
		$obj->stubObject = $json['stubObject'];
		$obj->mPropVals = array_map( static function ( array $x ) use( $deserializer ): array {
			return $deserializer->deserializeArray( $x );
		}, $json['mPropVals'] );
		$obj->mProperties = $deserializer->deserializeArray( $json['mProperties'] );
		$obj->mHasVisibleProps = $json['mHasVisibleProps'];
		$obj->mHasVisibleSpecs = $json['mHasVisibleSpecs'];
		$obj->subSemanticData = $json['subSemanticData'] ? $deserializer->deserialize( $json['subSemanticData'] ) : null;
		$obj->errors = $json['errors'];
		$obj->hash = $json['hash'];
		$obj->options = $json['options'] ? $deserializer->deserialize( $json['options'] ) : null;
		$obj->extensionData = $json['extensionData'];
		$obj->sequenceMap = $json['sequenceMap'];
		$obj->countMap = $json['countMap'];
		return $obj;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( SemanticData::class, 'SMW\SemanticData' );
