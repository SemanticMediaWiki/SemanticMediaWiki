<?php

namespace SMW\Test;

use SMW\Query\Profiler\DescriptionProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

/**
 * @covers \SMW\Query\Profiler\DescriptionProfile
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
class DescriptionProfileTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Query\Profiler\DescriptionProfile';
	}

	/**
	 * @since 1.9
	 *
	 * @return DescriptionProfile
	 */
	private function newInstance( $description = null ) {

		if ( $description === null ) {
			$description = $this->newMockBuilder()->newObject( 'QueryDescription' );
		}

		$profiler = new NullProfile(
			new Subobject( $this->newTitle() ),
			new HashIdGenerator( 'Foo' )
		);

		return new DescriptionProfile( $profiler, $description );
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
		$instance->addAnnotation();

		$expected = array(
			'propertyCount' => 3,
			'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE' ),
			'propertyValue' => array( 'Foo', 55, 9001 )
		);

		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );

	}

}
