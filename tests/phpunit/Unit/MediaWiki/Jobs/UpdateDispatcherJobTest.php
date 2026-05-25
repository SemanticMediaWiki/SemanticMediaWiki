<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateDispatcherJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJobTest extends TestCase {

	protected $expectedProperty;
	protected $expectedSubjects;
	private $semanticDataSerializer;
	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataSerializer = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();

		// SerializerFactory is still resolved through ApplicationFactory by the
		// job under test; configure the environment with default settings.
		$this->testEnvironment = new TestEnvironment( [
			'smwgMainCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		] );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newStore(): Store {
		return $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			UpdateDispatcherJob::class,
			new UpdateDispatcherJob( $title, [], $this->newStore() )
		);
	}

	public function testPushToJobQueue() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, [], $this->newStore() );
		$instance->isEnabledJobQueue( false );

		$this->assertNull( $instance->pushToJobQueue() );
	}

	public function testChunkedJobWithListOnValidMembers() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, [
			'job-list' => [
				'Foo#0##' => true,
				'Bar#102##'
			]
		], $this->newStore() );

		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			2,
			$instance->getJobCount()
		);
	}

	public function testChunkedJobWithListOnInvalidMembers() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UpdateDispatcherJob( $title, [
			'job-list' => [
				'|nulltitle#0##' => true,
				'deserlizeerror#0' => true
			]
		], $this->newStore() );

		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertSame(
			0,
			$instance->getJobCount()
		);
	}

	public function testJobRunOnMainNamespace() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, NS_MAIN );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'getProperties',
				'getInProperties' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( [] );

		$instance = new UpdateDispatcherJob( $title, [], $store );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobRunOnPropertyNamespace() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, SMW_NS_PROPERTY );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'getProperties',
				'getInProperties',
				'getAllPropertySubjects',
				'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new UpdateDispatcherJob( $title, [], $store );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobRunOnRestrictedPool() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );
		$subject = WikiPage::newFromText( 'Foo' );

		$semanticData = new SemanticData( $subject );
		$semanticData->addPropertyObjectValue( new Property( '42' ), $subject );

		$parameters = [
			'semanticData' => $this->semanticDataSerializer->serialize( $semanticData ),
			UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL => true
		];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'getAllPropertySubjects',
				] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->willReturn( [] );

		$instance = new UpdateDispatcherJob( $title, $parameters, $store );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue(
			$instance->run()
		);
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithOutParameters( $setup, $expected ) {
		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'getAllPropertySubjects',
				'getPropertyValues',
				'getProperties',
				'getInProperties',
				'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->willReturnCallback( [ $this, 'mockStoreAllPropertySubjectsCallback' ] );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromTitle( $setup['title'] ) ] );

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( $setup['properties'] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( $setup['properties'] );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new UpdateDispatcherJob( $setup['title'], $setup['parameters'], $store );
		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			$expected['count'],
			$instance->getJobCount()
		);
	}

	/**
	 * @dataProvider subjectDataProvider
	 */
	public function testRunJobOnMockWithParameters( $setup, $expected ) {
		$semanticData = new SemanticData(
			WikiPage::newFromTitle( $setup['title'] )
		);

		$parameters = [
			'semanticData' => $this->semanticDataSerializer->serialize( $semanticData )
		] + $setup['parameters'];

		$this->expectedProperty = $setup['property'];
		$this->expectedSubjects = $setup['subjects'];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'getAllPropertySubjects',
				'getPropertyValues',
				'getProperties',
				'getInProperties',
				'getPropertySubjects' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getAllPropertySubjects' )
			->willReturnCallback( [ $this, 'mockStoreAllPropertySubjectsCallback' ] );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromTitle( $setup['title'] ) ] );

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( $setup['properties'] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( $setup['properties'] );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new UpdateDispatcherJob( $setup['title'], $parameters, $store );
		$instance->isEnabledJobQueue( false );
		$instance->run();

		$this->assertEquals(
			$expected['count'],
			$instance->getJobCount()
		);
	}

	public function subjectDataProvider() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$provider = [];

		$duplicate = WikiPage::newFromText( 'Foo' );

		$subjects = [
			$duplicate,
			WikiPage::newFromText( 'Bar' ),
			WikiPage::newFromText( 'Baz' ),
			$duplicate,
			WikiPage::newFromText( 'Yon' ),
			WikiPage::newFromText( 'Yon' ),
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		];

		$count = count( $subjects ) - 1; // eliminate duplicate count
		$title = $titleFactory->newFromText( __METHOD__, SMW_NS_PROPERTY );
		$property = Property::newFromUserLabel( $title->getText() );

		# 0
		$provider[] = [
			[
				'title'      => $title,
				'subjects'   => $subjects,
				'property'   => $property,
				'properties' => [],
				'parameters' => []
			],
			[
				'count' => 6
			]
		];

		$title = $titleFactory->newFromText( __METHOD__, NS_MAIN );
		$property = Property::newFromUserLabel( $title->getText() );

		# 1
		$provider[] = [
			[
				'title'      => $title,
				'subjects'   => [ WikiPage::newFromTitle( $title ) ],
				'property'   => $property,
				'properties' => [ $property ],
				'parameters' => []
			],
			[
				'count' => 1
			]
		];

		# 2
		$duplicate = WikiPage::newFromText( 'Foo' );

		$subjects = [
			$duplicate,
			WikiPage::newFromText( 'Bar' ),
			WikiPage::newFromText( 'Baz' ),
			$duplicate,
			WikiPage::newFromText( 'Yon' ),
			WikiPage::newFromText( 'Yon' ),
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		];

		$title = $titleFactory->newFromText( __METHOD__, SMW_NS_PROPERTY );
		$property = Property::newFromUserLabel( $title->getText() );

		$provider[] = [
			[
				'title'      => $title,
				'subjects'   => $subjects,
				'property'   => $property,
				'properties' => [],
				'parameters' => [ UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL => true ]
			],
			[
				'count' => 6
			]
		];

		return $provider;
	}

	public function testIdOnlyInvocationProducesNoSecondaryDispatchJobs() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText(
			__METHOD__, NS_MAIN
		);

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [
				'getProperties',
				'getInProperties',
			] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->willReturn( [] );

		$reflector = new ReflectionClass( UpdateDispatcherJob::class );
		$jobsProp = $reflector->getProperty( 'jobs' );
		$jobsProp->setAccessible( true );

		// Unrestricted dispatch
		$unrestricted = new UpdateDispatcherJob(
			$title,
			[
				'_id' => 12345,
			],
			$store
		);
		$unrestricted->isEnabledJobQueue( false );
		$this->assertTrue( $unrestricted->run() );
		$this->assertSame( [], $jobsProp->getValue( $unrestricted ) );

		// Restricted dispatch (mirrors the ArticleDelete production call site)
		$restricted = new UpdateDispatcherJob(
			$title,
			[
				'_id' => 12345,
				UpdateDispatcherJob::RESTRICTED_DISPATCH_POOL => true,
			],
			$store
		);
		$restricted->isEnabledJobQueue( false );
		$this->assertTrue( $restricted->run() );
		$this->assertSame( [], $jobsProp->getValue( $restricted ) );
	}

	/**
	 * Returns an array of DIWikiPage objects if the expected property
	 * and the argument property are identical
	 *
	 * @see Store::getAllPropertySubjects
	 *
	 * @return WikiPage[]
	 */
	public function mockStoreAllPropertySubjectsCallback( Property $property ) {
		return $this->expectedSubjects;
	}

}
