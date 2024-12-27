<?php

namespace SMW\Tests\MediaWiki\Specials\PropertyLabelSimilarity;

use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $propertyLabelSimilarityLookup;
	private $htmlFormRenderer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->propertyLabelSimilarityLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ContentsBuilder::class,
			new ContentsBuilder( $this->propertyLabelSimilarityLookup, $this->htmlFormRenderer )
		);
	}

	public function testGetHtml() {
		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->atLeastOnce() )
			->method( 'getExtraConditions' )
			->willReturn( [ 'type' => 'Foo', 'threshold' => 50 ] );

		$methods = [
			'setName',
			'setMethod',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addSubmitButton',
			'withFieldset',
			'addInputField',
			'addNonBreakingSpace',
			'addCheckbox',
			'addQueryParameter',
			'addPaging'
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new ContentsBuilder(
			$this->propertyLabelSimilarityLookup,
			$this->htmlFormRenderer
		);

		$this->assertIsString(

			$instance->getHtml( $requestOptions )
		);
	}

}
