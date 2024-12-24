<?php

namespace SMW\Tests\MediaWiki\Preference;

use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Preference\PreferenceExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PreferenceExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PreferenceExaminer::class,
			new PreferenceExaminer()
		);
	}

	public function testHasPreferenceOf() {
		$this->user->expects( $this->any() )
			->method( 'getOption' )
			->with( 'foo' )
			->willReturn( false );

		$instance = new PreferenceExaminer();

		$instance->setUser( $this->user );

		$this->assertIsBool(

			$instance->hasPreferenceOf( 'foo' )
		);
	}

	public function testHasPreferenceOf_NoUser() {
		$instance = new PreferenceExaminer();

		$this->assertIsBool(

			$instance->hasPreferenceOf( 'foo' )
		);
	}

}
