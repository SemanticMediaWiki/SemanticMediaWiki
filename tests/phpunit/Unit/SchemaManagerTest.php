<?php

namespace SMW\Tests;

use SMW\SchemaManager;

/**
 * @covers \SMW\SchemaManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SchemaManager',
			new SchemaManager( $schemaReader )
		);
	}

	public function testIsSchemaPage() {

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MEDIAWIKI ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertTrue(
			$instance->isSchemaPage( $title )
		);
	}

	public function testContentHandlerDefaultModel() {

		$model = '';

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MEDIAWIKI ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertInternalType(
			'boolean',
			$instance->modifyContentHandlerDefaultModelFor( $title, $model )
		);
	}

	public function testCanNotEditForPropertyFromSchema() {

		$result = '';

		$properties[] = array(
			'property' => 'Foo'
		);

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$schemaReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'properties' ) )
			->will( $this->returnValue( $properties ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertFalse(
			$instance->canEdit( $title, $result )
		);

		$this->assertFalse(
			$result
		);
	}

	public function testCanNotEditForCategoryFromSchema() {

		$result = '';

		$categories[] = array(
			'category' => 'Foo'
		);

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$schemaReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'categories' ) )
			->will( $this->returnValue( $categories ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_CATEGORY ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertFalse(
			$instance->canEdit( $title, $result )
		);

		$this->assertFalse(
			$result
		);
	}

	public function testCanNotDeleteForPropertyFromSchema() {

		$result = '';

		$properties[] = array(
			'property' => 'Foo'
		);

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$schemaReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'properties' ) )
			->will( $this->returnValue( $properties ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertFalse(
			$instance->canDelete( $title, $result )
		);

		$this->assertFalse(
			$result
		);
	}

	public function testCanNotMoveForPropertyFromSchema() {

		$isMovable = '';

		$properties[] = array(
			'property' => 'Foo'
		);

		$schemaReader = $this->getMockBuilder( '\SMW\SchemaReader' )
			->disableOriginalConstructor()
			->getMock();

		$schemaReader->expects( $this->once() )
			->method( 'read' )
			->with( $this->stringContains( 'properties' ) )
			->will( $this->returnValue( $properties ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SchemaManager( $schemaReader );
		$instance->registerSchema( 'Foo' );

		$this->assertTrue(
			$instance->canMove( $title, $isMovable )
		);

		$this->assertFalse(
			$isMovable
		);
	}

}
