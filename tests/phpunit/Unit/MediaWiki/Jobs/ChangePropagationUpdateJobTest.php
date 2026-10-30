<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\EntityCacheFactory;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\MediaWiki\Jobs\ChangePropagationUpdateJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationUpdateJobTest extends TestCase {

	private $testEnvironment;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ChangePropagationUpdateJob::class,
			new ChangePropagationUpdateJob( $title, [], $this->jobFactory )
		);
	}

	/**
	 * @dataProvider jobProvider
	 */
	public function testRun( $subject, $parameters ) {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->once() )
			->method( 'run' );

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$instance = new ChangePropagationUpdateJob(
			$subject->getTitle(),
			$parameters,
			$this->jobFactory
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function testRunSeesASpecificationChangedByAnotherRunnerMomentsAgo() {
		$subject = WikiPage::newFromText( 'Foo' );
		$backend = new HashBagOStuff();
		$entityCache = EntityCacheFactory::newEntityCache( $backend );
		$this->testEnvironment->registerObject( 'EntityCache', $entityCache );

		$key = $entityCache->makeKey( 'specification' );
		$lookUp = static fn ( string $value ) => $entityCache->getWithSetCallback( $subject, $key, 60, static fn () => $value );
		$lookUp( 'old type' );
		EntityCacheFactory::newEntityCache( $backend )->invalidate( $subject );

		$this->runJob( $subject );

		$this->assertSame( 'new type', $lookUp( 'new type' ) );
	}

	public function testRunClearsWhatTheStoreKeepsInMemory() {
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )->method( 'clear' );
		$this->testEnvironment->registerObject( 'Store', $store );

		$this->runJob( WikiPage::newFromText( 'Foo' ) );
	}

	private function runJob( WikiPage $subject ): void {
		$this->jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn( $this->createMock( UpdateJob::class ) );

		( new ChangePropagationUpdateJob( $subject->getTitle(), [], $this->jobFactory ) )->run();
	}

	public function jobProvider() {
		$provider[] = [
			WikiPage::newFromText( __METHOD__ ),
			[]
		];

		return $provider;
	}

}
