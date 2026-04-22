<?php

namespace SMW\Importer;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ImportContents {

	const CONTENT_TEXT = 'text/plain';
	const CONTENT_XML = 'text/xml';

	private int $version = 0;

	private string $description = '';

	private string $name = '';

	private string $importPerformer = '';

	private int $namespace = 0;

	private string $contents = '';

	private string $contentsFile = '';

	private string $contentType = self::CONTENT_TEXT;

	private array $errors = [];

	private array $options = [];

	/**
	 * @since 2.5
	 */
	public function setVersion( mixed $version ): void {
		$this->version = intval( $version );
	}

	/**
	 * @since 2.5
	 */
	public function getVersion(): int {
		return $this->version;
	}

	/**
	 * @since 2.5
	 */
	public function setDescription( string $description ): void {
		$this->description = $description;
	}

	/**
	 * @since 2.5
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @since 3.0
	 */
	public function setContentType( string $contentType ): void {
		$this->contentType = $contentType;
	}

	/**
	 * @since 3.0
	 */
	public function getContentType(): string {
		return $this->contentType;
	}

	/**
	 * @since 2.5
	 */
	public function setName( string $name ): void {
		$this->name = $name;
	}

	/**
	 * @since 2.5
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @since 3.2
	 */
	public function setImportPerformer( string $importPerformer ): void {
		$this->importPerformer = $importPerformer;
	}

	/**
	 * @since 3.2
	 */
	public function getImportPerformer(): string {
		return $this->importPerformer;
	}

	/**
	 * @since 2.5
	 */
	public function setNamespace( int $namespace ): void {
		$this->namespace = $namespace;
	}

	/**
	 * @since 2.5
	 */
	public function getNamespace(): int {
		return $this->namespace;
	}

	/**
	 * @since 3.0
	 */
	public function setContentsFile( string $contentsFile ): void {
		$this->contentsFile = $contentsFile;
	}

	/**
	 * @since 3.2
	 */
	public function getFingerprint(): string {
		$fingerprint = md5( $this->contents );

		if ( $this->contentsFile !== '' ) {
			$fingerprint .= hash_file( 'md5', $this->contentsFile );
		}

		return md5( $this->version . $fingerprint );
	}

	/**
	 * @since 3.0
	 */
	public function getContentsFile(): string {
		return $this->contentsFile;
	}

	/**
	 * @since 2.5
	 */
	public function setContents( string $contents ): void {
		$this->contents = $contents;
	}

	/**
	 * @since 2.5
	 */
	public function getContents(): string {
		return $this->contents;
	}

	/**
	 * @since 2.5
	 */
	public function addError( array|string $error ): void {
		$this->errors[] = $error;
	}

	/**
	 * @since 2.5
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 */
	public function setOptions( mixed $options ): void {
		$this->options = (array)$options;
	}

	/**
	 * @since 2.5
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @since 2.5
	 */
	public function getOption( string $key ): mixed {
		return $this->options[$key] ?? false;
	}

}
