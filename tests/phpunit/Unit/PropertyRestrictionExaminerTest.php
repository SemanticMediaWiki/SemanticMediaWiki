<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyRestrictionExaminer;

/**
 * @covers \SMW\PropertyRestrictionExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyRestrictionExaminerTest extends \PHPUnit_Framework_TestCase {

	private $user;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertyRestrictionExaminer::class,
			new PropertyRestrictionExaminer()
		);
	}

	public function testGrepPropertyFromRestrictionErrorMsg() {

		$msg = '[2,"smw-datavalue-property-create-restriction","Has unknown","foo"]';

		$this->assertInstanceOf(
			DIProperty::class,
			PropertyRestrictionExaminer::grepPropertyFromRestrictionErrorMsg( $msg )
		);
	}

	public function testRestrictionForPredefinedProperty() {

		$instance = new PropertyRestrictionExaminer();

		$instance->checkRestriction( new DIProperty( '_MDAT' )  );

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForPredefinedPropertyOnQueryContext() {

		$instance = new PropertyRestrictionExaminer();
		$instance->isQueryContext( true );

		$instance->checkRestriction( new DIProperty( '_MDAT' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnFalseCreateProtectionRight() {

		$instance = new PropertyRestrictionExaminer();

		$instance->setCreateProtectionRight( false );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new DIProperty( 'Foo' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testRestrictionForUserPropertyOnCreateProtectionRight() {

		$instance = new PropertyRestrictionExaminer();

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

		$instance = new PropertyRestrictionExaminer();

		$instance->setCreateProtectionRight( $right );
		$instance->setUser( $this->user );

		$instance->checkRestriction( new DIProperty( 'Foo' )  );

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnMainNamespace() {

		$instance = new PropertyRestrictionExaminer();

		$instance->checkRestriction(
			new DIProperty( '_TYPE' ),
			DIWikiPage::newFromText( 'Bar', NS_MAIN )
		);

		$this->assertTrue(
			$instance->hasRestriction()
		);
	}

	public function testDeclarativePropertyOnPropertyNamespace() {

		$instance = new PropertyRestrictionExaminer();

		$instance->checkRestriction(
			new DIProperty( '_TYPE' ),
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$this->assertFalse(
			$instance->hasRestriction()
		);
	}

}
