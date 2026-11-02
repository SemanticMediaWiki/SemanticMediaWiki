<?php

namespace SMW\Tests\Integration\Maintenance;

use MediaWiki\MediaWikiServices;
use SMW\DataItems\Blob;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Maintenance\DataRebuilder
 *
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class RebuildDataFixedPropertyTableTest extends SMWIntegrationTestCase {

	private const SUBJECT = 'RebuildDataFixedPropertyTableTest subject';
	private const TEXT_PROPERTY = 'Has fixed rebuild text';
	private const NUMBER_PROPERTY = 'Has fixed rebuild number';

	/**
	 * @var string[]
	 */
	private array $tableNames = [];

	protected function tearDown(): void {
		$connection = $this->getStore()->getConnection( DB_PRIMARY );

		foreach ( $this->tableNames as $tableName ) {
			$connection->query( 'DROP TABLE ' . $connection->tableName( $tableName ), __METHOD__ );
		}

		$this->getStore()->clear();

		parent::tearDown();
	}

	public function testRebuildStoresValuesInEmptyTablesCreatedBeforeTheTypesWereStored(): void {
		$this->createPages();
		$this->removeStoredTypes();
		$this->createEmptyFixedTablesForPageValues();

		$this->runRebuildData();

		$subject = WikiPage::newFromText( self::SUBJECT );
		$this->assertEquals(
			[ new Blob( 'Some text' ) ],
			array_values( $this->getStore()->getPropertyValues( $subject, $this->newProperty( self::TEXT_PROPERTY ) ) )
		);
		$this->assertEquals(
			[ new Number( 42 ) ],
			array_values( $this->getStore()->getPropertyValues( $subject, $this->newProperty( self::NUMBER_PROPERTY ) ) )
		);
	}

	private function createPages(): void {
		$pageCreator = UtilityFactory::getInstance()->newPageCreator();
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$pageCreator->createPage( $titleFactory->newFromText( 'Property:' . self::TEXT_PROPERTY ) )
			->doEdit( '[[Has type::Text]]' );
		$pageCreator->createPage( $titleFactory->newFromText( 'Property:' . self::NUMBER_PROPERTY ) )
			->doEdit( '[[Has type::Number]]' );
		$pageCreator->createPage( $titleFactory->newFromText( self::SUBJECT ) )
			->doEdit( '[[' . self::TEXT_PROPERTY . '::Some text]] [[' . self::NUMBER_PROPERTY . '::42]]' );
	}

	private function removeStoredTypes(): void {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$this->getStore()->deleteSubject( $titleFactory->newFromText( 'Property:' . self::TEXT_PROPERTY ) );
		$this->getStore()->deleteSubject( $titleFactory->newFromText( 'Property:' . self::NUMBER_PROPERTY ) );
	}

	private function createEmptyFixedTablesForPageValues(): void {
		$this->testEnvironment->addConfiguration(
			'smwgFixedProperties',
			[ self::TEXT_PROPERTY, self::NUMBER_PROPERTY ]
		);
		$this->getStore()->clear();

		$tableBuilder = TableBuilder::factory( $this->getStore()->getConnection( DB_PRIMARY ) );

		foreach ( [ self::TEXT_PROPERTY, self::NUMBER_PROPERTY ] as $label ) {
			$tableName = $this->getStore()->findPropertyTableID( $this->newProperty( $label ) );

			$table = new Table( $tableName );
			$table->addColumn( 's_id', FieldType::FIELD_ID );
			$table->addColumn( 'o_id', FieldType::FIELD_ID );
			$tableBuilder->create( $table );

			$this->tableNames[] = $tableName;
		}
	}

	private function runRebuildData(): void {
		$runner = UtilityFactory::getInstance()->newRunnerFactory()->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildData'
		);
		$runner->setQuiet();
		$runner->run();

		$this->getStore()->clear();
	}

	private function newProperty( string $label ): Property {
		return Property::newFromUserLabel( $label );
	}

}
