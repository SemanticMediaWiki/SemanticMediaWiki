<?php

namespace SMW\Tests\MediaWiki\Specials\PropertyLabelSimilarity;

use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $propertyLabelSimilarityLookup;
	private $htmlFormRenderer;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->propertyLabelSimilarityLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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
			->will( $this->returnValue( [ 'type' => 'Foo', 'threshold' => 50 ] ) );

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
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new ContentsBuilder(
			$this->propertyLabelSimilarityLookup,
			$this->htmlFormRenderer
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml( $requestOptions )
		);
	}

}
