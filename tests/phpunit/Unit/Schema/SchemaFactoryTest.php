<?php

namespace SMW\Tests\Schema;

use SMW\Schema\SchemaFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\SchemaFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$instance = new SchemaFactory();

		$this->assertInstanceof(
			SchemaFactory::class,
			$instance
		);
	}

	public function testCanConstructSchemaValidator() {

		$instance = new SchemaFactory();

		$this->assertInstanceof(
			'\SMW\Schema\SchemaValidator',
			$instance->newSchemaValidator()
		);
	}

	public function testIsRegisteredType() {

		$instance = new SchemaFactory(
			[
				'foo' => []
			]
		);

		$this->assertTrue(
			$instance->isRegisteredType( 'foo' )
		);
	}

	public function testGetRegisteredTypes() {

		$instance = new SchemaFactory(
			[
				'foo' => [],
				'bar' => []
			]
		);

		$this->assertEquals(
			[ 'foo', 'bar' ],
			$instance->getRegisteredTypes()
		);
	}

	public function testGetRegisteredTypesByGroup() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ],
				'bar' => [ 'group' => 'b_group' ]
			]
		);

		$this->assertEquals(
			[ 'foo' ],
			$instance->getRegisteredTypesByGroup( 'f_group' )
		);
	}

	public function testNewSchemaDefinition() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->assertInstanceof(
			'\SMW\Schema\SchemaDefinition',
			$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] )
		);
	}

	public function testNewSchemaDefinitionOnUnknownTypeThrowsException() {

		$instance = new SchemaFactory();

		$this->setExpectedException( '\SMW\Schema\Exception\SchemaTypeNotFoundException' );
		$instance->newSchema( 'foo_bar', [ 'type' => 'foo' ] );
	}

	public function testNewSchemaDefinitionOnNoTypeThrowsException() {

		$instance = new SchemaFactory(
			[
				'foo' => [ 'group' => 'f_group' ]
			]
		);

		$this->setExpectedException( '\SMW\Schema\Exception\SchemaTypeNotFoundException' );
		$instance->newSchema( 'foo_bar', [] );
	}

}
