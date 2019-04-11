<?php

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;

class NormalizedStore {

	private $connection;
	private $tableNames;

	public function __construct( Connection $connection, TableNames $tableNames ) {
		$this->connection = $connection;
		$this->tableNames = $tableNames;
	}

	public function acquireTermInLanguageId( Term $term, $termType ) {
		$textInLanguageId = $this->acquireTextInLanguageId( $term );

		$id = $this->findExistingTermInLanguageId( $termType, $textInLanguageId );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTermInLanguageRecord( $termType, $textInLanguageId );

		return $this->connection->lastInsertId();
	}

	private function findExistingTermInLanguageId( $termType, $textInLanguageId ) {
		$record = $this->connection->executeQuery(
			'SELECT wbtl_id FROM ' . $this->tableNames->termInLanguage() . ' WHERE wbtl_type_id = ? AND wbtl_text_in_lang_id = ?',
			[ $termType, $textInLanguageId ],
			[ \PDO::PARAM_INT, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['wbtl_id'] : false;
	}

	private function insertTermInLanguageRecord( $termType, $textInLanguageId ) {
		$this->connection->insert(
			$this->tableNames->termInLanguage(),
			[
				'wbtl_type_id' => $termType,
				'wbtl_text_in_lang_id ' => $textInLanguageId,
			]
		);
	}

	private function acquireTextInLanguageId( Term $term ) {
		$textId = $this->acquireTextId( $term );

		$id = $this->findExistingTextInLanguageId( $term, $textId );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTextInLanguageRecord( $term, $textId );

		return $this->connection->lastInsertId();
	}

	private function findExistingTextInLanguageId( Term $term, $textId ) {
		$record = $this->connection->executeQuery(
			'SELECT wbxl_id FROM ' . $this->tableNames->textInLanguage() . ' WHERE wbxl_language = ? AND wbxl_text_id = ?',
			[ $term->getLanguageCode(), $textId ],
			[ \PDO::PARAM_STR, \PDO::PARAM_INT ]
		)->fetch();

		return is_array( $record ) ? $record['wbxl_id'] : false;
	}

	private function insertTextInLanguageRecord( Term $term, $textId ) {
		$this->connection->insert(
			$this->tableNames->textInLanguage(),
			[
				'wbxl_language' => $term->getLanguageCode(),
				'wbxl_text_id' => $textId,
			]
		);
	}

	private function acquireTextId( Term $term ) {
		$id = $this->findExistingTextId( $term );

		if ( $id !== false ) {
			return $id;
		}

		$this->insertTextRecord( $term );

		return $this->connection->lastInsertId();
	}

	private function findExistingTextId( Term $term ) {
		$record = $this->connection->executeQuery(
			'SELECT wbx_id FROM ' . $this->tableNames->text() . ' WHERE wbx_text = ?',
			[ $term->getText() ],
			[ \PDO::PARAM_STR ]
		)->fetch();

		return is_array( $record ) ? $record['wbx_id'] : false;
	}

	private function insertTextRecord( Term $term ) {
		$this->connection->insert(
			$this->tableNames->text(),
			[
				'wbx_text' => $term->getText(),
			]
		);
	}

	public function getFingerprint( Statement $statement ): Fingerprint {
		return $this->recordsToFingerprint(
			$statement->fetchAll( \PDO::FETCH_OBJ )
		);
	}

	private function recordsToFingerprint( array $termRecords ): Fingerprint {
		$fingerprint = new Fingerprint();

		$aliasGroups = [];

		foreach ( $termRecords as $term ) {
			switch ( $term->wbtl_type_id ) {
				case TermType::LABEL:
					$fingerprint->setLabel( $term->wbxl_language, $term->wbx_text );
					break;
				case TermType::DESCRIPTION:
					$fingerprint->setDescription( $term->wbxl_language, $term->wbx_text );
					break;
				case TermType::ALIAS:
					if ( !array_key_exists( $term->wbxl_language, $aliasGroups ) ) {
						$aliasGroups[$term->wbxl_language] = [];
					}

					$aliasGroups[$term->wbxl_language][] = $term->wbx_text;

					break;
			}
		}

		foreach ( $aliasGroups as $language => $aliases ) {
			$fingerprint->setAliasGroup( $language, $aliases );
		}

		return $fingerprint;
	}

}