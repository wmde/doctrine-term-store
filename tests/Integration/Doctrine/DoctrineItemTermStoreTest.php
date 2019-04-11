<?php

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermStore\DoctrineTermStore;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\TermStoreException;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineItemTermStore
 */
class DoctrineItemTermStoreTest extends TestCase {

	const UNKNOWN_ITEM_ID = 'Q404';

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var ItemTermStore
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

		$factory = new DoctrineTermStore( $this->connection, 'prefix_' );

		$factory->install();

		$this->store = $factory->newItemTermStore();
		$this->tableNames = new TableNames( 'prefix_' );
	}

	public function testWhenItemIsNotStored_getTermsReturnsEmptyFingerprint() {
		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( new ItemId( self::UNKNOWN_ITEM_ID ) )
		);
	}

	/**
	 * @dataProvider fingerprintProvider
	 */
	public function testFingerprintRoundtrip( Fingerprint $fingerprint ) {
		$itemId = new ItemId( 'Q1' );

		$this->store->storeTerms( $itemId, $fingerprint );

		$this->assertEquals(
			$fingerprint,
			$this->store->getTerms( $itemId )
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

	public function testOnlyTermsOfTheItemAreReturned() {
		$itemId = new ItemId( 'Q1' );
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
			$itemId,
			$terms
		);

		$this->store->storeTerms(
			new ItemId( 'Q2' ),
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
			$this->store->getTerms( $itemId )
		);
	}

	public function testDeletionRemovesReturnsOfTarget() {
		$itemId = new ItemId( 'Q1' );

		$this->store->storeTerms(
			$itemId,
			$this->newFingerprintWithManyTerms()
		);

		$this->store->deleteTerms( $itemId );

		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( $itemId )
		);
	}

	public function testDeletionOnlyRemovesTargetTerms() {
		$itemId = new ItemId( 'Q1' );

		$this->store->storeTerms(
			$itemId,
			$this->newFingerprintWithManyTerms()
		);

		$this->store->deleteTerms( new ItemId( 'Q2' ) );

		$this->assertEquals(
			$this->newFingerprintWithManyTerms(),
			$this->store->getTerms( $itemId )
		);
	}

	public function testStoreTermsUsesExistingRecordsOfOtherItems() {
		$fingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'EnglishLabel' ),
			] )
		);

		$this->store->storeTerms( new ItemId( 'Q1' ), $fingerprint );
		$this->store->storeTerms( new ItemId( 'Q2' ), $fingerprint );

		$this->assertEquals(
			$fingerprint,
			$this->store->getTerms( new ItemId( 'Q2' ) )
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

	public function testStoreTermsRemovesOldItemTerms() {
		$itemId = new ItemId( 'Q1' );

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

		$this->store->storeTerms( $itemId, $oldFingerprint );
		$this->store->storeTerms( $itemId, $newFingerprint );

		$this->assertEquals(
			$newFingerprint,
			$this->store->getTerms( $itemId )
		);
	}

	public function testGetTermsThrowsExceptionOnInfrastructureFailure() {
		$store = $this->newStoreWithThrowingConnection();

		$this->expectException( TermStoreException::class );
		$store->getTerms( new ItemId( 'Q1' ) );
	}

	private function newStoreWithThrowingConnection(): ItemTermStore {
		return ( new DoctrineTermStore( $this->newThrowingDoctrineConnection(), '' ) )->newItemTermStore();
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
		$store->storeTerms( new ItemId( 'Q1' ), $this->newFingerprintWithManyTerms() );
	}

	public function testDeleteTermsThrowsExceptionOnInfrastructureFailure() {
		$store = $this->newStoreWithThrowingConnection();

		$this->expectException( TermStoreException::class );
		$store->deleteTerms( new ItemId( 'Q1' ) );
	}

	// TODO: deletion cleanup

}
