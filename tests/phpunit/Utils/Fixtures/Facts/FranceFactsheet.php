<?php

namespace SMW\Tests\Utils\Fixtures\Facts;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Fixtures\Properties\CountryCategory;
use SMW\Tests\Utils\Fixtures\Properties\LocatedInProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\Subobject;
use SMW\SemanticData;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FranceFactsheet {

	/**
	 * @var DIWikiPage
	 */
	private $targetSubject = null;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @since 2.1
	 *
	 * @param DIWikiPage|null $targetSubject
	 */
	public function __construct( DIWikiPage $targetSubject = null ) {
		$this->targetSubject = $targetSubject;

		if ( $this->targetSubject === null ) {
			$this->targetSubject = $this->asSubject();
		}

		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param DIWikiPage $targetSubject
	 */
	public function setTargetSubject( DIWikiPage $targetSubject ) {
		$this->targetSubject = $targetSubject;
	}

	/**
	 * @since 2.1
	 *
	 * @return DIWikiPage
	 */
	public function asSubject() {
		return new DIWikiPage( 'France', NS_MAIN );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function asEntity() {

		$semanticData = new SemanticData( $this->asSubject() );
		$semanticData->addDataValue( $this->getLocatedInValue() );

		$countryCategory = new CountryCategory();

		$semanticData->addDataValue( $countryCategory->getCategoryValue() );

		return $semanticData;
	}

	/**
	 * @since 2.1
	 *
	 * @return DataValue
	 */
	public function getLocatedInValue() {

		$locatedInProperty = new LocatedInProperty();

		return $this->dataValueFactory->newDataItemValue(
			DIWikiPage::newFromText( 'European Union', NS_MAIN ),
			$locatedInProperty->getProperty(),
			'EU'
		);
	}

	/**
	 * @since 2.1
	 */
	public function purge() {

		$subjects = array();

		$subjects[] = $this->asSubject();
		$subjects[] = $this->targetSubject;
		$subjects[] = $this->getLocatedInValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getLocatedInValue()->getDataItem();

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		foreach ( $subjects as $subject ) {
			if ( $subject instanceof DIWikiPage ) {
				$pageDeleter->deletePage( $subject->getTitle() );
			}
		}
	}

}
