<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataItemFactory;
use SMW\SemanticData;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\TranslationPropertyAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\TranslationPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TranslationPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = TestEnvironment::newValidatorFactory()->newSemanticDataValidator();
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TranslationPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			[]
		);

		$this->assertInstanceOf(
			TranslationPropertyAnnotator::class,
			$instance
		);
	}

	public function testAddAnnotation() {

		$semanticData = new SemanticData(
			$this->dataItemFactory->newDIWikiPage( 'Foo' )
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foobar' ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$translation = [
			'languagecode' => 'foo',
			'sourcepagetitle' => $title,
			'messagegroupid' => 'bar'
		];

		$expected = [
			'propertyCount'  => 3,
			'propertyKeys'   => [ '_LCODE', '_TRANS_GROUP', '_TRANS_SOURCE' ],
			'propertyValues' => [ 'foo', 'bar', ':Foobar' ],
		];

		$instance = new TranslationPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$translation
		);

		$instance->setPredefinedPropertyList( [ '_TRANS' ] );

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_TRANS'
			],
			$instance->getSemanticData()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()->findSubSemanticData( 'trans.foo' )
		);
	}

	public function testAddAnnotation_NotListed() {

		$semanticData = new SemanticData(
			$this->dataItemFactory->newDIWikiPage( 'Foo' )
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->never() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foobar' ) );

		$title->expects( $this->never() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$translation = [
			'languagecode' => 'foo',
			'sourcepagetitle' => $title,
			'messagegroupid' => 'bar'
		];

		$instance = new TranslationPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$translation
		);

		$instance->setPredefinedPropertyList( [] );
		$instance->addAnnotation();
	}

	public function testAddAnnotation_EmptyData() {

		$semanticData = new SemanticData(
			$this->dataItemFactory->newDIWikiPage( 'Foo' )
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$translation = [];

		$instance = new TranslationPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$translation
		);

		$instance->addAnnotation();

		$this->assertEquals(
			$semanticData,
			$instance->getSemanticData()
		);
	}

}
