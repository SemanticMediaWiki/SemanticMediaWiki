<?php

namespace SMW\Tests\Unit\Constraint;

use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\TestCase;
use SMW\Constraint\Constraint;
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
class ConstraintRegistryFactoryTest extends TestCase {

	/**
	 * @dataProvider constraintKeyProvider
	 */
	public function testGetConstraint( $key ) {
		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$instance->setHookContainer(
			$hookContainer
		);

		$this->assertInstanceOf(
			Constraint::class,
			$instance->getConstraintByKey( $key )
		);
	}

	public function constraintKeyProvider() {
		$hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConstraintRegistry(
			ApplicationFactory::getInstance()->create( 'ConstraintFactory' )
		);

		$instance->setHookContainer(
			$hookContainer
		);

		foreach ( $instance->getConstraintKeys() as $key ) {
			yield [ $key ];
		}
	}

}
