<?php

namespace SMW\Tests\Integration\Services;

use JobQueueGroup;
use MediaWiki\Parser\Parser;
use MediaWiki\Revision\RevisionLookup;
use MediaWikiIntegrationTestCase;
use SMW\Importer\ContentCreators\TextContentCreator;
use SMW\Importer\ContentCreators\XmlContentCreator;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\PageCreator;
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
 * @coversNothing
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class BehaviourSensitiveServiceCharacterizationTest extends MediaWikiIntegrationTestCase {

	private ServicesFactory $factory;

	protected function setUp(): void {
		parent::setUp();
		$this->factory = ServicesFactory::getInstance();
	}

	protected function tearDown(): void {
		ServicesFactory::clear();
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
	 * NamespaceExaminer is globalised as `SMW.NamespaceExaminer` on the
	 * MediaWiki ServiceContainer; two retrievals via ServicesFactory return
	 * the same shared instance.
	 */
	public function testNamespaceExaminerTypeAndIdentity(): void {
		$first = $this->factory->getNamespaceExaminer();
		$second = $this->factory->getNamespaceExaminer();

		$this->assertInstanceOf( NamespaceExaminer::class, $first );
		$this->assertSame( $first, $second, 'NamespaceExaminer: globalised as SMW.NamespaceExaminer; both retrievals must return the same instance' );
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
	 * PageCreator is globalised as `SMW.PageCreator` on the MediaWiki
	 * ServiceContainer; ServicesFactory::newPageCreator() returns the same
	 * shared instance on repeated calls.
	 */
	public function testPageCreatorTypeAndIdentity(): void {
		$first = $this->factory->newPageCreator();
		$second = $this->factory->newPageCreator();

		$this->assertInstanceOf( PageCreator::class, $first );
		$this->assertSame( $first, $second, 'PageCreator: globalised as SMW.PageCreator; both retrievals must return the same instance' );
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
	 * ProtectionValidator is a Bucket-A service: all of its configuration setters
	 * run at build time and it holds no per-use mutable state. The audit
	 * (Section 2.6 / Section 3) classifies it as a shared singleton, so the
	 * global ServiceContainer returns the same instance on repeated retrieval.
	 */
	public function testProtectionValidatorTypeAndIdentity(): void {
		$first = $this->factory->create( 'ProtectionValidator' );
		$second = $this->factory->create( 'ProtectionValidator' );

		$this->assertInstanceOf( ProtectionValidator::class, $first );
		$this->assertSame( $first, $second, 'ProtectionValidator: shared singleton resolved via the global ServiceContainer' );
	}

	// -------------------------------------------------------------------------
	// Conservative Bucket C: also create()-reached and behaviour-sensitive
	// -------------------------------------------------------------------------

	/**
	 * ImporterServiceFactory is globalised as `SMW.ImporterServiceFactory` on
	 * the MediaWiki ServiceContainer; two create() retrievals return the same
	 * shared instance.
	 */
	public function testImporterServiceFactoryTypeAndIdentity(): void {
		$first = $this->factory->create( 'ImporterServiceFactory' );
		$second = $this->factory->create( 'ImporterServiceFactory' );

		$this->assertInstanceOf( ImporterServiceFactory::class, $first );
		$this->assertSame( $first, $second, 'ImporterServiceFactory: globalised as SMW.ImporterServiceFactory; both retrievals must return the same instance' );
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
	 * DataValueServiceFactory::getPropertyRestrictionExaminer(), which delegates to
	 * ServicesFactory::singleton('PropertyRestrictionExaminer') and then calls
	 * setUser( RequestContext::getMain()->getUser() ) on the result.
	 *
	 * Pre-migration this returned a shared singleton, so setUser() mutated state
	 * that leaked across call sites (a latent bug). The callback-container
	 * migration classifies PropertyRestrictionExaminer as Bucket C (fresh instance
	 * per call); ServicesFactory now routes the name to newPropertyRestrictionExaminer(),
	 * so each getPropertyRestrictionExaminer() call receives a distinct instance and
	 * the setUser() mutation no longer leaks.
	 */
	public function testPropertyRestrictionExaminerIsFreshInstancePerCall(): void {
		/** @var DataValueServiceFactory $dvFactory */
		$dvFactory = $this->factory->create( 'DataValueServiceFactory' );

		$first = $dvFactory->getPropertyRestrictionExaminer();
		$second = $dvFactory->getPropertyRestrictionExaminer();

		$this->assertInstanceOf( RestrictionExaminer::class, $first );

		// POST-MIGRATION: PropertyRestrictionExaminer is a Bucket-C factory-method
		// service, so each call returns a fresh instance and the setUser() mutation
		// is isolated per caller.
		$this->assertNotSame( $first, $second, 'PropertyRestrictionExaminer: Bucket-C service constructed fresh per call' );
	}

}
