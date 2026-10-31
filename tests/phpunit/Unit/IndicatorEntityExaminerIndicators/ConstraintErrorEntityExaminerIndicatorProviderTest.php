<?php

namespace SMW\Tests\Unit\IndicatorEntityExaminerIndicators;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Indicator\IndicatorProvider;
use SMW\Localizer\MessageLocalizer;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\EntityCacheFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerIndicatorProviderTest extends TestCase {

	private $store;
	private $errorLookup;
	private $messageLocalizer;
	private $entityCache;
	private $templateParser;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->errorLookup = $this->getMockBuilder( ErrorLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $this->errorLookup );

		$this->entityCache = EntityCacheFactory::newEntityCache();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( 'foo' );

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConstraintErrorEntityExaminerIndicatorProvider::class,
			new ConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache, $this->templateParser )
		);

		$this->assertInstanceOf(
			IndicatorProvider::class,
			new ConstraintErrorEntityExaminerIndicatorProvider( $this->store, $this->entityCache, $this->templateParser )
		);
	}

	public function testGetName() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testGetIndicators() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_DisabledCheck() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$instance->setConstraintErrorCheck(
			false
		);

		$this->assertFalse(
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_FromErrorLookup() {
		$this->errorLookup->expects( $this->once() )
			->method( 'buildArray' )
			->willReturn( [ 'Foo' ] );

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_FromErrorLookup_NoErrors() {
		$this->errorLookup->expects( $this->once() )
			->method( 'buildArray' )
			->willReturn( [] );

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->hasIndicator( $subject, [] );

		$this->assertEquals(
			[
				'id' => 'smw-entity-examiner-deferred-constraint-error',
				'content' => ''
			],
			$instance->getIndicators()
		);
	}

	/**
	 * @dataProvider errorsProvider
	 */
	public function testErrorsOfAPageAreLookedUpOnlyOnce( array $errors ) {
		$this->errorLookup->expects( $this->once() )
			->method( 'findErrorsByType' );

		$this->errorLookup->expects( $this->any() )
			->method( 'buildArray' )
			->willReturn( $errors );

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			$this->entityCache,
			$this->templateParser
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->hasIndicator( $subject, [] );
		$instance->hasIndicator( $subject, [] );
	}

	public function errorsProvider(): iterable {
		yield 'with errors' => [ [ 'Foo' ] ];
		yield 'without errors' => [ [] ];
	}

	public function testErrorsAreCachedPerPage() {
		$this->errorLookup->expects( $this->exactly( 2 ) )
			->method( 'findErrorsByType' )
			->willReturnCallback( static fn ( $type, WikiPage $subject ) => $subject->getDBkey() );

		$this->errorLookup->expects( $this->any() )
			->method( 'buildArray' )
			->willReturnCallback( static fn ( $res ) => $res === 'Foo' ? [ '["An error on Foo"]' ] : [] );

		$backend = new HashBagOStuff();
		$this->newProvider( $backend )->hasIndicator( WikiPage::newFromText( 'Foo' ), [] );
		$this->newProvider( $backend )->hasIndicator( WikiPage::newFromText( 'Bar' ), [] );
		$this->newProvider( $backend )->hasIndicator( WikiPage::newFromText( 'Foo' ), [] );

		$instance = $this->newProvider( $backend );
		$instance->hasIndicator( WikiPage::newFromText( 'Bar' ), [] );

		$this->assertSame( '', $instance->getIndicators()['content'] );
	}

	/**
	 * A provider of its own process, over a backend shared with other processes
	 */
	private function newProvider( BagOStuff $backend ): ConstraintErrorEntityExaminerIndicatorProvider {
		$instance = new ConstraintErrorEntityExaminerIndicatorProvider(
			$this->store,
			EntityCacheFactory::newEntityCache( $backend ),
			$this->templateParser
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		return $instance;
	}

}
