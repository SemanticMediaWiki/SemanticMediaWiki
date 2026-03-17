<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ComparatorMapper;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ComparatorMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ComparatorMapperTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ComparatorMapper::class,
			new ComparatorMapper()
		);
	}

	public function testInvalidComparatorThrowsException() {
		$value = '';

		$valueDescription = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ComparatorMapper();

		$this->expectException( 'RuntimeException' );
		$instance->mapComparator( $valueDescription, $value );
	}

	/**
	 * @dataProvider comparatorProvider
	 */
	public function testSQLComparatorElement( $comparator, $value, $expected ) {
		$valueDescription = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$valueDescription->expects( $this->once() )
			->method( 'getComparator' )
			->willReturn( $comparator );

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
		$provider[] = [ SMW_CMP_EQ, 'Foo%_*?', [ 'comparator' => '=', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_LESS, 'Foo%_*?', [ 'comparator' => '<', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_GRTR, 'Foo%_*?', [ 'comparator' => '>', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_LEQ, 'Foo%_*?', [ 'comparator' => '<=', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_GEQ, 'Foo%_*?', [ 'comparator' => '>=', 'value' => 'Foo%_*?' ] ];
		$provider[] = [ SMW_CMP_NEQ, 'Foo%_*?', [ 'comparator' => '!=', 'value' => 'Foo%_*?' ] ];

		$provider[] = [ SMW_CMP_LIKE, 'Foo%_*?\\', [ 'comparator' => ' LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_PRIM_LIKE, 'Foo%_*?\\', [ 'comparator' => ' LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_NLKE, 'Foo%_*?\\', [ 'comparator' => ' NOT LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];
		$provider[] = [ SMW_CMP_PRIM_NLKE, 'Foo%_*?\\', [ 'comparator' => ' NOT LIKE ', 'value' => 'Foo\%\_%_\\\\' ] ];

		return $provider;
	}

}
