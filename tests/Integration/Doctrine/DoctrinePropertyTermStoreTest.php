<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermStore\DoctrineStoreFactory;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore
 */
class DoctrinePropertyTermStoreTest extends TestCase {

	const UNKNOWN_PROPERTY_ID = 'P404';

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var PropertyTermStore
	 */
	private $store;

	/**
	 * @var TableNames
	 */
	private $tableNames;

	public function setUp() {
		$this->connection = DriverManager::getConnection( [
			'driver' => 'pdo_sqlite',
			'memory' => true,
		] );

		$factory = new DoctrineStoreFactory( $this->connection, 'prefix_' );

		$factory->install();

		$this->store = $factory->newPropertyTermStore();
		$this->tableNames = new TableNames( 'prefix_' );
	}

	public function testWhenPropertyIsNotStored_getTermsReturnsEmptyFingerprint() {
		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( new PropertyId( self::UNKNOWN_PROPERTY_ID ) )
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

	public function testStoreTermsUsesExistingRecordsOfOtherProperties() {
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

		$this->assertTableRowCount( 1, $this->tableNames->text() );
		$this->assertTableRowCount( 1, $this->tableNames->textInLanguage() );
		$this->assertTableRowCount( 1, $this->tableNames->termInLanguage() );
	}

	private function assertTableRowCount( $expectedCount, $tableName ) {
		$this->assertSame(
			(string)$expectedCount,
			$this->connection->executeQuery( 'SELECT count(*) as records FROM ' . $tableName )->fetchColumn(),
			'Table ' . $tableName . ' should contain ' . (string)$expectedCount . ' records'
		);
	}

	public function testStoreTermsRemovesOldPropertyTerms() {
		$propertyId = new PropertyId( 'P1' );

		$oldFingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'EnglishLabel' ),
				new Term( 'de', 'ZeGermanLabel' ),
			] )
		);

		$newFingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'EnglishLabel' ),
				new Term( 'fr', 'LeFrenchLabel' ),
			] )
		);

		$this->store->storeTerms( $propertyId, $oldFingerprint );
		$this->store->storeTerms( $propertyId, $newFingerprint );

		$this->assertEquals(
			$newFingerprint,
			$this->store->getTerms( $propertyId )
		);
	}

	public function testGetTermsThrowsExceptionOnInfrastructureFailure() {
		$store = $this->newStoreWithThrowingConnection();

		$this->expectException( TermStoreException::class );
		$store->getTerms( new PropertyId( 'P1' ) );
	}

	private function newStoreWithThrowingConnection(): PropertyTermStore {
		return ( new DoctrineStoreFactory( $this->newThrowingDoctrineConnection(), '' ) )->newPropertyTermStore();
	}

	private function newThrowingDoctrineConnection(): Connection {
		$connection = $this->createMock( Connection::class );

		$connection->method( $this->anything() )
			->willThrowException( new DBALException() );

		return $connection;
	}

	public function testStoreTermsThrowsExceptionOnInfrastructureFailure() {
		$store = $this->newStoreWithThrowingConnection();

		$this->expectException( TermStoreException::class );
		$store->storeTerms( new PropertyId( 'P1' ), $this->newFingerprintWithManyTerms() );
	}

	public function testDeleteTermsThrowsExceptionOnInfrastructureFailure() {
		$store = $this->newStoreWithThrowingConnection();

		$this->expectException( TermStoreException::class );
		$store->deleteTerms( new PropertyId( 'P1' ) );
	}

	// TODO: deletion cleanup

}
