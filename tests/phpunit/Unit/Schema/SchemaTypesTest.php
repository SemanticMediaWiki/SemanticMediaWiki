<?php

namespace SMW\Tests\Schema;

use SMW\Schema\SchemaTypes;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Schema\SchemaTypes
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaTypesTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $hookDispatcher;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new SchemaTypes();

		$this->assertInstanceof(
			SchemaTypes::class,
			$instance
		);
	}

	public function testRegisterSchemaTypes() {

		$this->hookDispatcher->expects( $this->once() )
			->method( 'onRegisterSchemaTypes' );

		$instance = new SchemaTypes();
		$instance->setHookDispatcher( $this->hookDispatcher );

		$instance->registerSchemaTypes( [] );
	}

	public function testRegisterSchemaType() {

		$instance = new SchemaTypes();

		$instance->registerSchemaType( 'Foo', [] );

		$this->assertTrue(
			$instance->isRegisteredType( 'Foo' )
		);
	}

	public function testGetRegisteredTypes() {

		$instance = new SchemaTypes();

		$instance->registerSchemaType( 'Foo', [] );
		$instance->registerSchemaType( 'Bar', [ 'bar' => 123 ] );

		$this->assertEquals(
			[ 'Foo', 'Bar' ],
			$instance->getRegisteredTypes()
		);
	}

	public function testGetRegisteredTypesByGroup() {

		$instance = new SchemaTypes();

		$instance->registerSchemaType( 'Foo', [ 'group' => 'foo_bar' ] );
		$instance->registerSchemaType( 'Bar', [ 'bar' => 123, 'group' => 'foobar' ] );

		$this->assertEquals(
			[ 'Bar' ],
			$instance->getRegisteredTypesByGroup( 'foobar' )
		);
	}

	public function testGetType() {

		$instance = new SchemaTypes();

		$this->assertEquals(
			[],
			$instance->getType( 'Foo' )
		);

		$instance->registerSchemaType( 'Foo', [ 'bar' => 123 ] );

		$this->assertEquals(
			[ 'bar' => 123 ],
			$instance->getType( 'Foo' )
		);
	}

	public function testWithDir() {

		$instance = new SchemaTypes( [], __DIR__ );

		$this->assertEquals(
			__DIR__ . '/Foo',
			$instance->withDir( 'Foo' )
		);
	}

	public function testRegisterKnownSchemaType_ThrowsException() {

		$instance = new SchemaTypes();

		$instance->registerSchemaType( 'Foo', [] );

		$this->expectException( '\SMW\Schema\Exception\SchemaTypeAlreadyExistsException' );
		$instance->registerSchemaType( 'Foo', [] );
	}

}
