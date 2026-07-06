<?php

namespace SMW\Elastic\Indexer\Attachment;

use File;
use FileBackend;
use MediaWiki\Title\Title;
use RepoGroup;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FileHandler {

	/**
	 * Transform the content to base64
	 */
	const FORMAT_BASE64 = 'format/base64';

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly RepoGroup $repoGroup ) {
	}

	/**
	 * @since 3.2
	 */
	public function findFileByTitle( Title $title ): File|false|null {
		return $this->repoGroup->findFile( $title );
	}

	/**
	 * @since 6.0
	 *
	 * @param File $file
	 *
	 * @return string
	 */
	public function fetchContentFromFile( File $file ): string {
		$be = $file->getRepo()->getBackend();

		$content = '';

		if ( $be instanceof FileBackend ) {
			$content = $be->getFileContents( [ 'src' => $file->getPath() ] ) ?: '';
		}

		return $content;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $contents
	 * @param string $type
	 *
	 * @return string
	 */
	public function format( string $contents, string $type = '' ): string {
		if ( $type === self::FORMAT_BASE64 ) {
			return base64_encode( $contents );
		}

		return $contents;
	}

}
