<?php

namespace SMW\Tests\Property;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Property\RestrictionExaminer;

/**
 * @covers \SMW\Property\RestrictionExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RestrictionExaminerTest extends TestCase {

	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RestrictionExaminer::class,
			new RestrictionExaminer()
		);
	}

	public function testGrepPropertyFromRestrictionErrorMsg() {
		$msg = '[2,"smw-datavalue-property-create-restriction","Has unknown","foo"]';

		$this->assertInstanceOf(
			Property::class,
			RestrictionExaminer::grepPropertyFromRestrictionErrorMsg( $msg )
		);
	}

	public function testRestrictionForPredefinedProperty() {
		$instance = new RestrictionExaminer();

		$instance->checkRestriction( new Property( '_MDAT' ) );

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForPredefinedPropertyOnQueryContext() {
		$instance = new RestrictionExaminer();
		$instance->isQueryContext( true );

		$instance->checkRestriction( new Property( '_MDAT' ) );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnFalseCreateProtectionRight() {
		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( false );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new Property( 'Foo' ) );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnCreateProtectionRight() {
		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( 'foo' );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new Property( 'Foo' ) );

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnCreateProtectionRightWithAllowedUser() {
		$right = 'bar';

		$this->user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $right )
			->willReturn( true );

		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( $right );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new Property( 'Foo' ) );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnMainNamespace() {
		$instance = new RestrictionExaminer();

		$instance->checkRestriction(
			new Property( '_TYPE' ),
			WikiPage::newFromText( 'Bar', NS_MAIN )
		);

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnPropertyNamespace() {
		$instance = new RestrictionExaminer();

		$instance->checkRestriction(
			new Property( '_TYPE' ),
			WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

}
