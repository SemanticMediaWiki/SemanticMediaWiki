<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use ExtensionRegistry;
use SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertyExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $declarationExaminer;
	private $semanticData;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PredefinedPropertyExaminer::class,
			new PredefinedPropertyExaminer( $this->declarationExaminer )
		);
	}

	public function testMessages() {
		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$dataItemFactory = new DataItemFactory();

		$instance = new PredefinedPropertyExaminer(
			$this->declarationExaminer
		);

		$instance->check(
			$dataItemFactory->newDIProperty( '_MDAT' )
		);

		$this->assertContains(
			'smw-property-predefined-mdat',
			$instance->getMessagesAsString()
		);
	}

	public function testTypeDeclarationMismatch() {
		$dataItemFactory = new DataItemFactory();
		$uri = $dataItemFactory->newDIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_num' );

		$this->semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->with( $dataItemFactory->newDIProperty( '_TYPE' ) )
			->willReturn( true );

		$this->semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $uri ] );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$instance = new PredefinedPropertyExaminer(
			$this->declarationExaminer
		);

		$instance->check(
			$dataItemFactory->newDIProperty( '_MDAT' )
		);

		$this->assertContains(
			'"error","smw-property-req-violation-predefined-type"',
			$instance->getMessagesAsString()
		);
	}

	public function testGeoProperty_MissingMapsExtension() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Maps' ) ) {
			$this->markTestSkipped( 'Skipping test because the Maps extension is installed!' );
		}

		$this->semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->willReturn( false );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$dataItemFactory = new DataItemFactory();

		$instance = new PredefinedPropertyExaminer(
			$this->declarationExaminer
		);

		$property = $dataItemFactory->newDIProperty( '_geo' );

		$instance->check(
			$property
		);

		$this->assertContains(
			'["error","smw-property-req-violation-missing-maps-extension","Geographic coordinates"]',
			$instance->getMessagesAsString()
		);
	}

}
