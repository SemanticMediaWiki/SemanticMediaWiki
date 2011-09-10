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
 */
class SMWContainerSemanticData extends SMWSemanticData {

	/**
	 * If true, the object will not allow further changes.
	 * @var boolean
	 */
	protected $m_immutable = false;

	/**
	 * Constructor.
	 *
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( $noDuplicates = true ) {
		$subject = SMWExporter::getInternalObjectDiPage();
		parent::__construct( $subject, $noDuplicates );
	}

	/**
	 * Restore complete serialization which is disabled in SMWSemanticData.
	 */
	public function __sleep() {
		return array( 'mSubject', 'mProperties', 'mPropVals', 'mHasVisibleProps', 'mHasVisibleSpecs', 'mNoDuplicates' );
	}

	/**
	 * Clone handler. Make any clone mutable again.
	 */
	public function __clone() {
		$this->m_immutable = false;
	}

	/**
	 * Freeze the object: no more change operations allowed after calling
	 * this. Normally this is only called when passing the object to an
	 * SMWDIContainer. Other code should not need this.
	 */
	public function makeImmutable() {
		$this->m_immutable = true;
	}

	/**
	 * Change the object to become an exact copy of the given
	 * SMWSemanticData object. Useful to convert arbitrary such
	 * objects into SMWContainerSemanticData objects.
	 *
	 * @param $semanticData SMWSemanticData
	 */
	public function copyDataFrom( SMWSemanticData $semanticData ) {
		$this->throwImmutableException();
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
	 * Store a value for a property identified by its SMWDataItem object,
	 * if the object was not set to immutable.
	 *
	 * @param $property SMWDIProperty
	 * @param $dataItem SMWDataItem
	 */
	public function addPropertyObjectValue( SMWDIProperty $property, SMWDataItem $dataItem ) {
		$this->throwImmutableException();
		parent::addPropertyObjectValue( $property, $dataItem );
	}

	/**
	 * Delete all data other than the subject, if the object was not set to
	 * immutable.
	 */
	public function clear() {
		$this->throwImmutableException();
		parent::clear();
	}

	/**
	 * Throw an exception if the object is immutable.
	 */
	protected function throwImmutableException() {
		if ( $this->m_immutable ) {
			throw new SMWDataItemException( 'Changing the SMWSemanticData object that belongs to a data item of type SMWDIContainer is not allowed. Data items are immutable.' );
		}
	}
}

/**
 * This class implements container data items that can store SMWSemanticData
 * objects. In this sense, data items of this type are a kind of "internal
 * object" that can contain the data that is otherwise associated with a wiki
 * page.
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
		$this->m_semanticData->makeImmutable();
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
	 * Get an internal object that can be used as a subject for this data.
	 * This subject is not part of the data itself but makes the connection
	 * to the wiki page for which this data will be stored. This allows to
	 * encode a kind of provenance information when storing container data,
	 * useful to find the source of the data, and to retrieve more data when
	 * a structural (helper) node is found in a query etc.
	 *
	 * @return SMWDIWikiPage
	 */
	public function getSubjectPage( SMWDIWikiPage $masterPage ) {
		return new SMWDIWikiPage( $masterPage->getDBkey(), $masterPage->getNamespace(), $masterPage->getInterwiki(), '_' . $this->getHash() );
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
