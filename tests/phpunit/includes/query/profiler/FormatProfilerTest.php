<?php

namespace SMW\Test;

use SMW\HashIdGenerator;
use SMW\FormatProfiler;
use SMW\NullProfiler;
use SMW\Subobject;

/**
 * @covers \SMW\FormatProfiler
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
class FormatProfilerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FormatProfiler';
	}

	/**
	 * @since 1.9
	 *
	 * @return FormatProfiler
	 */
	private function newInstance( $format = 'Foo' ) {

		$profiler = new NullProfiler(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);

		return new FormatProfiler( $profiler, $format );
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
		$instance->createProfile();

		$expected = array(
			'propertyCount' => 1,
			'propertyKey'   => array( '_ASKFO' ),
			'propertyValue' => array( 'Foo' )
		);

		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );

	}

}
