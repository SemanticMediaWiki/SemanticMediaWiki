<?php

namespace SMW\Test;

use SMW\Query\Profiler\DurationProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

/**
 * @covers \SMW\Query\Profiler\DurationProfile
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DurationProfileTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Query\Profiler\DurationProfile';
	}

	/**
	 * @since 1.9
	 *
	 * @return DurationProfile
	 */
	private function newInstance( $duration = 0 ) {

		$profiler = new NullProfile(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);

		return new DurationProfile( $profiler, $duration );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider durationDataProvider
	 *
	 * @since 1.9
	 */
	public function testCreateProfile( $duration, $expected ) {

		$instance = $this->newInstance( $duration );
		$instance->addAnnotation();

		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );

	}

	/**
	 * @since 1.9
	 */
	public function durationDataProvider() {

		$provider = array();

		$provider[] = array( 0, array(
			'propertyCount' => 0
		) );

		$provider[] = array( 0.9001, array(
			'propertyCount' => 1,
			'propertyKey'   => array( '_ASKDU' ),
			'propertyValue' => array( 0.9001 )
		) );

		return $provider;
	}

}
