<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\PropertyLabelSimilarity;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsBuilderTest extends TestCase {

	private $testEnvironment;
	private $propertyLabelSimilarityLookup;
	private $htmlFormRenderer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->propertyLabelSimilarityLookup = $this->getMockBuilder( PropertyLabelSimilarityLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->htmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
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
		$requestOptions = $this->getMockBuilder( RequestOptions::class )
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
