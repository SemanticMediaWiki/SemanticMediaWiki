<?php

namespace SMW;

use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDITime as DITime;

use WikiPage;
use Revision;
use User;

/**
 * Handling predefined property annotations
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotator extends PropertyAnnotatorDecorator {

	/** @var WikiPage */
	protected $wikiPage;

	/** @var Revision */
	protected $revision;

	/** @var User */
	protected $user;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param WikiPage $wikiPage
	 * @param Revision $revision
	 * @param User $user
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, WikiPage $wikiPage, Revision $revision, User $user ) {
		parent::__construct( $propertyAnnotator );
		$this->wikiPage = $wikiPage;
		$this->revision = $revision;
		$this->user = $user;
	}

	/**
	 * @see PropertyAnnotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {

		$predefinedProperties = $this->withContext()->getSettings()->get( 'smwgPageSpecialProperties' );
		$cachedProperties = array();

		foreach ( $predefinedProperties as $propertyId ) {

			// Ensure that only special properties are added that are registered
			// and only added once
			if ( ( DIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' ) ||
				( array_key_exists( $propertyId, $cachedProperties ) ) ) {
				continue;
			}

			$propertyDI = new DIProperty( $propertyId );

			// Don't do a double round
			if ( $this->getSemanticData()->getPropertyValues( $propertyDI ) !== array() ) {
				$cachedProperties[ $propertyId ] = true;
				continue;
			}

			switch ( $propertyId ) {
				case DIProperty::TYPE_MODIFICATION_DATE :
					$dataItem = DITime::newFromTimestamp( $this->wikiPage->getTimestamp() );
					break;
				case DIProperty::TYPE_CREATION_DATE :
					// Expensive getFirstRevision() initiates a revision table
					// read and is not cached
					$dataItem = DITime::newFromTimestamp(
						$this->wikiPage->getTitle()->getFirstRevision()->getTimestamp()
					);
					break;
				case DIProperty::TYPE_NEW_PAGE :
					// Expensive isNewPage() does a database read
					// $dataValue = new SMWDIBoolean( $this->title->isNewPage() );
					$dataItem = new DIBoolean( $this->revision->getParentId() !== '' );
					break;
				case DIProperty::TYPE_LAST_EDITOR :
					$dataItem = DIWikiPage::newFromTitle( $this->user->getUserPage() );
					break;
			}

			if ( $dataItem instanceof DataItem ) {
				$cachedProperties[ $propertyId ] = true;
				$this->getSemanticData()->addPropertyObjectValue( $propertyDI, $dataItem );
			}
		}

		$this->setState( 'updateOutput' );

		return $this;
	}

}
