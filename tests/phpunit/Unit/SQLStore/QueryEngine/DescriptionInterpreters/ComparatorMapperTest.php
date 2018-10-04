<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ComparatorMapper;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ComparatorMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ComparatorMapperTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\DescriptionInterpreters\ComparatorMapper',
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

		$provider[] = [ SMW_CMP_EQ,   'Foo%_*?', [ 'comparator' => '=',  'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_LESS, 'Foo%_*?', [ 'comparator' => '<',  'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_GRTR, 'Foo%_*?', [ 'comparator' => '>',  'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_LEQ,  'Foo%_*?', [ 'comparator' => '<=', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_GEQ,  'Foo%_*?', [ 'comparator' => '>=', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_NEQ,  'Foo%_*?', [ 'comparator' => '!=', 'value' => 'Foo%_*?' ] ];

		$provider[] = [ SMW_CMP_LIKE, 'Foo%_*?\\', [ 'comparator' => ' LIKE ',     'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_PRIM_LIKE, 'Foo%_*?\\', [ 'comparator' => ' LIKE ',     'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_NLKE, 'Foo%_*?\\', [ 'comparator' => ' NOT LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_PRIM_NLKE, 'Foo%_*?\\', [ 'comparator' => ' NOT LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];

		return $provider;
	}

}
