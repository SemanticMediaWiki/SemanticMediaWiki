<?php

namespace SMW\Tests\Unit\MediaWiki\Search\ProfileForm;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\SortForm;
use SMW\MediaWiki\Search\ProfileForm\FormsFactory;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\FormsFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FormsFactoryTest extends TestCase {

	private $webRequest;

	protected function setUp(): void {
		$this->webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FormsFactory::class,
			new FormsFactory()
		);
	}

	public function testCanConstructOpenForm() {
		$instance = new FormsFactory();

		$this->assertInstanceOf(
			OpenForm::class,
			$instance->newOpenForm( $this->webRequest )
		);
	}

	public function testCanConstructCustomForm() {
		$instance = new FormsFactory();

		$this->assertInstanceOf(
			CustomForm::class,
			$instance->newCustomForm( $this->webRequest )
		);
	}

	public function testCanConstructSortForm() {
		$instance = new FormsFactory();

		$this->assertInstanceOf(
			SortForm::class,
			$instance->newSortForm( $this->webRequest )
		);
	}

	public function testCanConstructNamespaceForm() {
		$instance = new FormsFactory();

		$this->assertInstanceOf(
			NamespaceForm::class,
			$instance->newNamespaceForm()
		);
	}

}
