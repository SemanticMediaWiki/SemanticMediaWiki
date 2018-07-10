<?php

namespace SMW\Tests\Utils\Validators;

use SMW\SQLStore\QueryEngine\QuerySegment;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class QuerySegmentValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @since 2.1
	 *
	 * @param  mixed $expected
	 * @param  QuerySegment[] $querySegment
	 */
	public function assertThatContainerContains( $expected, array $querySegment ) {

		$expected = is_array( $expected ) ? $expected : [ $expected ];

		$this->assertEquals(
			count( $expected ),
			count( $querySegment ),
			$this->formatMessage( 'container count', count( $expected ), count( $querySegment ) )
		);

		foreach ( $querySegment as $key => $container ) {
			$this->assertInstanceOf(
				'\SMW\SQLStore\QueryEngine\QuerySegment',
				$container
			);

			$this->assertThatContainerHasProperties( $expected[$key], $container );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param  mixed $expected
	 * @param  QuerySegment $querySegment
	 */
	public function assertThatContainerHasProperties( $expected, QuerySegment $querySegment ) {
		$this->assertPublicProperty( $expected, $querySegment, 'type' );
		$this->assertPublicProperty( $expected, $querySegment, 'joinfield' );
		$this->assertPublicProperty( $expected, $querySegment, 'jointable' );
		$this->assertPublicProperty( $expected, $querySegment, 'sortfields' );
		$this->assertPublicProperty( $expected, $querySegment, 'components' );
		$this->assertPublicProperty( $expected, $querySegment, 'alias' );
		$this->assertPublicProperty( $expected, $querySegment, 'where' );
		$this->assertPublicProperty( $expected, $querySegment, 'from' );
		$this->assertPublicProperty( $expected, $querySegment, 'queryNumber' );
	}

	private function assertPublicProperty( $expected, QuerySegment $querySegment, $property ) {

		if ( !isset( $expected->{$property} ) ) {
			return null;
		}

		$this->assertTrue(
			$expected->{$property} == $querySegment->{$property},
			$this->formatMessage( $property, $expected->{$property}, $querySegment->{$property} )
		);
	}

	private function formatMessage( $id, $expected, $actual ) {
		return "Asserts {$id} to be expected [ " . $this->formatAsString( $expected ) . ' ] vs. actual [ ' . $this->formatAsString( $actual ) .' ]';
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
