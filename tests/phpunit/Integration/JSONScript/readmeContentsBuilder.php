<?php

namespace SMW\Tests\Integration\JSONScript;

/**
 * Build contents from a selected folder and replaces the content of the
 * README.md from where the script was started.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ReadmeContentsBuilder {

	/**
	 * @var string
	 */
	CONST REPLACE_START_MARKER = '<!-- Begin of generated contents by readmeContentsBuilder.php -->';
	CONST REPLACE_END_MARKER = '<!-- End of generated contents by readmeContentsBuilder.php -->';

	/**
	 * @var array
	 */
	private $urlLocationMap = [
		'TestCases' => 'https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/JSONScript/TestCases'
	];

	/**
	 * @since  2.4
	 */
	public function run() {

		$file = __DIR__ . '/README.md';
		$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		$replacement = self::REPLACE_START_MARKER . "\n\n";
		$replacement .= $this->doGenerateContentFor( 'TestCases', __DIR__ . '/TestCases' );

		$replacement .= "\n-- Last updated on " .  $dateTimeUtc->format( 'Y-m-d' )  . " by `readmeContentsBuilder.php`". "\n";
		$replacement .= "\n" . self::REPLACE_END_MARKER;

		$contents = file_get_contents( $file );
		$start = strpos( $contents, self::REPLACE_START_MARKER );
		$length = strrpos( $contents, self::REPLACE_END_MARKER ) - $start + strlen( self::REPLACE_END_MARKER );

		file_put_contents(
			$file,
			substr_replace( $contents, $replacement, $start, $length )
		);
	}

	private function doGenerateContentFor( $title, $path ) {

		$output = '';
		$urlLocation = $this->urlLocationMap[$title];

		$counter = 0;
		$tests = 0;
		$previousFirstKey = '';

		foreach ( $this->findFilesFor( $path, 'json' ) as $key => $location ) {

			if ( $previousFirstKey !== $key{0} ) {
				$output .= "\n" . '### ' . ucfirst( $key{0} ). "\n";
			}

			$output .= '* [' . $key .'](' . $urlLocation . '/' . $key . ')';

			$contents = json_decode( file_get_contents( $location ), true );

			if ( $contents === null || json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			if ( isset( $contents['description'] ) ) {
				$output .= " " . $contents['description'];
			}

			if ( isset( $contents['tests'] ) ) {
				$tests += count( $contents['tests'] );
			}

			$output .= "\n";
			$counter++;
			$previousFirstKey = $key{0};
		}

		return "## $title\n\n" . "Contains $counter files with a total of $tests tests:\n" . $output;
	}

	private function findFilesFor( $path, $extension ) {

		$files = [];

		$directoryIterator = new \RecursiveDirectoryIterator( $path );

		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( strtolower( substr( $fileInfo->getFilename(), -( strlen( $extension ) + 1 ) ) ) === ( '.' . $extension ) ) {
				$files[$fileInfo->getFilename()] = $fileInfo->getPathname();
			}
		}

		return $files;
	}

}

$readmeContentsBuilder = new ReadmeContentsBuilder();
$readmeContentsBuilder->run();
