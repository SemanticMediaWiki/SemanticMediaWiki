<?php

namespace SMW\Tests\MediaWiki\Page\ListBuilder;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Page\ListBuilder\ItemListBuilder;
use SMW\RequestOptions;
use SMW\SortLetter;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Page\ListBuilder\ItemListBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ItemListBuilderTest extends TestCase {

	private $store;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ItemListBuilder::class,
			new ItemListBuilder( $this->store )
		);
	}

	public function testCreateEmptyList() {
		$requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ItemListBuilder( $this->store );

		$property = new Property( 'Foo' );
		$dataItem = new WikiPage( 'Bar', NS_MAIN );

		$this->assertSame(
			'',
			$instance->buildHTML( $property, $dataItem, $requestOptions )
		);
	}

	public function testCreateHtml() {
		$sortLetter = $this->getMockBuilder( SortLetter::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertySubjects', 'service' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'service' )
			->with( 'SortLetter' )
			->willReturn( $sortLetter );

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->willReturn( [ WikiPage::newFromText( __METHOD__ ) ] );

		$requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ItemListBuilder( $store );
		$instance->setListLimit( 10 );

		$property = new Property( 'Foo' );
		$dataItem = new WikiPage( 'Bar', NS_MAIN );

		$this->stringValidator->assertThatStringContains(
			[
				'title="SMW\Tests\MediaWiki\Page\ListBuilder\ItemListBuilderTest::testCreateHtml'
			],
			$instance->buildHTML( $property, $dataItem, $requestOptions )
		);
	}

}
