<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\SQLStore\QueryEngine\Interpreter\ComparatorMapper;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\ComparatorMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ComparatorMapperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\ComparatorMapper',
			new ComparatorMapper()
		);
	}

	public function testInvalidComparatorThrowsException() {

		$value = '';

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ComparatorMapper();

		$this->setExpectedException( 'RuntimeException' );
		$instance->mapComparator( $valueDescription, $value );
	}

	/**
	 * @dataProvider comparatorProvider
	 */
	public function testSQLComparatorElement( $comparator, $value, $expected ) {

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->once() )
			->method( 'getComparator' )
			->will( $this->returnValue( $comparator ) );

		$instance = new ComparatorMapper();

		$this->assertEquals(
			$expected['comparator'],
			$instance->mapComparator( $valueDescription, $value )
		);

		$this->assertEquals(
			$expected['value'],
			$value
		);
	}

	public function comparatorProvider() {

		$provider[] = array( SMW_CMP_EQ,   'Foo%_*?', array( 'comparator' => '=',  'value' => 'Foo%_*?' ) );
		$provider[] = array( SMW_CMP_LESS, 'Foo%_*?', array( 'comparator' => '<',  'value' => 'Foo%_*?' ) );
		$provider[] = array( SMW_CMP_GRTR, 'Foo%_*?', array( 'comparator' => '>',  'value' => 'Foo%_*?' ) );
		$provider[] = array( SMW_CMP_LEQ,  'Foo%_*?', array( 'comparator' => '<=', 'value' => 'Foo%_*?' ) );
		$provider[] = array( SMW_CMP_GEQ,  'Foo%_*?', array( 'comparator' => '>=', 'value' => 'Foo%_*?' ) );
		$provider[] = array( SMW_CMP_NEQ,  'Foo%_*?', array( 'comparator' => '!=', 'value' => 'Foo%_*?' ) );

		$provider[] = array( SMW_CMP_LIKE, 'Foo%_*?\\', array( 'comparator' => ' LIKE ',     'value' => 'Foo\%\_%_\\\\' ) );
		$provider[] = array( SMW_CMP_NLKE, 'Foo%_*?\\', array( 'comparator' => ' NOT LIKE ', 'value' => 'Foo\%\_%_\\\\' ) );

		return $provider;
	}

}
