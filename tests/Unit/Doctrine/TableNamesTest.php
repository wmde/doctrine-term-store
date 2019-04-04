<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Unit\Doctrine;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\TableNames
 */
class TableNamesTest extends TestCase {

	public function testNoPrefix() {
		$this->assertSame(
			'wbt_text',
			( new TableNames( '' ) )->text()
		);
	}

	public function testTextTableGetsPrefixed() {
		$this->assertSame(
			'prefix_wbt_text',
			( new TableNames( 'prefix_' ) )->text()
		);
	}

	public function testTextInLanguageTableGetsPrefixed() {
		$this->assertSame(
			'prefix_wbt_text_in_lang',
			( new TableNames( 'prefix_' ) )->textInLanguage()
		);
	}

	public function testTermInLanguageTableGetsPrefixed() {
		$this->assertSame(
			'prefix_wbt_term_in_lang',
			( new TableNames( 'prefix_' ) )->termInLanguage()
		);
	}

	public function testPropertyTermsTableGetsPrefixed() {
		$this->assertSame(
			'prefix_wbt_property_terms',
			( new TableNames( 'prefix_' ) )->propertyTerms()
		);
	}

	public function testItemTermsTableGetsPrefixed() {
		$this->assertSame(
			'prefix_wbt_item_terms',
			( new TableNames( 'prefix_' ) )->itemTerms()
		);
	}

	/**
	 * @dataProvider invalidPrefixProvider
	 */
	public function testOnlyAlphaNumericPrefixesAreAllowed( string $prefix ) {
		$this->expectException( \InvalidArgumentException::class );
		new TableNames( $prefix );
	}

	public function invalidPrefixProvider() {
		yield [ '-' ];
		yield [ ' ' ];
		yield [ 'abc!' ];
		yield [ 'abc!abc' ];
		yield [ '%&$' ];
		yield [ '"' ];
		yield [ "'" ];
		yield [ '\\' ];
	}

}
