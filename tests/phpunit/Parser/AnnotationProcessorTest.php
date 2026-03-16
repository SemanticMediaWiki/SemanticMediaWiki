<?php

namespace SMW\Tests\Parser;

use PHPUnit\Framework\TestCase;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Parser\AnnotationProcessor;
use SMW\SemanticData;

/**
 * @covers \SMW\Parser\AnnotationProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AnnotationProcessorTest extends TestCase {

	private $semanticData;
	private $dataValueFactory;

	protected function setUp(): void {
		$this->semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueFactory = $this->getMockBuilder( DataValueFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AnnotationProcessor::class,
			new AnnotationProcessor( $this->semanticData )
		);
	}

	public function testGetSemanticData() {
		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$this->assertSame(
			$this->semanticData,
			$instance->getSemanticData()
		);
	}

	public function testCanAnnotate() {
		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$this->assertTrue(
			$instance->canAnnotate()
		);

		$instance->setCanAnnotate( false );

		$this->assertFalse(
			$instance->canAnnotate()
		);
	}

	public function testNewDataValueByText() {
		$this->dataValueFactory->expects( $this->once() )
			->method( 'newDataValueByText' );

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$instance->newDataValueByText( 'Foo', 'Bar' );
	}

	public function testNewDataValueByItem() {
		$dataItem = $this->getMockBuilder( 'SMWDataItem' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$property = $this->getMockBuilder( DIProperty::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueFactory->expects( $this->once() )
			->method( 'newDataValueByItem' );

		$instance = new AnnotationProcessor(
			$this->semanticData,
			$this->dataValueFactory
		);

		$instance->newDataValueByItem( $dataItem, $property );
	}

}
