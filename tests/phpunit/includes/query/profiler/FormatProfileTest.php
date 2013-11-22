<?php

namespace SMW\Test;

use SMW\Query\Profiler\FormatProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

/**
 * @covers \SMW\Query\Profiler\FormatProfile
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
class FormatProfileTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Query\Profiler\FormatProfile';
	}

	/**
	 * @since 1.9
	 *
	 * @return FormatProfile
	 */
	private function newInstance( $format = 'Foo' ) {

		$profiler = new NullProfile(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);

		return new FormatProfile( $profiler, $format );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testCreateProfile() {

		$instance = $this->newInstance( 'Foo' );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount' => 1,
			'propertyKey'   => array( '_ASKFO' ),
			'propertyValue' => array( 'Foo' )
		);

		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );

	}

}
