<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\NamespaceInfo;

/**
 * @covers \SMW\MediaWiki\NamespaceInfo
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class NamespaceInfoTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NamespaceInfo::class,
			new NamespaceInfo()
		);
	}

	public function testGetCanonicalName() {

		$instance = new NamespaceInfo();

		$this->assertInternalType(
			'string',
			$instance->getCanonicalName( NS_MAIN )
		);
	}

	public function testGetValidNamespaces() {

		$instance = new NamespaceInfo();

		$this->assertInternalType(
			'array',
			$instance->getValidNamespaces()
		);
	}

	public function testGetSubject() {

		$instance = new NamespaceInfo();

		$this->assertInternalType(
			'integer',
			$instance->getSubject( NS_MAIN )
		);
	}

}
