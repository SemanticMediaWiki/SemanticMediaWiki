<?php

namespace SMW\Tests\MediaWiki;

use Language;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use Parser;
use Psr\Log\LoggerInterface;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\MediaWiki\DeepRedirectTargetResolver;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MessageBuilder;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageInfoProvider;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\MediaWiki\Renderer\HtmlColumnListRenderer;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Renderer\HtmlTableRenderer;
use SMW\MediaWiki\Renderer\HtmlTemplateRenderer;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\MediaWiki\RevisionGuard;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use Title;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\MwCollaboratorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class MwCollaboratorFactoryTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	public function setUp() : void {
		parent::setUp();

		$this->applicationFactory = $this->getMockBuilder( ApplicationFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MwCollaboratorFactory::class,
			new MwCollaboratorFactory( $this->applicationFactory )
		);
	}

	public function testCanConstructMessageBuilder() {

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			MessageBuilder::class,
			$instance->newMessageBuilder()
		);

		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			MessageBuilder::class,
			$instance->newMessageBuilder( $language )
		);
	}

	public function testCanConstructMagicWordsFinder() {

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			MagicWordsFinder::class,
			$instance->newMagicWordsFinder()
		);
	}

	public function testCanConstructRedirectTargetFinder() {

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			RedirectTargetFinder::class,
			$instance->newRedirectTargetFinder()
		);
	}

	public function testCanConstructDeepRedirectTargetResolver() {

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'newPageCreator' )
			->will( $this->returnValue( $pageCreator ) );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			DeepRedirectTargetResolver::class,
			$instance->newDeepRedirectTargetResolver()
		);
	}

	public function testCanConstructHtmlFormRenderer() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlFormRenderer::class,
			$instance->newHtmlFormRenderer( $title )
		);

		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlFormRenderer::class,
			$instance->newHtmlFormRenderer( $title, $language )
		);
	}

	public function testCanConstructHtmlTableRenderer() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			HtmlTableRenderer::class,
			$instance->newHtmlTableRenderer()
		);
	}

	public function testCanConstructHtmlColumnListRenderer() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			HtmlColumnListRenderer::class,
			$instance->newHtmlColumnListRenderer()
		);
	}

	public function testCanConstructLoadBalancerConnectionProvider() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			LoadBalancerConnectionProvider::class,
			$instance->newLoadBalancerConnectionProvider( DB_REPLICA )
		);
	}

	public function testCanConstructConnectionProvider() {

		$settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$logger = $this->getMockBuilder( LoggerInterface::class )
			->disableOriginalConstructor()
			->getMock();

		$settings->expects( $this->atLeastOnce() )
			->method( 'get' )
			->will( $this->returnValue( [] ) );

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'getSettings' )
			->will( $this->returnValue( $settings ) );

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'getMediaWikiLogger' )
			->will( $this->returnValue( $logger ) );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			ConnectionProvider::class,
			$instance->newConnectionProvider()
		);
	}

	public function testCanConstructPageInfoProvider() {

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionLookup = $this->getMockBuilder( RevisionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->exactly( 2 ) )
			->method( 'singleton' )
			->withConsecutive(
				[ $this->equalTo( 'RevisionGuard' ) ],
				[ $this->equalTo( 'RevisionLookup' ) ],
			)
			->will( $this->onConsecutiveCalls( $revisionGuard, $revisionLookup ) );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			PageInfoProvider::class,
			$instance->newPageInfoProvider( $wikiPage )
		);
	}

	public function testCanConstructEditInfo() {

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->equalTo( 'RevisionGuard' ) )
			->will( $this->returnValue( $revisionGuard ) );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			EditInfo::class,
			$instance->newEditInfo( $wikiPage, $revision )
		);
	}

	public function testCanConstructWikitextTemplateRenderer() {

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			WikitextTemplateRenderer::class,
			$instance->newWikitextTemplateRenderer()
		);
	}

	public function testCanConstructHtmlTemplateRenderer() {

		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			HtmlTemplateRenderer::class,
			$instance->newHtmlTemplateRenderer( $parser )
		);
	}

	public function testCanConstructMediaWikiNsContentReader() {

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'create' )
			->with( $this->equalTo( 'MediaWikiNsContentReader' ) )
			->will( $this->returnValue( $mediaWikiNsContentReader ) );

		$this->assertInstanceOf(
			MediaWikiNsContentReader::class,
			$instance->newMediaWikiNsContentReader()
		);
	}

}
