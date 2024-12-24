<?php

namespace SMW\Tests;

use MediaWiki\Content\Renderer\ContentRenderer;
use MediaWiki\MediaWikiServices;
use SMW\ContentParser;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\ContentParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ContentParserTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $revisionGuard;
	private $title;
	private $parser;
	private $parserOutput;

	private TestEnvironment $testEnvironment;

	/**
	 * @var ?ContentRenderer
	 */
	private $contentRenderer = null;

	protected function setUp(): void {
		parent::setUp();

		$this->revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();

		$this->contentRenderer = MediaWikiServices::getInstance()->getContentRenderer();
	}

	protected function tearDown(): void {
		if ( $this->contentRenderer !== null ) {
			$this->testEnvironment->redefineMediaWikiService( 'ContentRenderer', fn () => $this->contentRenderer );
		}
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ContentParser::class,
			new ContentParser( $this->title, $this->parser )
		);
	}

	public function testRunParseOnText() {
		$text = __METHOD__;

		$this->parser->expects( $this->any() )
			->method( 'parse' )
			->with( $this->stringContains( $text ) )
			->willReturn( $this->parserOutput );

		$instance = new ContentParser(
			$this->title,
			$this->parser
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->parse( $text );

		$this->assertInstanceOf(
			'\ParserOutput',
			$instance->getOutput()
		);
	}

	public function testRunParseFromRevision() {
		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->redefineMediaWikiService( 'ContentRenderer', function () {
			$contentRenderer = $this->getMockBuilder( '\MediaWiki\Content\Renderer\ContentRenderer' )
				->disableOriginalConstructor()
				->getMock();

			$contentRenderer->expects( $this->any() )
				->method( 'getParserOutput' )
				->willReturn( $this->parserOutput );

			return $contentRenderer;
		} );

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( $content );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $revision );

		$instance = new ContentParser(
			$this->title,
			$this->parser
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->parse();

		$this->assertInstanceOf(
			'\ParserOutput',
			$instance->getOutput()
		);
	}

}
