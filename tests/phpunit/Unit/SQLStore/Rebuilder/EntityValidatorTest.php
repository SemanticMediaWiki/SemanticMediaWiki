<?php

namespace SMW\Tests\SQLStore\Rebuilder;

use SMW\ApplicationFactory;
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
class EntityValidatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $titleFactory;
	private $entityValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgSemanticsEnabled' => true,
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
			->setMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( 0 ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
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
			->will( $this->returnValue( false ) );

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
