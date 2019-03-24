<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertyExaminerTest extends \PHPUnit_Framework_TestCase {

	private $declarationExaminer;
	private $semanticData;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->will( $this->returnValue( [] ) );

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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
			->will( $this->returnValue( $this->semanticData ) );

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
			->with( $this->equalTo( $dataItemFactory->newDIProperty( '_TYPE' ) ) )
			->will( $this->returnValue( true ) );

		$this->semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $uri ] ) );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $this->semanticData ) );


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

		if ( defined( 'SM_VERSION' ) ) {
			$this->markTestSkipped( 'Skipping test because the Maps extension is installed!' );
		}

		$this->semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->will( $this->returnValue( false ) );

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $this->semanticData ) );

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
