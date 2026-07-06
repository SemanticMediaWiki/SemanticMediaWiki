<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\NamespaceExaminer;

/**
 * @covers \SMW\MediaWiki\Hooks\UserChange
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class UserChangeTest extends TestCase {

	private $namespaceExaminer;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UserChange::class,
			new UserChange( $this->namespaceExaminer, $this->jobFactory )
		);
	}

	public function testOnEnabledUserNamespace() {
		$job = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $job );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( NS_USER )
			->willReturn( true );

		$instance = new UserChange(
			$this->namespaceExaminer,
			$this->jobFactory
		);

		$this->assertTrue(
			$instance->notify( 'Foo', 'Foo' )
		);
	}

	public function testOnEnabledUserNamespace_User() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$job = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newUpdateJob' )
			->willReturn( $job );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( NS_USER )
			->willReturn( true );

		$instance = new UserChange(
			$this->namespaceExaminer,
			$this->jobFactory
		);

		$this->assertTrue(
			$instance->notify( 'Foo', $user )
		);
	}

	public function testOnDisabledUserNamespace() {
		$this->jobFactory->expects( $this->never() )
			->method( 'newUpdateJob' );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( NS_USER )
			->willReturn( false );

		$instance = new UserChange(
			$this->namespaceExaminer,
			$this->jobFactory
		);

		$this->assertFalse(
			$instance->notify( 'Foo', 'Foo' )
		);
	}

}
