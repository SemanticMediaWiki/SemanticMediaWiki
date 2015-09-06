<?php

namespace SMW\Tests\MediaWiki\Specials\SearchByProperty;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\Specials\SearchByProperty\PageBuilder;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;

use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\DIWikiPage;
use SMW\Localizer;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\PageBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageBuilderTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;
	private $localizer;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
		$this->localizer = Localizer::getInstance();
	}

	public function testCanConstruct() {

		$HtmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$PageRequestOptions = $this->getMockBuilder( '\SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$QueryResultLookup = $this->getMockBuilder( '\SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SearchByProperty\PageBuilder',
			new PageBuilder( $HtmlFormRenderer, $PageRequestOptions, $QueryResultLookup )
		);
	}

	public function testGetHtmlForExactValueSearch() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->will( $this->returnSelf() );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array(
				new DIWikiPage( 'ResultOne', NS_MAIN ),
				new DIWikiPage( 'ResultTwo', NS_HELP ) ) ) );

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', array() ),
			new QueryResultLookup( $store )
		);

		$expected = array(
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNamespaceTextById( NS_HELP ) . ':ResultTwo'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testGetHtmlForNearbyResultsSearch() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->will( $this->returnSelf() );

		$message->expects( $this->any() )
			->method( 'rawParams' )
			->will( $this->returnSelf() );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array(
				new DIWikiPage( 'ResultOne', NS_MAIN ),
				new DIWikiPage( 'ResultTwo', NS_HELP ) ) ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$requestOptions = array(
			'propertyString' => 'Foo',
			'valueString' => 'Bar',
			'nearbySearchForType' => array( '_wpg' )
		);

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', $requestOptions ),
			new QueryResultLookup( $store )
		);

		$expected = array(
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNamespaceTextById( NS_HELP ) . ':ResultTwo'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
