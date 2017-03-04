<?php

namespace SMW\Importer;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImportContents {

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
	private $errors = array();

	/**
	 * @var array
	 */
	private $options = array();

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
