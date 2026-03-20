<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\SearchByProperty;

use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\MessageBuilder;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\SearchByProperty\PageBuilder;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\Query\QueryResult;
use SMW\Store;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\PageBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class PageBuilderTest extends TestCase {

	private $stringValidator;
	private $localizer;

	protected function setUp(): void {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
		$this->localizer = Localizer::getInstance();
	}

	public function testCanConstruct() {
		$HtmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$PageRequestOptions = $this->getMockBuilder( PageRequestOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$QueryResultLookup = $this->getMockBuilder( QueryResultLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PageBuilder::class,
			new PageBuilder( $HtmlFormRenderer, $PageRequestOptions, $QueryResultLookup )
		);
	}

	public function testGetHtmlForExactValueSearch() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->willReturnSelf();

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [
				new WikiPage( 'ResultOne', NS_MAIN ),
				new WikiPage( 'ResultTwo', NS_HELP ) ] );

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', [] ),
			new QueryResultLookup( $store )
		);

		$expected = [
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNsText( NS_HELP ) . ':ResultTwo'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testGetHtmlForNearbyResultsSearch() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( Message::class )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->willReturnSelf();

		$message->expects( $this->any() )
			->method( 'rawParams' )
			->willReturnSelf();

		$messageBuilder = $this->getMockBuilder( MessageBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->willReturn( false );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [
				new WikiPage( 'ResultOne', NS_MAIN ),
				new WikiPage( 'ResultTwo', NS_HELP ) ] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$requestOptions = [
			'propertyString' => 'Foo',
			'valueString' => 'Bar',
			'nearbySearchForType' => [ '_wpg' ]
		];

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', $requestOptions ),
			new QueryResultLookup( $store )
		);

		$expected = [
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNsText( NS_HELP ) . ':ResultTwo'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
