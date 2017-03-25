<?php

namespace SMW\DataModel;

use RuntimeException;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\Exception\DataItemException;

/**
 * Subclass of SemanticData that is used to store the data in SMWDIContainer
 * objects. It is special since the subject that the stored property-value pairs
 * refer may or may not be specified explicitly. This can be tested with
 * hasAnonymousSubject(). When trying to access the subject in anonymous state,
 * an Exception will be thrown.
 *
 * Anonymous container data items are used when no
 * page context is available, e.g. when specifying such a value in a search form
 * where the parent page is not known.
 *
 * Besides this change, the subclass mainly is needed to restore the disabled
 * serialization of SemanticData.
 *
 * See also the documentation of SMWDIContainer.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ContainerSemanticData extends SemanticData {

	/**
	 * @var boolean
	 */
	private $skipAnonymousCheck = false;

	/**
	 * Construct a data container that refers to an anonymous subject. See
	 * the documentation of the class for details.
	 *
	 * @since 1.7
	 *
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public static function makeAnonymousContainer( $noDuplicates = true, $skipAnonymousCheck = false ) {

		$containerSemanticData = new ContainerSemanticData(
			new DIWikiPage( 'SMWInternalObject', NS_SPECIAL, '', 'int' ),
			$noDuplicates
		);

		if ( $skipAnonymousCheck ) {
			$containerSemanticData->skipAnonymousCheck();
		}

		return $containerSemanticData;
	}

	/**
	 * Restore complete serialization which is disabled in SemanticData.
	 */
	public function __sleep() {
		return array(
			'mSubject',
			'mProperties',
			'mPropVals',
			'mHasVisibleProps',
			'mHasVisibleSpecs',
			'mNoDuplicates',
			'skipAnonymousCheck',
			'subSemanticData'
		);
	}

	/**
	 * Skip the check as it is required for some "search pattern match" activity
	 * to temporarily to access the container without raising an exception.
	 *
	 * @since 2.4
	 */
	public function skipAnonymousCheck() {
		$this->skipAnonymousCheck = true;
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
		     $this->mSubject->getInterwiki() === '' &&
		     $this->mSubject->getSubobjectName() === 'int' ) {
			return true;
		}

		return false;
	}

	/**
	 * Return subject to which the stored semantic annotation refer to, or
	 * throw an exception if the subject is anonymous (if the data has not
	 * been contextualized with setMasterPage() yet).
	 *
	 * @return DIWikiPage subject
	 * @throws DataItemException
	 */
	public function getSubject() {

		$error = "This container has been classified as anonymous and by trying to access" .
		" its subject (that has not been given any) an exception is raised to inform about" .
		" the incorrect usage. An anonymous container can only be used for a search pattern match.";

		if ( !$this->skipAnonymousCheck && $this->hasAnonymousSubject() ) {
			throw new DataItemException( $error );
		}

		return $this->mSubject;
	}

	/**
	 * Change the object to become an exact copy of the given
	 * SemanticData object. This is used to make other types of
	 * SemanticData into an SMWContainerSemanticData. To copy objects of
	 * the same type, PHP clone() should be used.
	 *
	 * @since 1.7
	 *
	 * @param $semanticData SemanticData object to copy from
	 */
	public function copyDataFrom( SemanticData $semanticData ) {
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
