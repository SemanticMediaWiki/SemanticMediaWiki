<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\NamespaceExaminer;
use SMW\Tests\TestEnvironment;

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
	private $testEnvironment;
	private $jobFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UserChange::class,
			new UserChange( $this->namespaceExaminer )
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
			$this->namespaceExaminer
		);

		$instance->setOrigin( 'Foo' );

		$this->assertTrue(
			$instance->process( 'Foo' )
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
			$this->namespaceExaminer
		);

		$instance->setOrigin( 'Foo' );

		$this->assertTrue(
			$instance->process( $user )
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
			$this->namespaceExaminer
		);

		$this->assertFalse(
			$instance->process( 'Foo' )
		);
	}

}
