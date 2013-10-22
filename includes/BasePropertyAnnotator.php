<?php

namespace SMW;

use SMWDataItem;
use SMWDIBlob;
use SMWDIBoolean;
use SMWDITime;

use WikiPage;
use Revision;
use User;


/**
 * Class that adds base property annotations (predefined, categories etc.)
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BasePropertyAnnotator extends ObservableSubject {

	/** @var SemanticData */
	protected $semanticData;

	/** @var Settings */
	protected $settings;

	/** @var Job */
	protected $dispatcherJob = null;

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 * @param Settings $settings
	 */
	public function __construct( SemanticData $semanticData, Settings $settings ) {
		$this->semanticData = $semanticData;
		$this->settings = $settings;
	}

	/**
	 * Add category information
	 *
	 * Part of this code was entangled in SMWParseData::onParserAfterTidy
	 * which has now been separated and is called from
	 * SMWHooks::onParserAfterTidy
	 *
	 * @note Fetches category information and other final settings
	 * from parser output, so that they are also replicated in SMW for more
	 * efficient querying.
	 *
	 * @see SMWHooks::onParserAfterTidy
	 *
	 * @since 1.9
	 *
	 * @param array $categoryLinks
	 *
	 * @return BasePropertyAnnotator
	 */
	public function addCategories( array $categoryLinks ) {

		// Iterate over available categories
		foreach ( $categoryLinks as $catname ) {

			if ( $this->settings->get( 'smwgCategoriesAsInstances' ) && ( $this->semanticData->getSubject()->getNamespace() !== NS_CATEGORY ) ) {
				$this->semanticData->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_CATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}

			if ( $this->settings->get( 'smwgUseCategoryHierarchy' ) && ( $this->semanticData->getSubject()->getNamespace() === NS_CATEGORY ) ) {
				$this->semanticData->addPropertyObjectValue(
					new DIProperty( DIProperty::TYPE_SUBCATEGORY ),
					new DIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}
		}

		$this->setState( 'updateOutput' );

		return $this;
	}

	/**
	 * Add default sort
	 *
	 * @see SMWHooks::onParserAfterTidy
	 *
	 * @since 1.9
	 *
	 * @param string $defaultSort
	 *
	 * @return BasePropertyAnnotator
	 */
	public function addDefaultSort( $defaultSort ) {
		$sortkey = $defaultSort ? $defaultSort : str_replace( '_', ' ', $this->semanticData->getSubject()->getDBkey() );
		$this->semanticData->addPropertyObjectValue(
			new DIProperty( DIProperty::TYPE_SORTKEY ),
			new SMWDIBlob( $sortkey )
		);

		$this->setState( 'updateOutput' );

		return $this;
	}

	/**
	 * Add additional information that is related to special properties
	 * e.g. modification date, the last edit date etc.
	 *
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 * @param Revision $revision
	 * @param User $user
	 *
	 * @return BasePropertyAnnotator
	 */
	public function addSpecialProperties( WikiPage $wikiPage, Revision $revision, User $user ) {

		// Keeps temporary account over processed properties
		$processedProperty = array();

		foreach ( $this->settings->get( 'smwgPageSpecialProperties' ) as $propertyId ) {

			// Ensure that only special properties are added that are registered
			// and only added once
			if ( ( DIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' ) ||
				( array_key_exists( $propertyId, $processedProperty ) ) ) {
				continue;
			}

			$propertyDI = new DIProperty( $propertyId );

			// Don't do a double round
			if ( $this->semanticData->getPropertyValues( $propertyDI ) !== array() ) {
				$processedProperty[ $propertyId ] = true;
				continue;
			}

			switch ( $propertyId ) {
				case DIProperty::TYPE_MODIFICATION_DATE :
					$dataItem = SMWDITime::newFromTimestamp( $wikiPage->getTimestamp() );
					break;
				case DIProperty::TYPE_CREATION_DATE :
					// Expensive getFirstRevision() initiates a revision table
					// read and is not cached
					$dataItem = SMWDITime::newFromTimestamp(
						$wikiPage->getTitle()->getFirstRevision()->getTimestamp()
					);
					break;
				case DIProperty::TYPE_NEW_PAGE :
					// Expensive isNewPage() does a database read
					// $dataValue = new SMWDIBoolean( $this->title->isNewPage() );
					$dataItem = new SMWDIBoolean( $revision->getParentId() !== '' );
					break;
				case DIProperty::TYPE_LAST_EDITOR :
					$dataItem = DIWikiPage::newFromTitle( $user->getUserPage() );
					break;
			}

			if ( $dataItem instanceof SMWDataItem ) {
				$processedProperty[ $propertyId ] = true;
				$this->semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
			}
		}

		$this->setState( 'updateOutput' );

		return $this;
	}

}
