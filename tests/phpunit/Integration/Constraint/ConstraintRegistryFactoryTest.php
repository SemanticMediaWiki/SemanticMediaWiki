<?php

namespace SMW\Tests\Integration\Constraint;

use PHPUnit\Framework\TestCase;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintRegistry;
use SMW\MediaWiki\HookDispatcher;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistryFactoryTest extends TestCase {

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraint( $key ) {
		$hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$instance->setHookDispatcher(
			$hookDispatcher
		);

		$this->assertInstanceOf(
			Constraint::class,
			$instance->getConstraintByKey( $key )
		);
	}

	public function constraintKeyProvider() {
		$hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
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
