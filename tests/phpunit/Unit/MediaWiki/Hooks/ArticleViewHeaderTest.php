<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleViewHeader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleViewHeaderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $dependencyValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->dependencyValidator = $this->getMockBuilder( '\SMW\DependencyValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ArticleViewHeader::class,
			new ArticleViewHeader( $this->store, $this->dependencyValidator )
		);
	}

	public function testProcessOnCategory() {

		$subject = DIWikiPage::newFromText( __METHOD__, NS_CATEGORY );
		$property = new DIProperty( DIProperty::TYPE_CHANGE_PROP );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'hasProperty' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( true ) );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->once() )
			->method( 'addHtml' );

		$context = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$page->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );

		$this->assertFalse(
			$useParserCache
		);
	}

	public function testProcessOnNoCategory() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$page->expects( $this->never() )
			->method( 'getContext' );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );
	}

	public function testHasArchaicDependency() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->dependencyValidator->expects( $this->any() )
			->method( 'hasArchaicDependencies' )
			->will( $this->returnValue( true ) );

		$title = $subject->getTitle();

		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$page->expects( $this->never() )
			->method( 'getContext' );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );

		$this->assertFalse(
			$useParserCache
		);
	}

}
