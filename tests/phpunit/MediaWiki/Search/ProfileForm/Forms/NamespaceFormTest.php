<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceFormTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $namespaceInfo;
	private $localizer;
	private $messageLocalizer;

	protected function setUp(): void {
		$this->namespaceInfo = $this->getMockBuilder( '\SMW\MediaWiki\NamespaceInfo' )
			->disableOriginalConstructor()
			->getMock();

		$this->localizer = $this->getMockBuilder( '\SMW\Localizer\Localizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NamespaceForm::class,
			new NamespaceForm( $this->namespaceInfo, $this->localizer )
		);
	}

	public function testMakeFields() {
		$instance = new NamespaceForm(
			$this->namespaceInfo,
			$this->localizer
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->setSearchableNamespaces( [ 0 => 'Foo ' ] );

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
			->method( 'isRegistered' )
			->willReturn( true );

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$instance = new NamespaceForm(
			$this->namespaceInfo,
			$this->localizer
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$instance->checkNamespaceEditToken( $specialSearch );
	}

}
