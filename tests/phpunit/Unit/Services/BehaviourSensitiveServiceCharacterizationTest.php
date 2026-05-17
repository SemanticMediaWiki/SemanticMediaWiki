<?php

namespace SMW\Tests\Unit\Services;

use JobQueueGroup;
use MediaWiki\Parser\Parser;
use MediaWiki\Revision\RevisionLookup;
use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\TitleFactory;
use SMW\NamespaceExaminer;
use SMW\Parser\LinksProcessor;
use SMW\Property\RestrictionExaminer;
use SMW\Protection\ProtectionValidator;
use SMW\Services\DataValueServiceFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Services\ServicesFactory;
use SMW\Utils\TempFile;

/**
 * Characterization tests for behaviour-sensitive services in the callback-container DI layer.
 *
 * These tests lock the CURRENT observable behaviour of services that are reached via
 * `create()` (fresh instance per call) or `singleton()` (shared instance), so that any
 * reclassification during the MediaWiki native container migration is deliberate and
 * reviewed rather than accidental.
 *
 * Each test asserts:
 * (1) the concrete type returned by the current access path, and
 * (2) whether two successive retrievals return the SAME instance (singleton path) or
 *     DISTINCT instances (create() path).
 *
 * @covers \SMW\Services\ServicesFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class BehaviourSensitiveServiceCharacterizationTest extends TestCase {

	private ServicesFactory $factory;

	protected function setUp(): void {
		parent::setUp();
		$this->factory = ServicesFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->factory->clear();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Primary 9: behaviour-sensitive services from the Bucket A candidates
	// -------------------------------------------------------------------------

	/**
	 * JobQueueGroup is reached via create('JobQueueGroup') inside the JobQueue callback.
	 * The callback delegates to MediaWikiServices::getJobQueueGroup() which is a MW singleton,
	 * so two create() calls return the same underlying instance.
	 */
	public function testJobQueueGroupTypeAndIdentity(): void {
		$first = $this->factory->create( 'JobQueueGroup' );
		$second = $this->factory->create( 'JobQueueGroup' );

		$this->assertInstanceOf( JobQueueGroup::class, $first );
		$this->assertSame( $first, $second, 'JobQueueGroup: create() delegates to a MW singleton; both calls must return the same instance' );
	}

	/**
	 * Parser is reached via create('Parser') inside the ContentParser callback.
	 * The callback delegates to MediaWikiServices::getParser() -> ParserFactory::getMainInstance(),
	 * which is a MW-level singleton, so two create() calls return the same Parser instance.
	 */
	public function testParserTypeAndIdentity(): void {
		$first = $this->factory->create( 'Parser' );
		$second = $this->factory->create( 'Parser' );

		$this->assertInstanceOf( Parser::class, $first );
		$this->assertSame( $first, $second, 'Parser: create() delegates to MediaWikiServices::getParser() (MW singleton); both calls must return the same instance' );
	}

	/**
	 * RevisionLookup is reached via create('RevisionLookup') inside the RevisionGuard callback.
	 * The callback delegates to MediaWikiServices::getRevisionLookup() which is a MW singleton,
	 * so two create() calls return the same underlying instance.
	 */
	public function testRevisionLookupTypeAndIdentity(): void {
		$first = $this->factory->create( 'RevisionLookup' );
		$second = $this->factory->create( 'RevisionLookup' );

		$this->assertInstanceOf( RevisionLookup::class, $first );
		$this->assertSame( $first, $second, 'RevisionLookup: create() delegates to a MW singleton; both calls must return the same instance' );
	}

	/**
	 * NamespaceExaminer is reached via ServicesFactory::getNamespaceExaminer() which calls
	 * create('NamespaceExaminer'). The callback constructs a new NamespaceExaminer each time,
	 * so two calls return distinct instances.
	 */
	public function testNamespaceExaminerTypeAndIdentity(): void {
		$first = $this->factory->getNamespaceExaminer();
		$second = $this->factory->getNamespaceExaminer();

		$this->assertInstanceOf( NamespaceExaminer::class, $first );
		$this->assertNotSame( $first, $second, 'NamespaceExaminer: getNamespaceExaminer() calls create() which constructs a new instance each time' );
	}

	/**
	 * LinksProcessor is reached via create('LinksProcessor') in newInTextAnnotationParser().
	 * The callback constructs a new LinksProcessor each time, so two create() calls
	 * return distinct instances.
	 */
	public function testLinksProcessorTypeAndIdentity(): void {
		$first = $this->factory->create( 'LinksProcessor' );
		$second = $this->factory->create( 'LinksProcessor' );

		$this->assertInstanceOf( LinksProcessor::class, $first );
		$this->assertNotSame( $first, $second, 'LinksProcessor: create() constructs a new instance each time' );
	}

	/**
	 * PageCreator is reached via ServicesFactory::newPageCreator() which calls create('PageCreator').
	 * The callback constructs a new PageCreator each time, so two calls return distinct instances.
	 */
	public function testPageCreatorTypeAndIdentity(): void {
		$first = $this->factory->newPageCreator();
		$second = $this->factory->newPageCreator();

		$this->assertInstanceOf( PageCreator::class, $first );
		$this->assertNotSame( $first, $second, 'PageCreator: newPageCreator() calls create() which constructs a new instance each time' );
	}

	/**
	 * TitleFactory is reached via ServicesFactory::newTitleFactory() which calls
	 * create('TitleFactory', ...). The callback constructs a new TitleFactory each time,
	 * so two calls return distinct instances. The PageCreator argument passed by the caller
	 * is ignored by the registered callback.
	 */
	public function testTitleFactoryTypeAndIdentity(): void {
		$first = $this->factory->newTitleFactory();
		$second = $this->factory->newTitleFactory();

		$this->assertInstanceOf( TitleFactory::class, $first );
		$this->assertNotSame( $first, $second, 'TitleFactory: newTitleFactory() calls create() which constructs a new instance each time' );
	}

	/**
	 * JobFactory is reached via ServicesFactory::newJobFactory() which calls create('JobFactory').
	 * The callback constructs a new JobFactory each time, so two calls return distinct instances.
	 */
	public function testJobFactoryTypeAndIdentity(): void {
		$first = $this->factory->newJobFactory();
		$second = $this->factory->newJobFactory();

		$this->assertInstanceOf( JobFactory::class, $first );
		$this->assertNotSame( $first, $second, 'JobFactory: newJobFactory() calls create() which constructs a new instance each time' );
	}

	/**
	 * ProtectionValidator is reached via create('ProtectionValidator') inside the
	 * TitlePermissions callback. The callback constructs a new ProtectionValidator each time,
	 * so two create() calls return distinct instances.
	 */
	public function testProtectionValidatorTypeAndIdentity(): void {
		$first = $this->factory->create( 'ProtectionValidator' );
		$second = $this->factory->create( 'ProtectionValidator' );

		$this->assertInstanceOf( ProtectionValidator::class, $first );
		$this->assertNotSame( $first, $second, 'ProtectionValidator: create() constructs a new instance each time' );
	}

	// -------------------------------------------------------------------------
	// Conservative Bucket C: also create()-reached and behaviour-sensitive
	// -------------------------------------------------------------------------

	/**
	 * ImporterServiceFactory is reached via create('ImporterServiceFactory').
	 * The callback constructs a new ImporterServiceFactory each time, so two create() calls
	 * return distinct instances.
	 */
	public function testImporterServiceFactoryTypeAndIdentity(): void {
		$first = $this->factory->create( 'ImporterServiceFactory' );
		$second = $this->factory->create( 'ImporterServiceFactory' );

		$this->assertInstanceOf( ImporterServiceFactory::class, $first );
		$this->assertNotSame( $first, $second, 'ImporterServiceFactory: create() constructs a new instance each time' );
	}

	/**
	 * XmlContentCreator is reached via create('XmlContentCreator') on the Importer
	 * domain ServicesContainer. The callback constructs a new XmlContentCreator each
	 * time, so two create() calls return distinct instances.
	 */
	public function testXmlContentCreatorTypeAndIdentity(): void {
		$container = ImporterServiceFactory::newServicesContainer(
			$this->factory->getSettings()->get( 'smwgServicesFileDir' )
		);

		$first = $container->create( 'XmlContentCreator', $container );
		$second = $container->create( 'XmlContentCreator', $container );

		$this->assertInstanceOf( XmlContentCreator::class, $first );
		$this->assertNotSame( $first, $second, 'XmlContentCreator: create() constructs a new instance each time' );
	}

	/**
	 * TextContentCreator is reached via create('TextContentCreator') on the Importer
	 * domain ServicesContainer. The callback constructs a new TextContentCreator each
	 * time, so two create() calls return distinct instances.
	 */
	public function testTextContentCreatorTypeAndIdentity(): void {
		$container = ImporterServiceFactory::newServicesContainer(
			$this->factory->getSettings()->get( 'smwgServicesFileDir' )
		);

		$first = $container->create( 'TextContentCreator', $container );
		$second = $container->create( 'TextContentCreator', $container );

		$this->assertInstanceOf( TextContentCreator::class, $first );
		$this->assertNotSame( $first, $second, 'TextContentCreator: create() constructs a new instance each time' );
	}

	/**
	 * TempFile is reached via create('TempFile').
	 * The callback constructs a new TempFile each time, so two create() calls
	 * return distinct instances.
	 */
	public function testTempFileTypeAndIdentity(): void {
		$first = $this->factory->create( 'TempFile' );
		$second = $this->factory->create( 'TempFile' );

		$this->assertInstanceOf( TempFile::class, $first );
		$this->assertNotSame( $first, $second, 'TempFile: create() constructs a new instance each time' );
	}

	// -------------------------------------------------------------------------
	// PropertyRestrictionExaminer: latent bug characterization
	// -------------------------------------------------------------------------

	/**
	 * PropertyRestrictionExaminer is reached via the REAL production path:
	 * DataValueServiceFactory::getPropertyRestrictionExaminer(). That method:
	 *   (a) calls singleton('PropertyRestrictionExaminer') (same shared instance every call), and
	 *   (b) calls setUser( RequestContext::getMain()->getUser() ) on that shared instance every call.
	 *
	 * This is a latent bug: setUser() mutates a shared singleton, so the last caller's user leaks
	 * into the next caller's context.
	 *
	 * This test documents the pre-fix state:
	 * - getPropertyRestrictionExaminer() returns a RestrictionExaminer
	 * - successive calls return the SAME shared instance (singleton path)
	 * - each call to getPropertyRestrictionExaminer() calls setUser() on that shared instance
	 *   (verified via identity: $first === $second, so any setUser() through the production method
	 *   is immediately visible through both references)
	 *
	 * The migration will fix this by switching to create() (fresh instance per call).
	 */
	public function testPropertyRestrictionExaminerIsSingletonWithSetUserMutation(): void {
		/** @var DataValueServiceFactory $dvFactory */
		$dvFactory = $this->factory->create( 'DataValueServiceFactory' );

		// Exercise the real production path twice; each call internally does:
		//   singleton('PropertyRestrictionExaminer') + setUser( RequestContext::getMain()->getUser() )
		$first = $dvFactory->getPropertyRestrictionExaminer();
		$second = $dvFactory->getPropertyRestrictionExaminer();

		$this->assertInstanceOf( RestrictionExaminer::class, $first );

		// PRE-FIX STATE: getPropertyRestrictionExaminer() delegates to singleton(), so both calls
		// return the same shared instance. Because $first === $second, the setUser() called on
		// every getPropertyRestrictionExaminer() invocation mutates the shared instance, so user
		// state leaks across call sites.
		$this->assertSame( $first, $second, 'PropertyRestrictionExaminer: getPropertyRestrictionExaminer() returns the same shared singleton instance (pre-fix state; latent bug)' );
	}

}
