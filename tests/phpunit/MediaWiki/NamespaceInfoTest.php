<?php

namespace SMW\Tests\MediaWiki;

use NamespaceInfo as MwNamespaceInfo;
use SMW\MediaWiki\NamespaceInfo;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\NamespaceInfo
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class NamespaceInfoTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private MwNamespaceInfo $mwNamespaceInfo;
	private NamespaceInfo $namespaceInfo;

	protected function setUp(): void {
		parent::setUp();

		$this->mwNamespaceInfo = $this->createMock( MwNamespaceInfo::class );
		$this->namespaceInfo = new NamespaceInfo( $this->mwNamespaceInfo );
	}

	public function testGetCanonicalName() {
		$this->mwNamespaceInfo->expects( $this->any() )
			->method( 'getCanonicalName' )
			->with( NS_MAIN )
			->willReturn( '' );

		$this->assertSame( '', $this->namespaceInfo->getCanonicalName( NS_MAIN ) );
	}

	public function testGetValidNamespaces() {
		$this->mwNamespaceInfo->expects( $this->any() )
			->method( 'getValidNamespaces' )
			->willReturn( [ NS_MAIN ] );

		$this->assertSame( [ NS_MAIN ], $this->namespaceInfo->getValidNamespaces() );
	}

	public function testGetSubject() {
		$this->mwNamespaceInfo->expects( $this->any() )
			->method( 'getSubject' )
			->with( NS_TALK )
			->willReturn( NS_MAIN );

		$this->assertSame(
			NS_MAIN,
			$this->mwNamespaceInfo->getSubject( NS_TALK )
		);
	}

}
