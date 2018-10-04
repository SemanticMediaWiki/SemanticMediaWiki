<?php

namespace SMW\DataModel;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exception\SubSemanticDataException;
use SMW\SemanticData;

/**
 * @private
 *
 * Internal handling of the SubSemanticData container and its subsequent
 * add and remove operations.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SubSemanticData {

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
	private $noDuplicates;

	/**
	 * DIWikiPage object that is the subject of this container.
	 * Subjects can never be null (and this is ensured in all methods setting
	 * them in this class).
	 *
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * Semantic data associated to subobjects of the subject of this
	 * SMWSemanticData.
	 * These key-value pairs of subObjectName (string) =>SMWSemanticData.
	 *
	 * @since 2.5
	 * @var SemanticData[]
	 */
	private $subSemanticData = [];

	/**
	 * Internal flag that indicates if this semantic data will accept
	 * subdata. Semantic data objects that are subdata already do not allow
	 * (second level) subdata to be added. This ensures that all data is
	 * collected on the top level, and in particular that there is only one
	 * way to represent the same data with subdata. This is also useful for
	 * diff computation.
	 *
	 * @var boolean
	 */
	private $subDataAllowed = true;

	/**
	 * Maximum depth for an recursive sub data assignment
	 *
	 * @var integer
	 */
	private $subContainerMaxDepth = 3;

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( DIWikiPage $subject, $noDuplicates = true ) {
		$this->clear();
		$this->subject = $subject;
		$this->noDuplicates = $noDuplicates;
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
		return [ 'subject', 'subSemanticData' ];
	}

	/**
	 * Return subject to which the stored semantic annotations refer to.
	 *
	 * @return DIWikiPage subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * This is used as contingency where the serialized SementicData still
	 * has an array object reference.
	 *
	 * @since 2.5
	 *
	 * @return ContainerSemanticData[]
	 */
	public function copyDataFrom( array $subSemanticData ) {
		$this->subSemanticData = $subSemanticData;
	}

	/**
	 * Return the array of subSemanticData objects in form of
	 * subobjectName => ContainerSemanticData
	 *
	 * @since 2.5
	 *
	 * @return ContainerSemanticData[]
	 */
	public function getSubSemanticData() {
		return $this->subSemanticData;
	}

	/**
	 * @since 2.5
	 */
	public function clear() {
		$this->subSemanticData = [];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $subobjectName|null
	 *
	 * @return boolean
	 */
	public function hasSubSemanticData( $subobjectName = null ) {

		if ( $this->subSemanticData === [] || $subobjectName === '' ) {
			return false;
		}

		return $subobjectName !== null ? isset( $this->subSemanticData[$subobjectName] ) : true;
	}

	/**
	 * Find a particular subobject container using its name as identifier
	 *
	 * @since 2.5
	 *
	 * @param string $subobjectName
	 *
	 * @return ContainerSemanticData|null
	 */
	public function findSubSemanticData( $subobjectName ) {

		if ( $this->hasSubSemanticData( $subobjectName ) && isset( $this->subSemanticData[$subobjectName] ) ) {
			return $this->subSemanticData[$subobjectName];
		}

		return null;
	}

	/**
	 * Add data about subobjects
	 *
	 * Will only work if the data that is added is about a subobject of
	 * this SMWSemanticData's subject. Otherwise an exception is thrown.
	 * The SMWSemanticData object that is given will belong to this object
	 * after the operation; it should not be modified further by the caller.
	 *
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 *
	 * @throws SubSemanticDataException if not adding data about a subobject of this data
	 */
	public function addSubSemanticData( SemanticData $semanticData ) {

		if ( $semanticData->subContainerDepthCounter > $this->subContainerMaxDepth ) {
			throw new SubSemanticDataException( "Cannot add further subdata with the depth of {$semanticData->subContainerDepthCounter}. You are trying to add data beyond the max depth of {$this->subContainerMaxDepth} to an SemanticData object." );
		}

		$subobjectName = $semanticData->getSubject()->getSubobjectName();

		if ( $subobjectName == '' ) {
			throw new SubSemanticDataException( "Cannot add data that is not about a subobject." );
		}

		if ( $semanticData->getSubject()->getDBkey() !== $this->getSubject()->getDBkey() ) {
			throw new SubSemanticDataException( "Data for a subobject of {$semanticData->getSubject()->getDBkey()} cannot be added to {$this->getSubject()->getDBkey()}." );
		}

		$this->appendSubSemanticData( $semanticData, $subobjectName );
	}

	/**
	* Remove data about a subobject
	*
	* If the removed data is not about a subobject of this object,
	* it will silently be ignored (nothing to remove). Likewise,
	* removing data that is not present does not change anything.
	*
	* @since 2.5
	*
	* @param SemanticData $semanticData
	*/
	public function removeSubSemanticData( SemanticData $semanticData ) {

		if ( $semanticData->getSubject()->getDBkey() !== $this->getSubject()->getDBkey() ) {
			return;
		}

		$subobjectName = $semanticData->getSubject()->getSubobjectName();

		if ( $this->hasSubSemanticData( $subobjectName ) ) {
			$this->subSemanticData[$subobjectName]->removeDataFrom( $semanticData );

			if ( $this->subSemanticData[$subobjectName]->isEmpty() ) {
				unset( $this->subSemanticData[$subobjectName] );
			}
		}
	}

	/**
	 * Remove property and all values associated with this property.
	 *
	 * @since 2.5
	 *
	 * @param $property DIProperty
	 */
	public function removeProperty( DIProperty $property ) {

		 // Inverse properties cannot be used for an annotation
		if ( $property->isInverse() ) {
			return;
		}

		foreach ( $this->subSemanticData as $containerSemanticData ) {
			$containerSemanticData->removeProperty( $property );
		}
	}

	private function appendSubSemanticData( $semanticData, $subobjectName ) {

		if ( $this->hasSubSemanticData( $subobjectName ) ) {
			$this->subSemanticData[$subobjectName]->importDataFrom( $semanticData );

			foreach ( $semanticData->getSubSemanticData() as $containerSemanticData ) {
				$this->addSubSemanticData( $containerSemanticData );
			}

			return;
		}

		$semanticData->subContainerDepthCounter++;

		foreach ( $semanticData->getSubSemanticData() as $containerSemanticData ) {

			// Skip container that are known to be registered (avoids recursive statement extension)
			if ( $this->hasSubSemanticData( $containerSemanticData->getSubject()->getSubobjectName() ) ) {
				continue;
			}

			$this->addSubSemanticData( $containerSemanticData );
		}

		$semanticData->clearSubSemanticData();
		$this->subSemanticData[$subobjectName] = $semanticData;
	}

}
