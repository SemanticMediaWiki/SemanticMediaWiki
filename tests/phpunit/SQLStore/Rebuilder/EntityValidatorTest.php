<?php

namespace SMW\Tests\SQLStore\Rebuilder;

use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Rebuilder\EntityValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class EntityValidatorTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private NamespaceExaminer $namespaceExaminer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgAutoRefreshSubject' => true,
				'smwgCacheType' => 'hash',
				'smwgEnableUpdateJobs' => false,
			]
		);

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->willReturn( 0 );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
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
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EntityValidator::class,
			new EntityValidator( $store, $this->namespaceExaminer )
		);
	}

	public function testIsDetachedSubobject() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( false );

		$row = (object)[
			'smw_subobject' => 'foo',
			'smw_proptable_hash' => []
		];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 42 );

		$title = $this->getMockBuilder( '\Title' )
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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
