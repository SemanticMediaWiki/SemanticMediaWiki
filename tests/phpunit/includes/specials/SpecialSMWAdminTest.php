<?php

namespace SMW\Test;

use SMWAdmin;
use FauxRequest;
use User;

/**
 * @covers \SMWAdmin
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SpecialPage
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class SpecialSMWAdminTest extends SpecialPageTestCase {

	public function getClass() {
		return '\SMWAdmin';
	}

	protected function getInstance() {
		return new SMWAdmin();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	public function testExecuteWithMissingPermissionThrowsException() {

		$this->setExpectedException( 'PermissionsError' );
		$this->execute( '', null, new User );

	}

	public function testExecute() {

		$this->execute( '', null, new MockSuperUser );
		$this->assertInternalType( 'string', $this->getText() );

	}

	/**
	 * @depends testExecute
	 */
	public function testExecuteOnActionListSettings() {

		$this->execute( '', new FauxRequest( array( 'action' => 'listsettings' ) ), new MockSuperUser );
		$this->assertInternalType( 'string', $this->getText() );

	}

}
