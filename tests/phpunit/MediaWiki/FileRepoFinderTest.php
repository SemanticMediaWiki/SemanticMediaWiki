<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\FileRepoFinder;

/**
 * @covers \SMW\MediaWiki\FileRepoFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class FileRepoFinderTest extends \PHPUnit_Framework_TestCase {

	private $repoGroup;

	protected function setUp() : void {

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
			->will( $this->returnValue( $file ) );

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

		$db = $this->getMockBuilder( '\Database' )
			->disableOriginalConstructor()
			->getMock();

		$localRepo = $this->getMockBuilder( '\LocalRepo' )
			->disableOriginalConstructor()
			->getMock();

		$localRepo->expects( $this->any() )
			->method( 'getReplicaDB' )
			->will( $this->returnValue( $db ) );

		$this->repoGroup->expects( $this->any() )
			->method( 'getLocalRepo' )
			->will( $this->returnValue( $localRepo ) );

		$localRepo->expects( $this->once() )
			->method( 'findBySha1' )
			->will( $this->returnValue( [ $file ] ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new FileRepoFinder(
			$this->repoGroup
		);

		$this->assertInstanceOf(
			'\File',
			$instance->findFromArchive( '42', '1970010101010' )
		);
	}

}
