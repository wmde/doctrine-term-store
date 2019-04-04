<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

class TableNames {

	/* private */ const ITEM_TERMS = 'wbt_item_terms';
	/* private */ const PROPERTY_TERMS = 'wbt_property_terms';
	/* private */ const TERM_IN_LANGUAGE = 'wbt_term_in_lang';
	/* private */ const TEXT_IN_LANGUAGE = 'wbt_text_in_lang';
	/* private */ const TEXT = 'wbt_text';

	private $tableNamePrefix;

	public function __construct( $tableNamePrefix ) {
		$this->tableNamePrefix = $tableNamePrefix;
	}

	public function itemTerms() {
		return $this->tableNamePrefix . self::ITEM_TERMS;
	}

	public function propertyTerms() {
		return $this->tableNamePrefix . self::PROPERTY_TERMS;
	}

	public function termInLanguage() {
		return $this->tableNamePrefix . self::TERM_IN_LANGUAGE;
	}

	public function textInLanguage() {
		return $this->tableNamePrefix . self::TEXT_IN_LANGUAGE;
	}

	public function text() {
		return $this->tableNamePrefix . self::TEXT;
	}

	public function prefix( $string ) {
		return $this->tableNamePrefix . $string;
	}

}
