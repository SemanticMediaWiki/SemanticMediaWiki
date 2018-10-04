<?php

namespace SMW\Rule;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Rule {

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var []
	 */
	private $if = [];

	/**
	 * @var []
	 */
	private $then = [];

	/**
	 * @var []
	 */
	private $dependencies = [];

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 */
	public function __construct( $name, array $if, array $then, array $dependencies = [] ) {
		$this->name = $name;
		$this->if = $if;
		$this->then = $then;
		$this->dependencies = $dependencies;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * @note < 7.1 unexpected 'if' (T_IF), expecting identifier (T_STRING) ...
	 *
	 * @since 3.0
	 *
	 * @return []
	 */
	public function when( $key = null ) {

		if ( $key === null ) {
			return $this->if;
		}

		if ( isset( $this->if[$key] ) ) {
			return $this->if[$key];
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function then( $key = null ) {

		if ( $key === null ) {
			return $this->then;
		}

		if ( isset( $this->then[$key] ) ) {
			return $this->then[$key];
		}

		return [];
	}

}
