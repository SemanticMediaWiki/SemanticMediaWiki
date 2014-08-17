<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Util\Validator\QueryContainerValidator;

use SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\ClassDescription;

use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ClassDescriptionCompilerTest extends \PHPUnit_Framework_TestCase {

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = new QueryContainerValidator();
	}

	public function testCanConstruct() {

		$queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler',
			new ClassDescriptionCompiler( $queryBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $pageId, $expected ) {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( $pageId ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$instance = new ClassDescriptionCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	public function descriptionProvider() {

		#0
		$pageId = 42;
		$description = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->components = array( 1 => "t0.o_id" );
		$expected->joinfield = "t0.s_id";

		$provider[] = array(
			$description,
			$pageId,
			$expected
		);

		#1 Empty
		$pageId = 0;
		$description = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );

		$expected = new \stdClass;
		$expected->type = 2;
		$expected->components = array();
		$expected->joinfield = "";

		$provider[] = array(
			$description,
			$pageId,
			$expected
		);

		return $provider;
	}

}
