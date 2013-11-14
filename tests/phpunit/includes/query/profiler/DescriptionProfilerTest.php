<?php

namespace SMW\Test;

use SMW\DescriptionProfiler;
use SMW\HashIdGenerator;
use SMW\NullProfiler;
use SMW\Subobject;

/**
 * @covers \SMW\DescriptionProfiler
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
class DescriptionProfilerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\DescriptionProfiler';
	}

	/**
	 * @since 1.9
	 *
	 * @return DescriptionProfiler
	 */
	private function newInstance( $description = null ) {

		if ( $description === null ) {
			$description = $this->newMockBuilder()->newObject( 'QueryDescription' );
		}

		$profiler = new NullProfiler(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);

		return new DescriptionProfiler( $profiler, $description );
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

		$description = $this->newMockBuilder()->newObject( 'QueryDescription', array(
			'getQueryString' => 'Foo',
			'getSize'  => 55,
			'getDepth' => 9001
		) );

		$instance = $this->newInstance( $description );
		$instance->createProfile();

		$expected = array(
			'propertyCount' => 3,
			'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE' ),
			'propertyValue' => array( 'Foo', 55, 9001 )
		);

		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );

	}

}
