<?php

namespace SMW\Tests\MediaWiki\Search\Form;

use SMW\MediaWiki\Search\Form\NamespaceForm;

/**
 * @covers \SMW\MediaWiki\Search\Form\NamespaceForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceFormTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NamespaceForm::class,
			new NamespaceForm()
		);
	}

	public function testMakeFields() {

		$instance = new NamespaceForm();

		$instance->setSearchableNamespaces( [ 0 => 'Foo '] );

		$this->assertContains(
			"<fieldset id='mw-searchoptions'>",
			$instance->makeFields()
		);
	}

	public function testCheckNamespaceEditToken() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getEditToken' );

		$user->expects( $this->any() )
			->method( 'isLoggedIn' )
			->will( $this->returnValue( true ) );

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$instance = new NamespaceForm();

		$instance->checkNamespaceEditToken( $specialSearch );
	}

}
