<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats;

/**
 * @private Package private
 */
class ResultFormat {

	private $name;
	private $nameMessageKey;
	private $parameterDefinitions;
	private $constructionFunction;

	public function __construct( string $name, string $nameMessageKey, array $parameterDefinitions, callable $presenterBuilder ) {
		$this->name = $name;
		$this->nameMessageKey = $nameMessageKey;
		$this->parameterDefinitions = $parameterDefinitions;
		$this->constructionFunction = $presenterBuilder;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getNameMessageKey(): string {
		return $this->nameMessageKey;
	}

	public function getParameterDefinitions(): array {
		return $this->parameterDefinitions;
	}

	public function buildPresenter(): ResultPresenter {
		return call_user_func( $this->constructionFunction );
	}

}
