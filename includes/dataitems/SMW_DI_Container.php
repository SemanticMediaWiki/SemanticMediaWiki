<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * Subclass of SMWSemanticData that can be made read only to enforce the
 * immutability of all SMW data items. This ensures that the container dataitem
 * can safely give out an object reference without concern that this is
 * exploited to indirectly change its content.
 *
 * Objects of this class are usually only made immutable when passed to a data
 * item, so they can be built as usual. When cloning the object, the clone
 * becomes mutable again. This is safe since all data is stored in arrays that
 * contain only immutable objects and values of basic types. Arrays are copied
 * (lazily) when cloning in PHP, so later changes in the cloce will not affect
 * the original.
 *
 * In contrast to normal SMWSemanticData objects, SMWContainerSemanticData can
 * be created without specifying a subject. In this case, the subject is some
 * "anonymous" object that is left unspecified (for search) or generated later
 * (for storage). The method hasAnonymousSubject() should be used to check for
 * this case (as the method getSubject() will always return a valid subject).
 * See the documentation of SMWDIContainer for further details.
 */
class SMWContainerSemanticData extends SMWSemanticData {

	/**
	 * Construct a data container that refers to an anonymous subject. See
	 * the documenation of the class for details.
	 *
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( $noDuplicates = true ) {
		$subject = new SMWDIWikiPage( 'SMWInternalObject', NS_SPECIAL, '' );
		return parent::__construct( $subject, $noDuplicates );
	}

	/**
	 * Restore complete serialization which is disabled in SMWSemanticData.
	 */
	public function __sleep() {
		return array( 'mSubject', 'mProperties', 'mPropVals', 'mHasVisibleProps', 'mHasVisibleSpecs', 'mNoDuplicates' );
	}

	/**
	 * Change the subject of this semantic data container so that it is used
	 * as a subobject of the given master page.
	 *
	 * This "contextualizes" the data to belong to a given (master) wiki
	 * page. It happens automatically when adding the object as part of a
	 * property value to another SMWSemanticData object.
	 *
	 * @note This method could be extended to allow custom subobject names
	 * to be set instead of using the hash. Note, however, that the length
	 * of the subobject name in the database is limited in SQLStore2. To
	 * make subobjects for sections of a page, this limit would have to be
	 * extended.
	 *
	 * @param SMWDIWikiPage $masterPage
	 */
	public function setMasterPage( SMWDIWikiPage $masterPage ) {
		$subobject = $this->getHash(); // 32 chars: current max length of subobject name in store
		$subobject{0} = '_'; // mark as anonymous subobject; hash is still good
		$this->mSubject = new SMWDIWikiPage( $masterPage->getDBkey(),
			$masterPage->getNamespace(), $masterPage->getInterwiki(),
			$subobject );
		foreach ( $this->getProperties() as $property ) {
			foreach ( $this->getPropertyValues( $property ) as $di ) {
				if ( $di->getDIType() == SMWDataItem::TYPE_CONTAINER ) {
					$di->getSemanticData()->setMasterPage( $this->mSubject );
				}
			}
		}
	}

	/**
	 * Change the object to become an exact copy of the given
	 * SMWSemanticData object. Useful to convert arbitrary such
	 * objects into SMWContainerSemanticData objects.
	 *
	 * @param $semanticData SMWSemanticData
	 */
	public function copyDataFrom( SMWSemanticData $semanticData ) {
		$this->mSubject = $semanticData->getSubject();
		$this->mProperties = $semanticData->getProperties();
		$this->mPropVals = array();
		foreach ( $this->mProperties as $property ) {
			$this->mPropVals[$property->getKey()] = $semanticData->getPropertyValues( $property );
		}
		$this->mHasVisibleProps = $semanticData->hasVisibleProperties();
		$this->mHasVisibleSpecs = $semanticData->hasVisibleSpecialProperties();
		$this->mNoDuplicates = $semanticData->mNoDuplicates;
	}

	/**
	 * Check if the subject of this container is an anonymous object.
	 * See the documenation of the class for details.
	 * @return boolean
	 */
	public function hasAnonymousSubject() {
		if ( $this->mSubject->getNamespace() == NS_SPECIAL &&
		     $this->mSubject->getDBkey() == 'SMWInternalObject' &&
		     $this->mSubject->getInterwiki() == '' ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return subject to which the stored semantic annotation refer to, or
	 * throw an exception if the subject is anonymous (if the data has not
	 * been contextualized with setMasterPage() yet).
	 *
	 * @return SMWDIWikiPage subject
	 */
	public function getSubject() {
		if ( $this->hasAnonymousSubject() ) {
			throw new SMWDataItemException("Trying to get the subject of a container data item that has not been given any. The method hasAnonymousSubject() can be called to detect this situation.");
		} else {
			return $this->mSubject;
		}
	}

}

/**
 * This class implements container data items that can store SMWSemanticData
 * objects. Containers are not dataitems in the proper sense: they do not
 * represent a single, opaque value that can be assigned to a property. Rather,
 * a container represents a collection of individual property-value assignments.
 * When a container is stored, these individual data assignments are stored --
 * the data managed by SMW never contains any "container", just individual
 * property assignments. Likewise, when a container is used in search, it is
 * interpreted as a patterns of possible property assignments, and this pattern
 * is searched for.
 *
 * The data encapsulated in a container data item is essentially an
 * SMWSemanticData object. The data represented by the container consists of
 * the data stored in this SMWSemanticData object. As a special case, it is
 * possible that the subject of this data is left unspecified. The name of the
 * subject in this case is not part of the data: when storing such containers, a
 * new name will be invented; when searching for such containers, only the
 * property-value pairs are considered relevant in the search. If the subject is
 * given (i.e. not anonymous), and the container DI is used as a property value
 * for a wikipage Foo, then the subject of the container must be a subobject of
 * Foo, for example Foo#section. In this case "Foo" is called the master page of
 * the container. To get a suitable subject for a given master page, the method
 * getSubjectPage() can be used.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIContainer extends SMWDataItem {

	/**
	 * Internal value.
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

	public function getSerialization() {
		return serialize( $this->m_semanticData );
	}

	/**
	 * Get a hash string for this data item.
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->m_semanticData->getHash();
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIContainer
	 */
	public static function doUnserialize( $serialization ) {
		/// TODO May issue an E_NOTICE when problems occur; catch this
		$data = unserialize( $serialization );
		if ( !( $data instanceof SMWContainerSemanticData ) ) {
			throw new SMWDataItemException( "Could not unserialize SMWDIContainer from the given string." );
		}
		return new SMWDIContainer( $data );
	}

}
