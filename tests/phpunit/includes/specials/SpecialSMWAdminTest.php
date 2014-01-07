<?php

namespace SMW\Test;

use SMWAdmin;
use FauxRequest;

/**
 * @covers \SMWAdmin
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class SpecialSMWAdminTest extends SpecialPageTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWAdmin';
	}

	/**
	 * @return SMWAdmin
	 */
	protected function getInstance() {
		return new SMWAdmin();
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testExecuteWithMissingPermissionThrowsException() {

		$this->setExpectedException( 'PermissionsError' );

		$this->getInstance();
		$this->execute( '', null, new \User );

	}

	/**
	 * @since 1.9.0.2
	 */
	public function testExecute() {

		$this->getInstance();
		$this->execute( '', null, new MockSuperUser );

		$this->assertInternalType( 'string', $this->getText() );
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testExecuteOnActionListSettings() {

		$this->getInstance();

		ob_start();
		$this->execute( '', new FauxRequest( array( 'action' => 'listsettings' ) ), new MockSuperUser );
		ob_clean();

		$this->assertInternalType( 'string', $this->getText() );
	}

}
