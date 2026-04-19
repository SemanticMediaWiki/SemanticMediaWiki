<?php

namespace SMW\MediaWiki;

use LocalFile;
use MediaWiki\Title\Title;
use OldLocalFile;
use RepoGroup;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class FileRepoFinder {

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly RepoGroup $repoGroup ) {
	}

	/**
	 * @since 3.2
	 */
	public function findFile( Title $title, array $options = [] ) {
		return $this->repoGroup->findFile( $title, $options );
	}

	/**
	 * @since 3.2
	 */
	public function findFromArchive( string $sha1, string $timestamp ): OldLocalFile|LocalFile|false {
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
