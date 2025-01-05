<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\DIWikiPage;
use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\BrowseBySubject
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class BrowseBySubjectTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $apiFactory;
	private $semanticDataFactory;

	private $applicationFactory;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->apiFactory = $utilityFactory->newMwApiFactory();
		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( [ 'subject' => 'Foo' ] ),
			'browsebysubject'
		);

		$this->assertInstanceOf(
			BrowseBySubject::class,
			$instance
		);
	}

	public function testExecuteForValidSubject() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->testEnvironment->registerObject( 'Store', $store );

		$expectedResultToContainArrayKeys = [
			'error'  => false,
			'result' => true
		];

		$result = $this->apiFactory->doApiRequest( [
			'action'  => 'browsebysubject',
			'subject' => 'Foo'
		] );

		$this->assertToContainArrayKeys(
			$expectedResultToContainArrayKeys,
			$result
		);
	}

	public function testExecuteForInvalidSubjectThrowsException() {
		$this->expectException( interface_exists( 'Throwable' ) ? 'Throwable' : 'Exception' );

		$result = $this->apiFactory->doApiRequest( [
			'action'  => 'browsebysubject',
			'subject' => '{}'
		] );
	}

	public function testRawJsonPrintOutput() {
		$parameters = [ 'subject' => 'Foo', 'subobject' => 'Bar' ];

		$dataItem = new DIWikiPage(
			'Foo',
			NS_MAIN,
			'',
			'Bar'
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( $dataItem );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->with( $dataItem )
			->willReturn( $semanticData );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( $parameters ),
			'browsebysubject'
		);

		// Went away with 1.26/1.27
		if ( function_exists( 'setRawMode' ) ) {
			$instance->getMain()->getResult()->setRawMode();
		}

		$instance->execute();

		$printer = $instance->getMain()->createPrinterByName( 'json' );

		ob_start();
		$printer->initPrinter( false );
		$printer->execute();
		$printer->closePrinter();
		$out = ob_get_clean();

		$this->stringValidator->assertThatStringContains(
			'"subject":"Foo#0##Bar"',
			$out
		);
	}

	public function testHtmlJsonPrintOutput() {
		$parameters = [
			'subject' => 'Foo',
			'subobject' => 'Bar',
			'type' => 'html'
		];

		$dataItem = new DIWikiPage(
			'Foo',
			NS_MAIN,
			'',
			'Bar'
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( $dataItem );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->with( $dataItem )
			->willReturn( $semanticData );

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new BrowseBySubject(
			$this->apiFactory->newApiMain( $parameters ),
			'browsebysubject'
		);

		// Went away with 1.26/1.27
		if ( function_exists( 'setRawMode' ) ) {
			$instance->getMain()->getResult()->setRawMode();
		}

		$instance->execute();

		$printer = $instance->getMain()->createPrinterByName( 'json' );

		ob_start();
		$printer->initPrinter( false );
		$printer->execute();
		$printer->closePrinter();
		$out = ob_get_clean();

		$this->stringValidator->assertThatStringContains(
			'"query":"<div\n\tclass=\"smw-browse\"\n\t\n\t\n>\n\t\n\t\n\t<div class=\"smw-browse-content\">\n\t\t<div class=\"smw-factbox\">\n\t<div class=\"smw-factbox-header mw-sticky-header-element\">',
			$out
		);
	}

	public function assertToContainArrayKeys( $setup, $result ) {
		$this->assertInternalArrayStructure(
			$setup, $result, 'error', 'array', static function ( $r ) { return $r['error'];
			} );

		$this->assertInternalArrayStructure(
			$setup, $result, 'result', 'array', static function ( $r ) { return $r['query'];
			} );

		$this->assertInternalArrayStructure(
			$setup, $result, 'subject', 'string', static function ( $r ) { return $r['query']['subject'];
			} );

		$this->assertInternalArrayStructure(
			$setup, $result, 'data', 'array', static function ( $r ) { return $r['query']['data'];
			} );

		$this->assertInternalArrayStructure(
			$setup, $result, 'sobj', 'array', static function ( $r ) { return $r['query']['sobj'];
			} );
	}

	protected function assertInternalArrayStructure( $setup, $result, $field, $internalType, $definition ) {
		if ( isset( $setup[$field] ) && $setup[$field] ) {

			$this->assertInternalType(
				$internalType,
				is_callable( $definition ) ? $definition( $result ) : $definition
			);
		}
	}

}
