<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm;

use SMW\MediaWiki\Search\ProfileForm\FormsFactory;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\FormsFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsFactoryTest extends \PHPUnit_Framework_TestCase {

	private $webRequest;

	protected function setUp() {

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
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
			'\SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm',
			$instance->newOpenForm( $this->webRequest )
		);
	}

	public function testCanConstructCustomForm() {

		$instance = new FormsFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm',
			$instance->newCustomForm( $this->webRequest )
		);
	}

	public function testCanConstructSortForm() {

		$instance = new FormsFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\ProfileForm\Forms\SortForm',
			$instance->newSortForm( $this->webRequest )
		);
	}

	public function testCanConstructNamespaceForm() {

		$instance = new FormsFactory();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm',
			$instance->newNamespaceForm()
		);
	}

}
