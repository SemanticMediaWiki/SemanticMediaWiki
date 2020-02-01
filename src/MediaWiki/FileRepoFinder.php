<?php

namespace SMW\MediaWiki;

use Title;
use RepoGroup;
use OldLocalFile;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class FileRepoFinder {

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @since 3.2
	 *
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( RepoGroup $repoGroup ) {
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param array $options
	 *
	 * @return File|bool File, or false if the file does not exist
	 */
	public function findFile( Title $title, array $options = [] ) {
		return $this->repoGroup->findFile( $title, $options );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $sha1
	 * @param string $timestamp
	 *
	 * @return File|bool File, or false if the file does not exist
	 */
	public function findFromArchive( $sha1, $timestamp ) {
		$localRepo = $this->repoGroup->getLocalRepo();

		$file = OldLocalFile::newFromKey(
			$sha1,
			$localRepo,
			$timestamp
		);

		// Try the local repo!
		if ( $file === false ) {
			$files = $localRepo->findBySha1( $sha1 );
			$file = end( $files );
		}

		return $file;
	}

}
