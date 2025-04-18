<?php

namespace SMW\Tests\Integration;

use SMW\DIProperty;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SemanticDataSortKeyUpdateDBIntegrationTest extends SMWIntegrationTestCase {

	private $semanticDataFactory;
	private $mwHooksHandler;
	private $subjects = [];

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
	}

	protected function tearDown(): void {
		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->subjects );
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testSubjectSortKeySetter() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$subject = $semanticData->getSubject();
		$subject->setSortKey( 'a_b_c' );

		$this->getStore()->updateData( $semanticData );

		$semanticDataFromDB = $this->getStore()->getSemanticData( $subject );

		$this->assertEquals(
			'a b c',
			$semanticDataFromDB->getSubject()->getSortKey()
		);

		foreach ( $semanticDataFromDB->getPropertyValues( new DIProperty( '_SKEY' ) ) as $value ) {
			$this->assertEquals(
				'a b c',
				$value->getString()
			);
		}

		$this->subjects[] = $semanticData->getSubject();
	}

	public function testDefinedSortKeyTakesPrecedenceOverSubjectSortKey() {
		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$subject = $semanticData->getSubject();
		$subject->setSortKey( '1_2_3' );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_SKEY' ),
			new DIBlob( 'x_y_z' )
		);

		$this->getStore()->updateData( $semanticData );
		$this->getStore()->clear();

		$semanticDataFromDB = $this->getStore()->getSemanticData( $subject );

		$this->assertEquals(
			'x y z',
			$semanticDataFromDB->getSubject()->getSortKey()
		);

		foreach ( $semanticDataFromDB->getPropertyValues( new DIProperty( '_SKEY' ) ) as $value ) {
			$this->assertEquals(
				'x y z',
				$value->getString()
			);
		}

		$this->subjects[] = $semanticData->getSubject();
	}

}
