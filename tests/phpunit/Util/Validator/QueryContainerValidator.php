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

		$this->assertEquals(
			count( $expected ),
			count( $queryContainer ),
			$this->formatMessage( 'container count', count( $expected ), count( $queryContainer ) )
		);

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
		$this->assertPublicProperty( $expected, $queryContainer, 'type' );
		$this->assertPublicProperty( $expected, $queryContainer, 'where' );
		$this->assertPublicProperty( $expected, $queryContainer, 'components' );
		$this->assertPublicProperty( $expected, $queryContainer, 'joinfield' );
		$this->assertPublicProperty( $expected, $queryContainer, 'alias' );
		$this->assertPublicProperty( $expected, $queryContainer, 'queryNumber' );
	}

	private function assertPublicProperty( $expected, QueryContainer $queryContainer, $property ) {

		if ( !isset( $expected->{$property} ) ) {
			return null;
		}

		$this->assertTrue(
			$expected->{$property} == $queryContainer->{$property},
			$this->formatMessage( $property, $expected->{$property}, $queryContainer->{$property} )
		);
	}

	private function formatMessage( $id, $expected, $actual ) {
		return "Asserts {$id} to be expected [ " . $this->formatAsString( $expected ) . ' ] vs. actual [ ' . $this->formatAsString( $actual ) .' ]';
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
