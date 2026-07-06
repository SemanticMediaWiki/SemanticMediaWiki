<?php

namespace SMW\Tests\Utils\Fixtures\Facts;

use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\Tests\Utils\Fixtures\Properties\CountryCategory;
use SMW\Tests\Utils\Fixtures\Properties\LocatedInProperty;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class FranceFactsheet {

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @since 2.1
	 */
	public function __construct( private ?WikiPage $targetSubject = null ) {
		if ( $this->targetSubject === null ) {
			$this->targetSubject = $this->asSubject();
		}

		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param WikiPage $targetSubject
	 */
	public function setTargetSubject( WikiPage $targetSubject ) {
		$this->targetSubject = $targetSubject;
	}

	/**
	 * @since 2.1
	 *
	 * @return WikiPage
	 */
	public function asSubject() {
		return new WikiPage( 'France', NS_MAIN );
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

		return $this->dataValueFactory->newDataValueByItem(
			WikiPage::newFromText( 'European Union', NS_MAIN ),
			$locatedInProperty->getProperty(),
			'EU'
		);
	}

	/**
	 * @since 2.1
	 */
	public function purge() {
		$subjects = [];

		$subjects[] = $this->asSubject();
		$subjects[] = $this->targetSubject;
		$subjects[] = $this->getLocatedInValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getLocatedInValue()->getDataItem();

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		foreach ( $subjects as $subject ) {
			if ( $subject instanceof WikiPage ) {
				$pageDeleter->deletePage( $subject->getTitle() );
			}
		}
	}

}
