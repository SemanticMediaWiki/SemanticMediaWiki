<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\FileRepoFinder;

/**
 * @covers \SMW\MediaWiki\FileRepoFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class FileRepoFinderTest extends \PHPUnit\Framework\TestCase {

	private $repoGroup;

	protected function setUp(): void {
		$this->repoGroup = $this->getMockBuilder( '\RepoGroup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FileRepoFinder::class,
			new FileRepoFinder( $this->repoGroup )
		);
	}

	public function testFindFile() {
		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$this->repoGroup->expects( $this->once() )
			->method( 'findFile' )
			->willReturn( $file );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FileRepoFinder(
			$this->repoGroup
		);

		$this->assertInstanceOf(
			'\File',
			$instance->findFile( $title )
		);
	}

	public function testFindFromArchive() {
		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			// Mock the IReadableDatabase interface
			$db = $this->getMockBuilder( \Wikimedia\Rdbms\IReadableDatabase::class )
			->disableOriginalConstructor()
			->getMock();
		} else {
			$db = $this->getMockBuilder( '\Database' )
			->disableOriginalConstructor()
			->getMock();
		}

		$localRepo = $this->getMockBuilder( '\LocalRepo' )
			->disableOriginalConstructor()
			->getMock();

		// Ensure getReplicaDB returns a mock of IReadableDatabase
		$localRepo->expects( $this->any() )
			->method( 'getReplicaDB' )
			->willReturn( $db );

		$this->repoGroup->expects( $this->any() )
			->method( 'getLocalRepo' )
			->willReturn( $localRepo );

		$localRepo->expects( $this->once() )
			->method( 'findBySha1' )
			->willReturn( [ $file ] );

		$instance = new FileRepoFinder(
			$this->repoGroup
		);

		$this->assertInstanceOf(
			'\File',
			$instance->findFromArchive( '42', '1970010101010' )
		);
	}

}
