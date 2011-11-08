<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * Subclass of SMWSemanticData that is used to store the data in SMWDIContainer
 * objects. It is special since the subject that the stored property-value pairs
 * refer may or may not be specified explicitly. This can be tested with
 * hasAnonymousSubject(). When trying to access the subject in anonymous state,
 * an Exception will be thrown. Anonymous container data items are used when no
 * page context is available, e.g. when specifying such a value in a search form
 * where the parent page is not known.
 *
 * Besides this change, the subclass mainly is needed to restroe the disabled
 * serialization of SMWSemanticData.
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
	 * the documentation of the class for details.
	 *
	 * @since 1.7
	 *
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public static function makeAnonymousContainer( $noDuplicates = true ) {
		$subject = new SMWDIWikiPage( 'SMWInternalObject', NS_SPECIAL, '' );
		return new SMWContainerSemanticData( $subject, $noDuplicates );
	}

	/**
	 * Restore complete serialization which is disabled in SMWSemanticData.
	 */
	public function __sleep() {
		return array( 'mSubject', 'mProperties', 'mPropVals',
			'mHasVisibleProps', 'mHasVisibleSpecs', 'mNoDuplicates' );
	}

	/**
	 * Check if the subject of this container is an anonymous object.
	 * See the documenation of the class for details.
	 *
	 * @return boolean
	 */
	public function hasAnonymousSubject() {
		if ( $this->mSubject->getNamespace() == NS_SPECIAL &&
		     $this->mSubject->getDBkey() == 'SMWInternalObject' &&
		     $this->mSubject->getInterwiki() === '' ) {
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
			throw new SMWDataItemException("Trying to get the subject of a container data item that has not been given any. This container can only be used as a search pattern.");
		} else {
			return $this->mSubject;
		}
	}

	/**
	 * Change the object to become an exact copy of the given
	 * SMWSemanticData object. This is used to make other types of
	 * SMWSemanticData into an SMWContainerSemanticData. To copy objects of
	 * the same type, PHP clone() should be used.
	 *
	 * @since 1.7
	 *
	 * @param $semanticData SMWSemanticData object to copy from
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
	 *
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
