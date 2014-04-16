<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\Query\Profiler\DurationProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

use Title;

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
class DurationProfileTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\Query\Profiler\DurationProfile';
	}

	/**
	 * @return DurationProfile
	 */
	private function newInstance( $duration = 0 ) {

		$profiler = new NullProfile(
			new Subobject( Title::newFromText( __METHOD__ ) ),
			new HashIdGenerator( 'Foo' )
		);

		return new DurationProfile( $profiler, $duration );
	}

	/**
	 * @since 1.9
	 */
	public function testCanConstruct() {
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

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);

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
			'propertyCount'  => 1,
			'propertyKeys'   => array( '_ASKDU' ),
			'propertyValues' => array( 0.9001 )
		) );

		return $provider;
	}

}
