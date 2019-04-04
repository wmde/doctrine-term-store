<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\DoctrineStoreFactory;
use Wikibase\TermStore\PackagePrivate\Doctrine\TableNames;

/**
 * @covers \Wikibase\TermStore\DoctrineStoreFactory
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator
 */
class DoctrineStoreFactoryTest extends TestCase {

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
		$this->newStoreFactory()->install();

		$this->assertTableExists( $this->tableNames->itemTerms() );
		$this->assertTableExists( $this->tableNames->propertyTerms() );
		$this->assertTableExists( $this->tableNames->termInLanguage() );
		$this->assertTableExists( $this->tableNames->textInLanguage() );
		$this->assertTableExists( $this->tableNames->text() );
	}

	private function newStoreFactory(): DoctrineStoreFactory {
		return new DoctrineStoreFactory( $this->connection, self::PREFIX );
	}

	private function assertTableExists( string $tableName ) {
		$this->assertTrue(
			$this->connection->getSchemaManager()->tablesExist( $tableName ),
			'Table "' . $tableName . '" should exist'
		);
	}

	public function testInstallCreatesItemTermsColumns() {
		$this->newStoreFactory()->install();

		$columns = $this->connection->getSchemaManager()->listTableColumns( $this->tableNames->itemTerms() );

		$this->assertTrue( $columns['id']->getAutoincrement(), 'id column should have auto increment' );
		$this->assertTrue( $columns['id']->getNotnull(), 'id column should not be nullable' );
		$this->assertContains(
			$columns['id']->getType()->getName(),
			[ Type::BIGINT, Type::INTEGER ],
			'id column should have BIGINT or INTEGER type'
		);
	}

	public function testInstallCreatesItemTermsIndexes() {
		$this->newStoreFactory()->install();

		$table = $this->connection->getSchemaManager()->listTableDetails( $this->tableNames->itemTerms() );

		$this->assertSame(
			[ 'id' ],
			$table->getPrimaryKey()->getColumns(),
			'primary key should be on id column'
		);

		$this->assertSame(
			[ 'item_id' ],
			$table->getIndex( 'prefix_wbt_item_terms_item_id' )->getColumns(),
			'prefix_wbt_item_terms_item_id index should exist'
		);

		$this->assertSame(
			[ 'term_in_lang_id' ],
			$table->getIndex( 'prefix_wbt_item_terms_term_in_lang_id' )->getColumns(),
			'prefix_wbt_item_terms_term_in_lang_id index should exist'
		);
	}

	public function testUninstallDropsTables() {
		$this->newStoreFactory()->install();
		$this->newStoreFactory()->uninstall();

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
		( new DoctrineStoreFactory( $this->connection, 'one_' ) )->install();
		( new DoctrineStoreFactory( $this->connection, 'two_' ) )->install();

		$this->assertTableExists( ( new TableNames( 'one_' ) )->itemTerms() );
		$this->assertTableExists( ( new TableNames( 'two_' ) )->itemTerms() );
	}

}
