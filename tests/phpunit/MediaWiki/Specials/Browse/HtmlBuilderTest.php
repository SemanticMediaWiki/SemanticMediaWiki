<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\SemanticData;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HtmlBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
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
			->willReturn( [] );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
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

	/**
	 * @dataProvider buildHTMLProvider
	 */
	public function testBuildHTML( $options ) {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( new SemanticData( $subject ) );

		$this->store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( [] );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$instance->setOptions(
			$options
		);

		$this->assertIsString(
			$instance->buildHTML()
		);
	}

	public function buildHTMLProvider(): array {
		return [
			'noOptions' => [
				[
				]
			],
			'basicOptionsShowAll' => [
				[
					'offset' => 0,
					'showAll' => true,
					'showInverse' => false,
					'dir' => 'both',
					'printable' => ''
				]
			],
			'offsetAndPrintableYes' => [
				[
					'offset' => 10,
					'showAll' => false,
					'showInverse' => true,
					'dir' => 'incoming',
					'printable' => 'yes'
				]
			],
			'showInverseEnabled' => [
				[
					'offset' => 5,
					'showAll' => false,
					'showInverse' => true,
					'dir' => 'outgoing',
					'printable' => 'no'
				]
			],
			'differentDirection' => [
				[
					'offset' => 15,
					'showAll' => false,
					'showInverse' => false,
					'dir' => 'incoming',
					'printable' => ''
				]
			],
			'printableEmptyString' => [
				[
					'offset' => 20,
					'showAll' => true,
					'showInverse' => false,
					'dir' => 'both',
					'printable' => ''
				]
			],
			'maximumOffset' => [
				[
					'offset' => 100,
					'showAll' => false,
					'showInverse' => false,
					'dir' => 'both',
					'printable' => 'no'
				]
			],
			'allFeaturesEnabled' => [
				[
					'offset' => 0,
					'showAll' => true,
					'showInverse' => true,
					'dir' => 'both',
					'printable' => 'yes'
				]
			]
		];
	}

	public function testLegacy() {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( new SemanticData( $subject ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$this->assertIsString(

			$instance->legacy()
		);
	}

	public function testGetPlaceholderData() {
		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( new SemanticData( $subject ) );

		$instance = new HtmlBuilder(
			$this->store,
			$subject
		);

		$options = [
			'lang' => 'fr',
			'showAll' => true,
			'printable' => 'no',
			'including' => false
		];

		$instance->setOptions( $options );

		$placeholderData = $instance->getPlaceholderData();

		// Assert that the result is an array
		$this->assertIsArray( $placeholderData );

		// Assert specific keys exist in the result
		$this->assertArrayHasKey( 'subject', $placeholderData );
		$this->assertArrayHasKey( 'options', $placeholderData );
		$this->assertArrayHasKey( 'html-noscript', $placeholderData );
		$this->assertArrayHasKey( 'data-factbox', $placeholderData );

		// Assert that 'subject' is correctly encoded
		$expectedSubject = [
			'dbkey' => $subject->getDBKey(),
			'ns' => $subject->getNamespace(),
			'iw' => $subject->getInterwiki(),
			'subobject' => $subject->getSubobjectName(),
		];
		$this->assertEquals(
			json_encode( $expectedSubject, JSON_UNESCAPED_UNICODE ),
			$placeholderData['subject']
		);

		// Assert that 'options' are correctly encoded
		$this->assertEquals(
			json_encode( $options ),
			$placeholderData['options']
		);

		// Assert that 'html-noscript' contains the noscript link
		// Since Html::errorBox output can be different depending on the MW version and language
		$this->assertStringContainsString(
			'<a rel="nofollow" class="external text" href="https://www.semantic-mediawiki.org/wiki/Help:Noscript">',
			$placeholderData['html-noscript']
		);

		// Assert that 'data-factbox' contains expected structure
		$this->assertArrayHasKey( 'is-loading', $placeholderData['data-factbox'] );
		$this->assertTrue( $placeholderData['data-factbox']['is-loading'] );

		// Check if 'data-form' is present when 'printable' is not 'yes' and 'including' is false
		$this->assertArrayHasKey( 'data-form', $placeholderData );
		// Define the expected keys from getQueryFormData
		$expectedDataFormKeys = [
			'button-value',
			'form-action',
			'form-title',
			'input-placeholder',
			'input-value'
		];
		// Check if all expected keys are present in the data-form array
		foreach ( $expectedDataFormKeys as $key ) {
			$this->assertArrayHasKey( $key, $placeholderData['data-form'] );
		}
	}

}