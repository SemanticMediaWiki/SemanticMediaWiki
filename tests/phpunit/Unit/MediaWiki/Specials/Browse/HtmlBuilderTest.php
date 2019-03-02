<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\SemanticData;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HtmlBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		// Disable a possible active hook execution
		$this->testEnvironment = new TestEnvironment( [
			'smwgEnabledQueryDependencyLinksStore' => false
		] );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new HtmlBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInstanceOf(
			HtmlBuilder::class,
			$instance
		);
	}

	public function testBuildEmptyHTML() {

		$instance = new HtmlBuilder(
			$this->store,
			DIWikiPage::newFromText( 'Foo' )
		);

		$this->assertInternalType(
			'string',
			$instance->buildEmptyHTML()
		);
	}

	public function testBuildHTML() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$this->assertInternalType(
			'string',
			$instance->buildHTML()
		);
	}

	public function testLegacy() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$this->assertInternalType(
			'string',
			$instance->legacy()
		);
	}

	public function testPlaceholder() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$this->assertInternalType(
			'string',
			$instance->placeholder()
		);
	}

	public function testBuildHTMLWithOptions() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( new SemanticData( $subject ) ) );

		$this->store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( [] ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$options = [
			'offset' => 0,
			'showAll' => true,
			'showInverse' => false,
			'dir' => 'both',
			'printable' => ''
		];

		$instance->setOptions(
			$options
		);

		$this->assertInternalType(
			'string',
			$instance->buildHTML()
		);
	}

	public function testOptions() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$options = [
			'Foo' => 42
		];

		$instance->setOptions(
			$options
		);

		$instance->setOption(
			'Bar',
			1001
		);

		$this->assertEquals(
			42,
			$instance->getOption( 'Foo' )
		);

		$this->assertEquals(
			1001,
			$instance->getOption( 'Bar' )
		);
	}

}
