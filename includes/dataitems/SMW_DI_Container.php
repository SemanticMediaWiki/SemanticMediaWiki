<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * Subclass of SMWSemanticData that is used to store the data in SMWDIContainer
 * objects. It is special since the subject that the stored property-value pairs
 * refer to is not specified explicitly. Instead, the subject used in container
 * data items is determined by the stored data together with the page that the
 * data item is assigned to (the "master page" of this data). Maintaining the
 * relation to the master page is important for data management since the
 * subjects of container data items must be deleted when deleting the value.
 *
 * Therefore, this container data provides a method setMasterPage() that is used
 * to define the master page. SMWSemanticData will always call this method when
 * it is given a container data item to store. Until this is done, the subject
 * of the container is "anonymous" -- it has no usable name. This can be tested
 * with hasAnonymousSubject(). When trying to access the subject in this state,
 * an Exception will be thrown. Note that container data items that are not
 * related to any master page are necessary, e.g. when specifying such a value
 * in a search form where the master page is not known.
 *
 * See also the documentation of SMWDIContainer.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
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
 * a container represents a "subobject" with a number of property-value
 * assignments. When a container is stored, these individual data assignments
 * are stored -- the data managed by SMW never contains any "container", just
 * individual property assignments for the subobject. Likewise, when a container
 * is used in search, it is interpreted as a patterns of possible property
 * assignments, and this pattern is searched for.
 *
 * The data encapsulated in a container data item is essentially an
 * SMWSemanticData object of class SMWContainerSemanticData. This class has a
 * special handling for the subject of the stored annotations (i.e. the
 * subobject). See the repsective documentation for details.
 * 
 * Being a mere placeholder/template for other data, an SMWDIContainer is not
 * immutable as the other basic data items. New property-value pairs can always
 * be added to the internal SMWContainerSemanticData. Moreover, the subobject
 * that the container refers to is set only after it has been created, when the
 * data item is added as a property value to some existing SMWSemanticData.
 * Only after this has happened is the subobject fully defined. Any attempt to
 * obtain the subobject from the internal SMWContainerSemanticData before it
 * has been defined will result in an SMWDataItemException.
 *
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
