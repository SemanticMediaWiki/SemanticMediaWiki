<?php

namespace SMW;

use MWException;
use SMW\DataModel\SubSemanticData;
use SMW\DataModel\SequenceMap;
use SMW\Exception\SemanticDataImportException;
use SMWContainerSemanticData;
use SMWDataItem;
use SMWDataValue;
use SMWDIContainer;

/**
 * Class for representing chunks of semantic data for one given
 * subject. This consists of property-value pairs, grouped by property,
 * and possibly by SMWSemanticData objects about subobjects.
 *
 * Data about subobjects can be added in two ways: by directly adding it
 * using addSubSemanticData() or by adding a property value of type
 * SMWDIContainer.
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
class SemanticData {

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
	static protected $mPropertyPrefix = '';

	/**
	 * States whether this is a stub object. Stubbing might happen on
	 * serialisation to save DB space.
	 *
	 * @todo Check why this is public and document this here. Or fix it.
	 *
	 * @var boolean
	 */
	public $stubObject;

	/**
	 * Array mapping property keys (string) to arrays of SMWDataItem
	 * objects.
	 *
	 * @var SMWDataItem[]
	 */
	protected $mPropVals = [];

	/**
	 * Array mapping property keys (string) to DIProperty objects.
	 *
	 * @var DIProperty[]
	 */
	protected $mProperties = [];

	/**
	 * States whether the container holds any normal properties.
	 *
	 * @var boolean
	 */
	protected $mHasVisibleProps = false;

	/**
	 * States whether the container holds any displayable predefined
	 * $mProperties (as opposed to predefined properties without a display
	 * label). For some settings we need this to decide if a Factbox is
	 * displayed.
	 *
	 * @var boolean
	 */
	protected $mHasVisibleSpecs = false;

	/**
	 * States whether repeated values should be avoided. Not needing
	 * duplicate elimination (e.g. when loading from store) can save some
	 * time, especially in subclasses like SMWSqlStubSemanticData, where
	 * the first access to a data item is more costy.
	 *
	 * @note This setting is merely for optimization. The SMW data model
	 * never cares about the multiplicity of identical data assignments.
	 *
	 * @var boolean
	 */
	protected $mNoDuplicates;

	/**
	 * DIWikiPage object that is the subject of this container.
	 * Subjects can never be null (and this is ensured in all methods setting
	 * them in this class).
	 *
	 * @var DIWikiPage
	 */
	protected $mSubject;

	/**
	 * Semantic data associated to subobjects of the subject of this
	 * SMWSemanticData.
	 * These key-value pairs of subObjectName (string) =>SMWSemanticData.
	 *
	 * @since 1.8
	 * @var SubSemanticData
	 */
	protected $subSemanticData;

	/**
	 * Internal flag that indicates if this semantic data will accept
	 * subdata. Semantic data objects that are subdata already do not allow
	 * (second level) subdata to be added. This ensures that all data is
	 * collected on the top level, and in particular that there is only one
	 * way to represent the same data with subdata. This is also useful for
	 * diff computation.
	 */
	protected $subDataAllowed = true;

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
	 * @var array
	 */
	protected $sequenceMap = [];

	/**
	 * This is kept public to keep track of the depth during a recursive processing
	 * when accessed through the SubSemanticData instance.
	 *
	 * @var integer
	 */
	public $subContainerDepthCounter = 0;

	/**
	 * Constructor.
	 *
	 * @param DIWikiPage $subject to which this data refers
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( DIWikiPage $subject, $noDuplicates = true ) {
		$this->clear();
		$this->mSubject = $subject;
		$this->mNoDuplicates = $noDuplicates;
		$this->subSemanticData = new SubSemanticData( $subject, $noDuplicates );
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
	public function __sleep() {
		return [ 'mSubject', 'mPropVals', 'mProperties', 'subSemanticData', 'mHasVisibleProps', 'mHasVisibleSpecs', 'options', 'extensionData', 'sequenceMap' ];
	}

	/**
	 * Return subject to which the stored semantic annotations refer to.
	 *
	 * @return DIWikiPage subject
	 */
	public function getSubject() {
		return $this->mSubject;
	}

	/**
	 * Returns a hashed value map
	 *
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getSequenceMap() {
		return $this->sequenceMap;
	}

	/**
	 * Get the array of all properties that have stored values.
	 *
	 * @return array of DIProperty objects
	 */
	public function getProperties() {
		ksort( $this->mProperties, SORT_STRING );
		return $this->mProperties;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function hasProperty( DIProperty $property ) {
		return isset( $this->mProperties[$property->getKey()] ) || array_key_exists( $property->getKey(), $this->mProperties );
	}

	/**
	 * Get the array of all stored values for some property.
	 *
	 * @param DIProperty $property
	 * @return SMWDataItem[]
	 */
	public function getPropertyValues( DIProperty $property ) {
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
	public function setExtensionData( $key, $value ) {
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
	public function setOption( $key, $value ) {

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
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds an error array
	 *
	 * @since  1.9
	 *
	 * @return array|string
	 */
	public function addError( $error ) {
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
	public function getHash() {

		if ( $this->hash !== null ) {
			return $this->hash;
		}

		return $this->hash = Hash::createFromSemanticData( $this );
	}

	/**
	 * @see SubSemanticData::getSubSemanticData
	 *
	 * @since 1.8
	 *
	 * @return ContainerSemanticData[]
	 */
	public function getSubSemanticData() {

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
	public function clearSubSemanticData() {

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
	 * function DIProperty::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return boolean
	 */
	public function hasVisibleProperties() {
		return $this->mHasVisibleProps;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 *
	 * @note While called "visible" this check actually refers to the
	 * function DIProperty::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return boolean
	 */
	public function hasVisibleSpecialProperties() {
		return $this->mHasVisibleSpecs;
	}

	/**
	 * Store a value for a property identified by its SMWDataItem object.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param $property DIProperty
	 * @param $dataItem SMWDataItem
	 */
	public function addPropertyObjectValue( DIProperty $property, SMWDataItem $dataItem ) {

		$this->hash = null;

		if( $dataItem instanceof SMWDIContainer ) {
			$this->addSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if( $property->getKey() === DIProperty::TYPE_MODIFICATION_DATE ) {
			$this->setOption( self::OPT_LAST_MODIFIED, $dataItem->getMwTimestamp() );
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
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
		if ( $this->mSubject->getSubobjectName() === '' && $property->getKey() === DIProperty::TYPE_SORTKEY ) {
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
	 * @param $dataItem SMWDataItem
	 */
	public function addPropertyValue( $propertyName, SMWDataItem $dataItem ) {
		$propertyKey = smwfNormalTitleDBKey( $propertyName );

		if ( array_key_exists( $propertyKey, $this->mProperties ) ) {
			$property = $this->mProperties[$propertyKey];
		} else {
			if ( self::$mPropertyPrefix === '' ) {
				global $wgContLang;
				self::$mPropertyPrefix = $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':';
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
	 * @param SMWDataValue $dataValue
	 */
	public function addDataValue( SMWDataValue $dataValue ) {

		if ( !$dataValue->getProperty() instanceof DIProperty || !$dataValue->isValid() ) {

			$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
				$this->getSubject()
			);

			$processingErrorMsgHandler->addToSemanticData(
				$this,
				$processingErrorMsgHandler->newErrorContainerFromDataValue( $dataValue )
			);

			return $this->addError( $dataValue->getErrors() );
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
	public function addSubobject( Subobject $subobject ) {
		$this->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);
	}

	/**
	 * Remove a value for a property identified by its SMWDataItem object.
	 * This method removes a property-value specified by the property and
	 * dataitem. If there are no more property-values for this property it
	 * also removes the property from the mProperties.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param $property DIProperty
	 * @param $dataItem SMWDataItem
	 *
	 * @since 1.8
	 */
	public function removePropertyObjectValue( DIProperty $property, SMWDataItem $dataItem ) {

		$this->hash = null;

		//delete associated subSemanticData
		if( $dataItem instanceof SMWDIContainer ) {
			$this->removeSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		if (
			!array_key_exists( $property->getKey(), $this->mPropVals ) ||
			!array_key_exists( $property->getKey(), $this->mProperties ) ) {
			return;
		}

		if ( $this->mNoDuplicates ) {
			//this didn't get checked for my tests, but should work
			unset( $this->mPropVals[$property->getKey()][md5( $dataItem->getHash() )] );
		} else {
			foreach( $this->mPropVals[$property->getKey()] as $index => $di ) {
				if( $di->equals( $dataItem ) ) {
					unset( $this->mPropVals[$property->getKey()][$index] );
				}
			}
			$this->mPropVals[$property->getKey()] = array_values( $this->mPropVals[$property->getKey()] );
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
	 * @param $property DIProperty
	 */
	public function removeProperty( DIProperty $property ) {

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
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
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
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->getProperties() === [] && $this->getSubSemanticData() === [];
	}

	/**
	 * Add all data from the given SMWSemanticData.
	 * Only works if the imported SMWSemanticData has the same subject as
	 * this SMWSemanticData; an exception is thrown otherwise.
	 *
	 * @since 1.7
	 *
	 * @param SemanticData $semanticData object to copy from
	 *
	 * @throws SemanticDataImportException
	 */
	public function importDataFrom( SemanticData $semanticData ) {

		if( !$this->mSubject->equals( $semanticData->getSubject() ) ) {
			throw new SemanticDataImportException( "SemanticData can only represent data about one subject. Importing data for another subject is not possible." );
		}

		$this->hash = null;

		// Shortcut when copying into empty objects that don't ask for
		// more duplicate elimination:
		if ( count( $this->mProperties ) == 0 &&
		     ( $semanticData->mNoDuplicates >= $this->mNoDuplicates ) ) {

			$this->mProperties = $semanticData->getProperties();
			$this->sequenceMap = $semanticData->getSequenceMap();
			$this->mPropVals = [];

			foreach ( $this->mProperties as $property ) {
				$key = $property->getKey();
				$this->mPropVals[$key] = $semanticData->getPropertyValues( $property );

				if ( SequenceMap::canMap( $property ) && isset( $this->sequenceMap[$key] ) ) {
					$sequenceMap = array_flip( $this->sequenceMap[$key] );

					usort ( $this->mPropVals[$key], function( $a, $b ) use( $sequenceMap ) {

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
					$this->addPropertyObjectValue( $property, $dataItem);
				}
			}
		}

		foreach( $semanticData->getSubSemanticData() as $semData ) {
			$this->addSubSemanticData( $semData );
		}
	}

	/**
	 * Removes data from the given SMWSemanticData.
	 * If the subject of the data that is to be removed is not equal to the
	 * subject of this SMWSemanticData, it will just be ignored (nothing to
	 * remove). Likewise, removing data that is not present does not change
	 * anything.
	 *
	 * @since 1.8
	 *
	 * @param SemanticData $semanticData
	 */
	public function removeDataFrom( SemanticData $semanticData ) {
		if( !$this->mSubject->equals( $semanticData->getSubject() ) ) {
			return;
		}

		foreach ( $semanticData->getProperties() as $property ) {
			$this->removeProperty( $property );
		}

		foreach( $semanticData->getSubSemanticData() as $semData ) {
			$this->removeSubSemanticData( $semData );
		}
	}

	/**
	 * @see SubSemanticData::hasSubSemanticData
	 * @since 1.9
	 *
	 * @param string $subobjectName|null
	 *
	 * @return boolean
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
	 * @return SMWContainerSemanticData|null
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
	public function addSubSemanticData( SemanticData $semanticData ) {
		$this->hash = null;
		$this->subSemanticData->addSubSemanticData( $semanticData );
	}

	/**
	 * @see SubSemanticData::removeSubSemanticData
	 * @since 1.8
	 *
	 * @param SemanticData $semanticData
	*/
	public function removeSubSemanticData( SemanticData $semanticData ) {
		$this->hash = null;
		$this->subSemanticData->removeSubSemanticData( $semanticData );
	}

}
