<?php

namespace SMW\Tests\Integration\Constraint;

use SMW\ApplicationFactory;
use SMW\Constraint\ConstraintRegistry;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistryFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraint( $key ) {

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
			$instance->getConstraintByKey( $key )
		);
	}

	public function constraintKeyProvider() {

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		foreach ( $instance->getConstraintKeys() as $key ) {
			yield [ $key ];
		}
	}

}
