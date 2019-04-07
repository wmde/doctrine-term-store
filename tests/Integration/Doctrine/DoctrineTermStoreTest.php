<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\DoctrineTermStore;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;

/**
 * @covers \Wikibase\TermStore\DoctrineTermStore
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator
 */
class DoctrineTermStoreTest extends TestCase {

	/* private */ const PREFIX = 'prefix_';

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var TableNames
	 */
	private $tableNames;

	public function setUp() {
		$this->connection = DriverManager::getConnection( [
			'driver' => 'pdo_sqlite',
			'memory' => true,
		] );

		$this->tableNames = new TableNames( 'prefix_' );
	}

	public function testInstallCreatesTables() {
		$this->newTermStore()->install();

		$this->assertTableExists( $this->tableNames->itemTerms() );
		$this->assertTableExists( $this->tableNames->propertyTerms() );
		$this->assertTableExists( $this->tableNames->termInLanguage() );
		$this->assertTableExists( $this->tableNames->textInLanguage() );
		$this->assertTableExists( $this->tableNames->text() );
	}

	private function newTermStore(): DoctrineTermStore {
		return new DoctrineTermStore( $this->connection, self::PREFIX );
	}

	private function assertTableExists( string $tableName ) {
		$this->assertTrue(
			$this->connection->getSchemaManager()->tablesExist( $tableName ),
			'Table "' . $tableName . '" should exist'
		);
	}

	public function testInstallCreatesItemTermsColumns() {
		$this->newTermStore()->install();

		$columns = $this->connection->getSchemaManager()->listTableColumns( $this->tableNames->itemTerms() );

		$this->assertTrue( $columns['wbit_id']->getAutoincrement(), 'id column should have auto increment' );
		$this->assertTrue( $columns['wbit_id']->getNotnull(), 'id column should not be nullable' );
		$this->assertContains(
			$columns['wbit_id']->getType()->getName(),
			[ Type::BIGINT, Type::INTEGER ],
			'id column should have BIGINT or INTEGER type'
		);
	}

	public function testInstallCreatesItemTermsIndexes() {
		$this->newTermStore()->install();

		$table = $this->connection->getSchemaManager()->listTableDetails( $this->tableNames->itemTerms() );

		$this->assertSame(
			[ 'wbit_id' ],
			$table->getPrimaryKey()->getColumns(),
			'primary key should be on id column'
		);

		$this->assertSame(
			[ 'wbit_item_id' ],
			$table->getIndex( 'prefix_wbt_item_terms_item_id' )->getColumns(),
			'prefix_wbt_item_terms_item_id index should exist'
		);

		$this->assertSame(
			[ 'wbit_term_in_lang_id' ],
			$table->getIndex( 'prefix_wbt_item_terms_term_in_lang_id' )->getColumns(),
			'prefix_wbt_item_terms_term_in_lang_id index should exist'
		);
	}

	public function testUninstallDropsTables() {
		$this->newTermStore()->install();
		$this->newTermStore()->uninstall();

		$this->assertTableDoesNotExist( $this->tableNames->itemTerms() );
		$this->assertTableDoesNotExist( $this->tableNames->propertyTerms() );
		$this->assertTableDoesNotExist( $this->tableNames->termInLanguage() );
		$this->assertTableDoesNotExist( $this->tableNames->textInLanguage() );
		$this->assertTableDoesNotExist( $this->tableNames->text() );
	}

	private function assertTableDoesNotExist( string $tableName ) {
		$this->assertFalse(
			$this->connection->getSchemaManager()->tablesExist( $tableName ),
			'Table "' . $tableName . '" should NOT exist'
		);
	}

	public function testCanInstallMultipleStoresInOneDatabaseUsingDifferentPrefixes() {
		( new DoctrineTermStore( $this->connection, 'one_' ) )->install();
		( new DoctrineTermStore( $this->connection, 'two_' ) )->install();

		$this->assertTableExists( ( new TableNames( 'one_' ) )->itemTerms() );
		$this->assertTableExists( ( new TableNames( 'two_' ) )->itemTerms() );
	}

	public function testCanInstallMultipleTimes() {
		$store = $this->newTermStore();

		$store->install();
		$store->install();

		$this->assertTableExists( $this->tableNames->itemTerms() );
	}

}
