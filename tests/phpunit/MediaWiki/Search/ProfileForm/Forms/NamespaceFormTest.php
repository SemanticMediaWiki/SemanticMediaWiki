<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use MediaWiki\Title\NamespaceInfo;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\Localizer;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceFormTest extends TestCase {

	private $namespaceInfo;
	private $localizer;
	private $messageLocalizer;

	protected function setUp(): void {
		$this->namespaceInfo = $this->getMockBuilder( NamespaceInfo::class )
			->disableOriginalConstructor()
			->getMock();

		$this->localizer = $this->getMockBuilder( Localizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
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

		$this->assertStringContainsString(
			"<fieldset id='mw-searchoptions'>",
			$instance->makeFields()
		);
	}

	public function testCheckNamespaceEditToken() {
		$user = $this->getMockBuilder( User::class )
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
