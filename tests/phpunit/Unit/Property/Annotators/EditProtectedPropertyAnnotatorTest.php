<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataItemFactory;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\EditProtectedPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectedPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\EditProtectedPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAddAnnotationForDisplayTitle( $title, $editProtectionRight, array $expected ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$title
		);

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$instance->setEditProtectionRight( $editProtectionRight );
		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testAddTopIndicatorToFromMatchableRestriction() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		// FIXME 3.0; Only MW 1.25+ (ParserOutput::setIndicator)
		if ( !method_exists( $parserOutput, 'setIndicator' ) ) {
			return $this->markTestSkipped( 'Only MW 1.25+ (ParserOutput::setIndicator)' );
		}

		$parserOutput->expects( $this->once() )
			->method( 'setIndicator' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$instance = new EditProtectedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$title
		);

		$instance->setEditProtectionRight( 'Foo' );
		$instance->addTopIndicatorTo( $parserOutput );
	}

	public function titleProvider() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [] ) );

		$provider = [];

		#0 no EditProtectionRight
		$provider[] = [
			$title,
			false,
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		#1
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		#2
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 1,
				'propertyKeys'   => [ '_EDIP' ],
				'propertyValues' => [ true ],
			]
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'isProtected' )
			->with( $this->equalTo( 'edit' ) )
			->will( $this->returnValue( false ) );

		$title->expects( $this->never() )
			->method( 'getRestrictions' );

		#3
		$provider[] = [
			$title,
			'Foo',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => [],
				'propertyValues' => [],
			]
		];

		return $provider;
	}

}
