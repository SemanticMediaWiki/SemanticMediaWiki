<?php

namespace SMW\Tests\Integration\Constraint;

use SMW\Constraint\ConstraintRegistry;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistryFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraint( $key ) {
		$hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$instance->setHookDispatcher(
			$hookDispatcher
		);

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
			$instance->getConstraintByKey( $key )
		);
	}

	public function constraintKeyProvider() {
		$hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$instance->setHookDispatcher(
			$hookDispatcher
		);

		foreach ( $instance->getConstraintKeys() as $key ) {
			yield [ $key ];
		}
	}

}
