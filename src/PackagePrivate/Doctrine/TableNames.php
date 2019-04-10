<?php

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

class TableNames {

	/* private */ const ITEM_TERMS = 'wbt_item_terms';
	/* private */ const PROPERTY_TERMS = 'wbt_property_terms';
	/* private */ const TERM_IN_LANGUAGE = 'wbt_term_in_lang';
	/* private */ const TEXT_IN_LANGUAGE = 'wbt_text_in_lang';
	/* private */ const TEXT = 'wbt_text';

	private $tableNamePrefix;

	public function __construct( $tableNamePrefix ) {
		if ( !$this->prefixIsSafe( $tableNamePrefix ) ) {
			throw new \InvalidArgumentException( 'Table name prefix contains forbidden characters' );
		}
		$this->tableNamePrefix = $tableNamePrefix;
	}

	private function prefixIsSafe( $prefix ) {
		$withoutUnderscores = str_replace( '_', '', $prefix );
		return $withoutUnderscores === '' || ctype_alnum( $withoutUnderscores );
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
