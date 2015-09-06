<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Query\Language\ClassDescription;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;

	protected function setUp() {
		parent::setUp();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter',
			new ClassDescriptionInterpreter( $querySegmentListBuilder )
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
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$instance = new ClassDescriptionInterpreter(
			new QuerySegmentListBuilder( $store )
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
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
