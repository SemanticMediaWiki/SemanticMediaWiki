<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataItemFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\TranslationPropertyAnnotator;
use SMW\SemanticData;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\TranslationPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TranslationPropertyAnnotatorTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp(): void {
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
			->willReturn( 'Foobar' );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

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
			->willReturn( 'Foobar' );

		$title->expects( $this->never() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

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
