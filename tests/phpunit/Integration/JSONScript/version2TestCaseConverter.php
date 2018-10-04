<?php

namespace SMW\Tests\Integration\JSONScript;

/**
 * @private
 * @codeCoverageIgnore
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Version2TestCaseConverter {

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var string
	 */
	private $contents = '';

	/**
	 * @since 2.5
	 */
	public function run() {

		// http://php.net/manual/en/function.getopt.php
		$this->options = getopt( '', [ "file::", "test" ] );

		print "\nRunning Version2TestCaseConverter ..." . "\n\n";

		foreach ( $this->findFilesFor( __DIR__ . '/Fixtures', 'json' ) as $file => $location ) {
			$this->readAndConvertFile( $file, $location );
		}
	}

	private function readAndConvertFile( $file, $location ) {

		if ( isset( $this->options['file'] ) && $this->options['file'] !== $file ) {
			return print "Skipping {$file} because it doesn't match " . $this->options['file'] . "\n";
		}

		$this->contents = json_decode( file_get_contents( $location ), true );

		if ( isset( $this->contents['meta']['version'] ) && $this->contents['meta']['version'] === '2' ) {
		//	return print "Skipping {$file} because it has already been tagged with version 2.\n";
		}

	//	$contents = $this->replaceSpaceIndent(
	//		$this->doConvertToVersion2()
	//	);

		$contents = $this->replaceSpaceIndent(
			$this->doConvertToVersion2Assert()
		);

		if ( !isset( $this->options['test'] ) ) {
			file_put_contents( $location, $contents );
			print "{$file} was converted to version 2.\n";
		} else {
			print $contents;
		}
	}

	private function replaceSpaceIndent( $contents ) {

		// Change the four-space indent to a tab indent
		$contents = str_replace( "\n    ", "\n\t", $contents );

		while ( strpos( $contents, "\t    " ) !== false ) {
			$contents = str_replace( "\t    ", "\t\t", $contents );
		}

		return $contents;
	}

	private function doConvertToVersion2Assert() {

		$contents = $this->contents;

		foreach ( $contents['tests'] as $key => $value ) {

			if ( isset( $value['store'] ) ) {
				$value['assert-store'] = $value['store'];
				unset( $value['store'] );
			}

			if ( isset( $value['expected-output'] ) ) {
				$value['assert-output'] = $value['expected-output'];
				unset( $value['expected-output'] );
			}

			$contents['tests'][$key] = $value;
		}

		foreach ( $contents['setup'] as $key => $value ) {

			if ( isset( $value['name'] ) ) {
				$value['page'] = $value['name'];
				unset( $value['name'] );
			}

			if ( isset( $value['contents'] ) ) {
				$v = $value['contents'];
				unset( $value['contents'] );
				$value['contents'] = $v;
			}

			$contents['setup'][$key] = $value;
		}

		return json_encode( $contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function doConvertToVersion2() {

		$setup = [];
		$tests = [];

		// Replace and accordance with the new Version 2 structure
		$this->findAndReplaceEntity( 'properties', $setup );
		$this->findAndReplaceEntity( 'subjects', $setup );

		// Replace and accordance with the new Version 2 structure
		$this->findAndReplaceTestCases( 'format-testcases', 'format', $tests );
		$this->findAndReplaceTestCases( 'parser-testcases', 'parser', $tests );
		$this->findAndReplaceTestCases( 'rdf-testcases', 'rdf', $tests );
		$this->findAndReplaceTestCases( 'special-testcases', 'special', $tests );
		$this->findAndReplaceTestCases( 'query-testcases', 'query', $tests );
		$this->findAndReplaceTestCases( 'concept-testcases', 'concept', $tests );

		// Reorder
		$contents = [
			'description' => $this->contents['description'],
			'setup' => $setup,
			'beforeTest' => []
		];

		if ( isset( $this->contents['maintenance-run'] ) ) {
			$contents['beforeTest']['maintenance-run'] = $this->contents['maintenance-run'];
		}

		if ( isset( $this->contents['job-run'] ) ) {
			$contents['beforeTest']['job-run'] = $this->contents['job-run'];
		}

		if ( $contents['beforeTest'] === [] ) {
			unset( $contents['beforeTest'] );
		}

		$contents['tests'] = $tests;
		$contents['settings'] = $this->contents['settings'];
		$contents['meta'] = $this->contents['meta'];
		$contents['meta']['version'] = '2';

		return json_encode( $contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function findAndReplaceEntity( $name, &$entities ) {

		if ( !isset( $this->contents[$name] ) || $this->contents[$name] === [] ) {
			return;
		}

		foreach ( $this->contents[$name] as $values ) {

			$entity = [];

			if ( $name === 'properties' ) {
				$entity['namespace'] = "SMW_NS_PROPERTY";
			}

			foreach ( $values as $key => $value ) {
				$entity[$key] = $value;
			}

			$entities[] = $entity;
		}
	}

	private function findAndReplaceTestCases( $name, $type, &$tests ) {

		if ( !isset( $this->contents[$name] ) || $this->contents[$name] === [] ) {
			return;
		}

		foreach ( $this->contents[$name] as $values ) {

			$case = [];
			$case['type'] = $type;

			foreach ( $values as $key => $value ) {
				$case[$key] = $value;
			}

			$tests[] = $case;
		}
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

$version2TestCaseConverter = new Version2TestCaseConverter();
$version2TestCaseConverter->run();
