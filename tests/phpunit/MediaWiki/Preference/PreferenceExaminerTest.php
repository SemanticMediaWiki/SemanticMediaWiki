<?php

namespace SMW\Tests\MediaWiki\Preference;

use MediaWiki\User\UserOptionsLookup;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Preference\PreferenceExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
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
		$userOptionsLookup = $this->createMock( UserOptionsLookup::class );

		$userOptionsLookup->expects( $this->any() )
			->method( 'getOption' )
			->with( $this->user, 'foo', false )
			->willReturn( false );

		$instance = new PreferenceExaminer( $this->user, $userOptionsLookup );

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
