<?php

namespace SMW\Tests\Importer;

use SMW\Importer\ImportContents;

/**
 * @covers \SMW\Importer\ImportContents
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImportContentsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\ImportContents',
			new ImportContents()
		);
	}

	public function testDescription() {

		$instance = new ImportContents();

		$instance->setDescription( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getDescription()
		);
	}

	public function testVersion() {

		$instance = new ImportContents();

		$instance->setVersion( '1' );

		$this->assertSame(
			1,
			$instance->getVersion()
		);
	}

	public function testName() {

		$instance = new ImportContents();

		$instance->setName( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getName()
		);
	}

	public function testNamespace() {

		$instance = new ImportContents();

		$instance->setNamespace( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getNamespace()
		);
	}

	public function testContents() {

		$instance = new ImportContents();

		$instance->setContents( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getContents()
		);
	}

	public function testContentType() {

		$instance = new ImportContents();

		$instance->setContentType( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getContentType()
		);
	}

	public function testError() {

		$instance = new ImportContents();

		$instance->addError( 'Foo' );

		$this->assertSame(
			[ 'Foo' ],
			$instance->getErrors()
		);
	}

	public function testOptions() {

		$instance = new ImportContents();

		$instance->setOptions( 'Foo' );

		$this->assertSame(
			[ 'Foo' ],
			$instance->getOptions()
		);

		$this->assertFalse(
			$instance->getOption( 'Foo' )
		);
	}

}
