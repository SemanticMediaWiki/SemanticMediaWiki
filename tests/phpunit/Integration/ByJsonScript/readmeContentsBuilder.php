<?php

namespace SMW\Tests\Integration\ByJsonScript;

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
	 * @var array
	 */
	private $urlLocationMap = array(
		'Fixtures' => 'https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/tests/phpunit/Integration/ByJsonScript/Fixtures'
	);

	/**
	 * @since  2.4
	 */
	public function run() {

		$output = '';
		$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		$output .= $this->doGenerateContentFor( 'Fixtures', __DIR__ . '/Fixtures' );
		$output .= "\n-- Last updated on " .  $dateTimeUtc->format( 'Y-m-d' )  . " by `readmeContentsBuilder.php`". "\n";

		file_put_contents(  __DIR__ . '/README.md', $output );
	}

	private function doGenerateContentFor( $title, $path ) {

		$output = '';
		$urlLocation = $this->urlLocationMap[$title];

		$counter = 0;
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

			$output .= "\n";
			$counter++;
			$previousFirstKey = $key{0};
		}

		return "## $title\n" . "Contains $counter files:\n" . $output;
	}

	private function findFilesFor( $path, $extension ) {

		$files = array();

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
