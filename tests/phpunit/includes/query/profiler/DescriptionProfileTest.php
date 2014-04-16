<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\Mock\MockObjectBuilder;
use SMW\Tests\Util\Mock\CoreMockObjectRepository;

use SMW\Query\Profiler\DescriptionProfile;
use SMW\Query\Profiler\NullProfile;
use SMW\HashIdGenerator;
use SMW\Subobject;

use Title;

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
class DescriptionProfileTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\Query\Profiler\DescriptionProfile';
	}

	/**
	 * @return DescriptionProfile
	 */
	private function newInstance( $description = null ) {

		if ( $description === null ) {
			$mockBuilder = new MockObjectBuilder( new CoreMockObjectRepository() );
			$description = $mockBuilder->newObject( 'QueryDescription' );
		}

		$profiler = new NullProfile(
			new Subobject( Title::newFromText( __METHOD__ ) ),
			new HashIdGenerator( 'Foo' )
		);

		return new DescriptionProfile( $profiler, $description );
	}

	/**
	 * @since 1.9
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testCreateProfile() {

		$mockBuilder = new MockObjectBuilder( new CoreMockObjectRepository() );
		$description = $mockBuilder->newObject( 'QueryDescription', array(
			'getQueryString' => 'Foo',
			'getSize'  => 55,
			'getDepth' => 9001
		) );

		$instance = $this->newInstance( $description );
		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 3,
			'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE' ),
			'propertyValues' => array( 'Foo', 55, 9001 )
		);

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);

	}

}
