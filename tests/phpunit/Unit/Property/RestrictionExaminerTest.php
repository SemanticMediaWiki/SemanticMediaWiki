<?php

namespace SMW\Tests\Property;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Property\RestrictionExaminer;

/**
 * @covers \SMW\Property\RestrictionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RestrictionExaminerTest extends \PHPUnit_Framework_TestCase {

	private $user;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RestrictionExaminer::class,
			new RestrictionExaminer()
		);

		//@ legavy
		$this->assertInstanceOf(
			'\SMW\PropertyRestrictionExaminer',
			new RestrictionExaminer()
		);
	}

	public function testGrepPropertyFromRestrictionErrorMsg() {

		$msg = '[2,"smw-datavalue-property-create-restriction","Has unknown","foo"]';

		$this->assertInstanceOf(
			DIProperty::class,
			RestrictionExaminer::grepPropertyFromRestrictionErrorMsg( $msg )
		);
	}

	public function testRestrictionForPredefinedProperty() {

		$instance = new RestrictionExaminer();

		$instance->checkRestriction( new DIProperty( '_MDAT' )  );

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForPredefinedPropertyOnQueryContext() {

		$instance = new RestrictionExaminer();
		$instance->isQueryContext( true );

		$instance->checkRestriction( new DIProperty( '_MDAT' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnFalseCreateProtectionRight() {

		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( false );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new DIProperty( 'Foo' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnCreateProtectionRight() {

		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( 'foo' );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new DIProperty( 'Foo' )  );

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnCreateProtectionRightWithAllowedUser() {

		$right = 'bar';

		$this->user->expects( $this->once() )
			->method( 'isAllowed' )
			->with( $this->equalTo( $right ) )
			->will( $this->returnValue( true ) );

		$instance = new RestrictionExaminer();

		$instance->setCreateProtectionRight( $right );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new DIProperty( 'Foo' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnMainNamespace() {

		$instance = new RestrictionExaminer();

		$instance->checkRestriction(
			new DIProperty( '_TYPE' ),
			DIWikiPage::newFromText( 'Bar', NS_MAIN )
		);

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnPropertyNamespace() {

		$instance = new RestrictionExaminer();

		$instance->checkRestriction(
			new DIProperty( '_TYPE' ),
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

}
