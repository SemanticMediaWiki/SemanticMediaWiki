<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Revision\RevisionLookup;
use SMW\MediaWiki\RevisionGuard;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\MediaWiki\MwCollaboratorFactory;

/**
 * @covers \SMW\MediaWiki\MwCollaboratorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class MwCollaboratorFactoryTest extends \PHPUnit\Framework\TestCase {

	private $applicationFactory;

	public function setUp(): void {
		parent::setUp();

		$this->applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\MwCollaboratorFactory',
			new MwCollaboratorFactory( $this->applicationFactory )
		);
	}

	public function testCanConstructMessageBuilder() {
		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$instance->newMessageBuilder()
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$instance->newMessageBuilder( $language )
		);
	}

	public function testCanConstructMagicWordsFinder() {
		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordsFinder',
			$instance->newMagicWordsFinder()
		);
	}

	public function testCanConstructRedirectTargetFinder() {
		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\RedirectTargetFinder',
			$instance->newRedirectTargetFinder()
		);
	}

	public function testCanConstructDeepRedirectTargetResolver() {
		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'newPageCreator' )
			->willReturn( $pageCreator );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\DeepRedirectTargetResolver',
			$instance->newDeepRedirectTargetResolver()
		);
	}

	public function testCanConstructHtmlFormRenderer() {
		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlFormRenderer',
			$instance->newHtmlFormRenderer( $title )
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlFormRenderer',
			$instance->newHtmlFormRenderer( $title, $language )
		);
	}

	public function testCanConstructHtmlTableRenderer() {
		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlTableRenderer',
			$instance->newHtmlTableRenderer()
		);
	}

	public function testCanConstructHtmlColumnListRenderer() {
		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlColumnListRenderer',
			$instance->newHtmlColumnListRenderer()
		);
	}

	public function testCanConstructLoadBalancerConnectionProvider() {
		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Connection\LoadBalancerConnectionProvider',
			$instance->newLoadBalancerConnectionProvider( DB_REPLICA )
		);
	}

	public function testCanConstructConnectionProvider() {
		$settings = $this->getMockBuilder( '\SMW\Settings' )
			->disableOriginalConstructor()
			->getMock();

		$logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$settings->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( [] );

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'getSettings' )
			->willReturn( $settings );

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'getMediaWikiLogger' )
			->willReturn( $logger );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Connection\ConnectionProvider',
			$instance->newConnectionProvider()
		);
	}

	public function testCanConstructPageInfoProvider() {
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->any() )
			->method( 'singleton' )
			->willReturnMap( [
				[ 'RevisionGuard', $this->createMock( RevisionGuard::class ) ],
				[ 'RevisionLookup', $this->createMock( RevisionLookup::class ) ]
			] );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageInfoProvider',
			$instance->newPageInfoProvider( $wikiPage )
		);
	}

	public function testCanConstructEditInfo() {
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( 'RevisionGuard' )
			->willReturn( $revisionGuard );

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\EditInfo',
			$instance->newEditInfo( $wikiPage, $revision )
		);
	}

	public function testCanConstructWikitextTemplateRenderer() {
		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\WikitextTemplateRenderer',
			$instance->newWikitextTemplateRenderer()
		);
	}

	public function testCanConstructHtmlTemplateRenderer() {
		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlTemplateRenderer',
			$instance->newHtmlTemplateRenderer( $parser )
		);
	}

	public function testCanConstructMediaWikiNsContentReader() {
		$instance = new MwCollaboratorFactory(
			$this->applicationFactory
		);

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory->expects( $this->atLeastOnce() )
			->method( 'create' )
			->with( 'MediaWikiNsContentReader' )
			->willReturn( $mediaWikiNsContentReader );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MediaWikiNsContentReader',
			$instance->newMediaWikiNsContentReader()
		);
	}

}
