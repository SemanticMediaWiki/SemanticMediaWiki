<?php

namespace SMW\Tests\Util\Validator;

use SMW\SQLStore\QueryEngine\QueryContainer;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class QueryContainerValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @since 2.1
	 *
	 * @param  mixed $expected
	 * @param  QueryContainer[] $queryContainer
	 */
	public function assertThatContainerContains( $expected, array $queryContainer ) {

		$expected = is_array( $expected ) ? $expected : array( $expected );

		$this->assertEquals( count( $expected ), count( $queryContainer ) );

		foreach ( $queryContainer as $key => $container ) {
			$this->assertInstanceOf(
				'\SMW\SQLStore\QueryEngine\QueryContainer',
				$container
			);

			$this->assertThatContainerHasProperties( $expected[ $key ], $container );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param  mixed $expected
	 * @param  QueryContainer $queryContainer
	 */
	public function assertThatContainerHasProperties( $expected, QueryContainer $queryContainer ) {

		$typeCondition = true;
		$whereCondition = true;
		$componentsCondition = true;

		if ( isset( $expected->type ) ) {
			$typeCondition = $expected->type == $queryContainer->type;
		}

		if ( isset( $expected->where ) ) {
			$whereCondition = $expected->where == $queryContainer->where;
		}

		if ( isset( $expected->components ) ) {
			$componentsCondition = $expected->components == $queryContainer->components;
		}

		$this->assertTrue( $typeCondition );
		$this->assertTrue( $whereCondition );
		$this->assertTrue( $componentsCondition );
	}

}
