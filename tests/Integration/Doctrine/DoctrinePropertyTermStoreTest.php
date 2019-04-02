<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermStore\DoctrineStoreFactory;
use Wikibase\TermStore\PackagePrivate\Doctrine\Tables;
use Wikibase\TermStore\PropertyTermStore;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore
 */
class DoctrinePropertyTermStoreTest extends TestCase {

	const UNKNOWN_PROPERTY_ID = 'P404';

	/**
	 * @var PropertyTermStore
	 */
	private $store;

	/**
	 * @var Connection
	 */
	private $connection;

	public function setUp() {
		$this->connection = DriverManager::getConnection( [
			'driver' => 'pdo_sqlite',
			'memory' => true,
		] );

		$factory = new DoctrineStoreFactory( $this->connection );

		$factory->createSchema();

		$this->store = $factory->newPropertyTermStore();
	}

	public function testWhenPropertyIsNotStored_getTermsReturnsEmptyFingerprint() {
		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( new PropertyId( self::UNKNOWN_PROPERTY_ID ) )
		);
	}

	public function testTempPropertyTerms() {
		$propertyId = new PropertyId( 'P1' );
		$fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'EnglishLabel' ) ] )
		);

		$this->store->storeTerms( $propertyId, $fingerprint );

		$this->assertEquals(
			[
				'id' => 1,
				'property_id' => 1,
				'term_in_lang_id' => 1,
			],
			$this->connection->executeQuery( 'SELECT * FROM wbt_property_terms' )->fetch()
		);
	}

	/**
	 * @dataProvider fingerprintProvider
	 */
	public function testFingerprintRoundtrip( Fingerprint $fingerprint ) {
		$propertyId = new PropertyId( 'P1' );

		$this->store->storeTerms( $propertyId, $fingerprint );

		$this->assertEquals(
			$fingerprint,
			$this->store->getTerms( $propertyId )
		);
	}

	public function fingerprintProvider(): \Iterator {
		yield 'one label' => [
			new Fingerprint(
				new TermList( [ new Term( 'en', 'EnglishLabel' ) ] )
			)
		];

		yield 'one description' => [
			new Fingerprint(
				null,
				new TermList( [ new Term( 'de', 'ZeGermanDescription' ) ] )
			)
		];

		yield 'one alias' => [
			new Fingerprint(
				null,
				null,
				new AliasGroupList( [
					new AliasGroup( 'fr', [ 'LeFrenchAlias' ] )
				] )
			)
		];

		yield 'multiple terms' => [
			$this->newFingerprintWithManyTerms()
		];
	}

	private function newFingerprintWithManyTerms(): Fingerprint {
		return new Fingerprint(
			new TermList( [
				new Term( 'en', 'EnglishLabel' ),
				new Term( 'de', 'ZeGermanLabel' ),
				new Term( 'fr', 'LeFrenchLabel' ),
			] ),
			new TermList( [
				new Term( 'en', 'EnglishDescription' ),
				new Term( 'de', 'ZeGermanDescription' ),
			] ),
			new AliasGroupList( [
				new AliasGroup( 'fr', [ 'LeFrenchAlias', 'LaFrenchAlias' ] ),
				new AliasGroup( 'en', [ 'EnglishAlias' ] ),
			] )
		);
	}

	public function testOnlyTermsOfThePropertyAreReturned() {
		$propertyId = new PropertyId( 'P1' );
		$terms = new Fingerprint(
			new TermList( [
				new Term( 'de', 'ZeGermanLabel' ),
			] ),
			new TermList( [
				new Term( 'de', 'ZeGermanDescription' ),
			] ),
			new AliasGroupList( [
				new AliasGroup( 'de', [ 'ZeGermanAlias' ] ),
			] )
		);

		$this->store->storeTerms(
			$propertyId,
			$terms
		);

		$this->store->storeTerms(
			new PropertyId( 'P2' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishLabel' ),
				] ),
				new TermList( [
					new Term( 'en', 'EnglishDescription' ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'EnglishAlias' ] ),
				] )
			)
		);

		$this->assertEquals(
			$terms,
			$this->store->getTerms( $propertyId )
		);
	}

	public function testDeletionRemovesReturnsOfTarget() {
		$propertyId = new PropertyId( 'P1' );

		$this->store->storeTerms(
			$propertyId,
			$this->newFingerprintWithManyTerms()
		);

		$this->store->deleteTerms( $propertyId );

		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( $propertyId )
		);
	}

	public function testDeletionOnlyRemovesTargetTerms() {
		$propertyId = new PropertyId( 'P1' );

		$this->store->storeTerms(
			$propertyId,
			$this->newFingerprintWithManyTerms()
		);

		$this->store->deleteTerms( new PropertyId( 'P2' ) );

		$this->assertEquals(
			$this->newFingerprintWithManyTerms(),
			$this->store->getTerms( $propertyId )
		);
	}

	public function testInsertionUsesExistingRecordsOfOtherProperties() {
		$fingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'EnglishLabel' ),
			] )
		);

		$this->store->storeTerms( new PropertyId( 'P1' ), $fingerprint );
		$this->store->storeTerms( new PropertyId( 'P2' ), $fingerprint );

		$this->assertEquals(
			$fingerprint,
			$this->store->getTerms( new PropertyId( 'P2' ) )
		);

		$this->assertTableRowCount( 1, Tables::TEXT );
		$this->assertTableRowCount( 1, Tables::TEXT_IN_LANGUAGE );
		$this->assertTableRowCount( 1, Tables::TERM_IN_LANGUAGE );
	}

	private function assertTableRowCount( $expectedCount, $tableName ) {
		$this->assertSame(
			(string)$expectedCount,
			$this->connection->executeQuery( 'SELECT count(*) as records FROM ' . $tableName )->fetchColumn(),
			'Table ' . $tableName . ' should contain ' . (string)$expectedCount . ' records'
		);
	}

	// TODO: insertion of existing elements
	// TODO: update
	// TODO: deletion cleanup
	// TODO: infra failures

}
