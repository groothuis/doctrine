<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class MySqlPlatformTest extends AbstractPlatformTestCase
{
    public function createPlatform()
    {
        return new MysqlPlatform;
    }

    public function testGenerateMixedCaseTableCreate()
    {
        $table = new \Doctrine\DBAL\Schema\Table("Foo");
        $table->createColumn("Bar", "integer");

        $sql = $this->_platform->getCreateTableSql($table);
        $this->assertEquals('CREATE TABLE Foo (Bar INT NOT NULL) ENGINE = InnoDB', array_shift($sql));
    }

    public function getGenerateTableSql()
    {
        return 'CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, test VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) ENGINE = InnoDB';
    }

    public function getGenerateTableWithMultiColumnUniqueIndexSql()
    {
        return array(
            'CREATE TABLE test (foo VARCHAR(255) DEFAULT NULL, bar VARCHAR(255) DEFAULT NULL, UNIQUE INDEX test_foo_bar_uniq (foo, bar)) ENGINE = InnoDB'
        );
    }

    public function getGenerateAlterTableSql()
    {
        return array(
            "ALTER TABLE mytable RENAME TO userlist, ADD quota INT DEFAULT NULL, DROP foo, CHANGE bar baz VARCHAR(255) DEFAULT 'def' NOT NULL"
        );
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
        $this->assertEquals('`', $this->_platform->getIdentifierQuoteCharacter(), 'Quote character is not correct');
        $this->assertEquals('CONCAT(column1, column2, column3)', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation function is not correct');
    }

    public function testGeneratesTransactionsCommands()
    {
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED),
            ''
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED)
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE)
        );
    }


    public function testGeneratesDDLSnippets()
    {
        $this->assertEquals('SHOW DATABASES', $this->_platform->getShowDatabasesSql());
        $this->assertEquals('CREATE DATABASE foobar', $this->_platform->getCreateDatabaseSql('foobar'));
        $this->assertEquals('DROP DATABASE foobar', $this->_platform->getDropDatabaseSql('foobar'));
        $this->assertEquals('DROP TABLE foobar', $this->_platform->getDropTableSql('foobar'));
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'INT',
            $this->_platform->getIntegerTypeDeclarationSql(array())
        );
        $this->assertEquals(
            'INT AUTO_INCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'INT AUTO_INCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(
                array('autoincrement' => true, 'primary' => true)
        ));
    }

    public function testGeneratesTypeDeclarationForStrings()
    {
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSql(
                array('length' => 10, 'fixed' => true)
        ));
        $this->assertEquals(
            'VARCHAR(50)',
            $this->_platform->getVarcharTypeDeclarationSql(array('length' => 50)),
            'Variable string declaration is not correct'
        );
        $this->assertEquals(
            'VARCHAR(255)',
            $this->_platform->getVarcharTypeDeclarationSql(array()),
            'Long string declaration is not correct'
        );
    }

    public function testPrefersIdentityColumns()
    {
        $this->assertTrue($this->_platform->prefersIdentityColumns());
    }

    public function testSupportsIdentityColumns()
    {
        $this->assertTrue($this->_platform->supportsIdentityColumns());
    }

    public function testDoesNotSupportSavePoints()
    {
        $this->assertFalse($this->_platform->supportsSavepoints());   
    }

    public function getGenerateIndexSql()
    {
        return 'CREATE INDEX my_idx ON mytable (user_name, last_login)';
    }

    public function getGenerateUniqueIndexSql()
    {
        return 'CREATE UNIQUE INDEX index_name ON test (test, test2)';
    }

    public function getGenerateForeignKeySql()
    {
        return 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)';
    }

    public function testModifyLimitQuery()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10, 0);
        $this->assertEquals('SELECT * FROM user LIMIT 10 OFFSET 0', $sql);
    }

    public function testModifyLimitQueryWithEmptyOffset()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10);
        $this->assertEquals('SELECT * FROM user LIMIT 10', $sql);
    }

    /**
     * @group DDC-118
     */
    public function testGetDateTimeTypeDeclarationSql()
    {
        $this->assertEquals("DATETIME", $this->_platform->getDateTimeTypeDeclarationSql(array('version' => false)));
        $this->assertEquals("TIMESTAMP", $this->_platform->getDateTimeTypeDeclarationSql(array('version' => true)));
        $this->assertEquals("DATETIME", $this->_platform->getDateTimeTypeDeclarationSql(array()));
    }
}