<?php

namespace SMW\Tests\Page\ListBuilder;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Page\ListBuilder\ListBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Page\ListBuilder\ListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListBuilderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ListBuilder::class,
			new ListBuilder( $this->store )
		);
	}

	public function testCreateEmptyList() {

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListBuilder( $this->store );

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->assertEquals(
			'',
			$instance->createHtml( $property, $dataItem, $requestOptions )
		);
	}

	public function testCreateHtml() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [ DIWikiPage::newFromText( __METHOD__ ) ] ) );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListBuilder( $store );
		$instance->setListLimit( 10 );

		$property = new DIProperty( 'Foo' );
		$dataItem = new DIWikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'title="SMW\Tests\Page\ListBuilder\ListBuilderTest::testCreateHtml'
			],
			$instance->createHtml( $property, $dataItem, $requestOptions )
		);
	}

}
