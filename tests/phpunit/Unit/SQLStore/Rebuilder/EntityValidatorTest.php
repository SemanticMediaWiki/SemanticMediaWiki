<?php

namespace SMW\Tests\Unit\SQLStore\Rebuilder;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Rebuilder\EntityValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class EntityValidatorTest extends TestCase {

	private $testEnvironment;
	private NamespaceExaminer $namespaceExaminer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgAutoRefreshSubject' => true,
				'smwgMainCacheType' => 'hash',
				'smwgEnableUpdateJobs' => false,
			]
		);

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->willReturn( 0 );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EntityValidator::class,
			new EntityValidator( $store, $this->namespaceExaminer )
		);
	}

	public function testIsDetachedSubobject() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( false );

		$row = (object)[
			'smw_subobject' => 'foo',
			'smw_proptable_hash' => []
		];

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityValidator(
			$store,
			$this->namespaceExaminer
		);

		$this->assertTrue(
			$instance->isDetachedSubobject( $title, $row )
		);
	}

	public function testHasLatestRevID() {
		$revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 42 );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( false );

		$row = (object)[
			'smw_subobject' => 'foo',
			'smw_proptable_hash' => [],
			'smw_rev' => 42
		];

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityValidator(
			$store,
			$this->namespaceExaminer
		);

		$instance->setRevisionGuard(
			$revisionGuard
		);

		$this->assertTrue(
			$instance->hasLatestRevID( $title, $row )
		);
	}

	public function testIsDetachedQueryRef() {
		$row = (object)[
			'smw_subobject' => '_QUERY-Foo',
			'smw_proptable_hash' => null
		];

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityValidator(
			$store,
			$this->namespaceExaminer
		);

		$this->assertTrue(
			$instance->isDetachedQueryRef( $row )
		);
	}

	/**
	 * @dataProvider propertyRetiredListProvider
	 */
	public function testIsRetiredProperty( $row, $list, $expected ) {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityValidator(
			$store,
			$this->namespaceExaminer
		);

		$instance->setPropertyRetiredList(
			$list
		);

		$this->assertEquals(
			$expected,
			$instance->isRetiredProperty( $row )
		);
	}

	public function propertyRetiredListProvider() {
		yield [
			(object)[ 'smw_namespace' => SMW_NS_PROPERTY, 'smw_title' => 'Test_SD_Some' ],
			[ '_SD_' ],
			false,
		];

		yield [
			(object)[ 'smw_namespace' => SMW_NS_PROPERTY, 'smw_title' => '_SD_Some' ],
			[ '_SD_' ],
			true,
		];
	}

}
