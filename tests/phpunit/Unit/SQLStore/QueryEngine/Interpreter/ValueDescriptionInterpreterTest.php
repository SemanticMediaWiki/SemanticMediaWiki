<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;

use SMW\Query\Language\ValueDescription;

use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ValueDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter',
			new ValueDescriptionInterpreter( $querySegmentListBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $expected ) {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$instance = new ValueDescriptionInterpreter( new QuerySegmentListBuilder( $store ) );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function descriptionProvider() {

		#0 SMW_CMP_EQ
		$description = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ );

		$expected = new \stdClass;
		$expected->type = 2;
		$expected->alias = "t0";
		$expected->joinfield = array( 42 );

		$provider[] = array(
			$description,
			$expected
		);

		#1 SMW_CMP_LEQ
		$description = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_LEQ );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->alias = "t0";
		$expected->joinfield = "t0.smw_id";
		$expected->where = "t0.smw_sortkey<=Foo";

		$provider[] = array(
			$description,
			$expected
		);

		#2 SMW_CMP_LIKE
		$description = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_LIKE );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->alias = "t0";
		$expected->joinfield = "t0.smw_id";
		$expected->where = "t0.smw_sortkey LIKE Foo";

		$provider[] = array(
			$description,
			$expected
		);

		#3 not a DIWikiPage
		$description = new ValueDescription( new DIBLob( 'Foo' ) );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = "";
		$expected->where = "";

		$provider[] = array(
			$description,
			$expected
		);

		return $provider;
	}

}
