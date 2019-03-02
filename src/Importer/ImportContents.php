<?php

namespace SMW\Importer;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImportContents {

	const CONTENT_TEXT = 'text/plain';
	const CONTENT_XML = 'text/xml';

	/**
	 * @var string
	 */
	private $version = '';

	/**
	 * @var string
	 */
	private $description = '';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var integer
	 */
	private $namespace = 0;

	/**
	 * @var string
	 */
	private $contents = '';

	/**
	 * @var string
	 */
	private $contentsFile = '';

	/**
	 * @var string
	 */
	private $contentType = self::CONTENT_TEXT;

	/**
	 * @var string
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @since 2.5
	 *
	 * @param string $version
	 */
	public function setVersion( $version ) {
		$this->version = intval( $version );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $description
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $contentType
	 */
	public function setContentType( $contentType ) {
		$this->contentType = $contentType;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $namespace
	 */
	public function setNamespace( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $contentsFile
	 */
	public function setContentsFile( $contentsFile ) {
		$this->contentsFile = $contentsFile;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getContentsFile() {
		return $this->contentsFile;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $contents
	 */
	public function setContents( $contents ) {
		$this->contents = $contents;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getContents() {
		return $this->contents;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $error
	 */
	public function addError( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $options
	 */
	public function setOptions( $options ) {
		$this->options = (array)$options;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key ) {
		return isset( $this->options[$key] ) ? $this->options[$key] : false;
	}

}
