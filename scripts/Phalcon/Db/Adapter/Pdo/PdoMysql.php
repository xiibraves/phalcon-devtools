<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-present Phalcon Team (http://www.phalconphp.com)    |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Sergii Svyrydenko <sergey.v.sviridenko@gmail.com>             |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\Exception;
use Phalcon\Db;
use Phalcon\Db\Column;

/**
 * Phalcon\Db\Adapter\Pdo\PdoMysql
 *
 * @package Phalcon\Db\Adapter\Pdo
 */
class PdoMysql extends Mysql
{
    /**
     * Generates SQL to add an index to a table if FOREIGN_KEY_CHECKS=1
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ReferenceInterface $reference
     *
     * @throws \Phalcon\Db\Exception
     */
    public function addForeignKey($tableName, $schemaName, ReferenceInterface $reference)
    {
        $foreignKeyCheck = $this->{"prepare"}($this->_dialect->getForeignKeyChecks());
        if (!$foreignKeyCheck->execute()) {
            throw new Exception("DATABASE PARAMETER 'FOREIGN_KEY_CHECKS' HAS TO BE 1");
        }

        return $this->{"execute"}($this->_dialect->addForeignKey($tableName, $schemaName, $reference));
    }

    /**
     * Returns an array of Phalcon\Db\Column objects describing a table
     *
     * @param string $table
     * @param string $schema
     *
     * @throws \Phalcon\Db\Exception
     */
    public function describeColumns($table, $schema = NULL) {

        $oldColumn = NULL;
        $sizePattern = "#\\(([0-9]+)(?:,\\s*([0-9]+))*\\)#";
        $columns = [];

        /**
         * Get the SQL to describe a table
         * We're using FETCH_NUM to fetch the columns
         * Get the describe
         * Field Indexes: 0:name, 1:type, 2:not null, 3:key, 4:default, 5:extra
         */
        foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), Db::FETCH_NUM) as $field) {
            /**
             * By default the bind types is two
             */
            $definition = ["bindType" => Column::BIND_PARAM_STR];

            /**
             * By checking every column type we convert it to a Phalcon\Db\Column
             */

            $columnType = $field[1];

            if (strpos($columnType, "enum") !== False) {
                /**
                 * Enum are treated as char
                 */
                $definition["type"] = Column::TYPE_CHAR;
            } elseif (strpos($columnType, "bigint") !== False) {
                /**
                 * Smallint/Bigint/Integers/Int are int
                 */
                $definition["type"] = Column::TYPE_BIGINTEGER;
                $definition["isNumeric"] = true;
                $definition["bindType"] = Column::BIND_PARAM_INT;
            } elseif (strpos($columnType, "int") !== False) {
                /**
                 * Smallint/Bigint/Integers/Int are int
                 */
                $definition["type"] = Column::TYPE_INTEGER;
                $definition["isNumeric"] = true;
                $definition["bindType"] = Column::BIND_PARAM_INT;
            } elseif (strpos($columnType, "varchar") !== False) {
                /**
                 * Varchar are varchars
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
            } elseif (strpos($columnType, "datetime") !== False) {
                /**
                 * Special type for datetime
                 */
                $definition["type"] = Column::TYPE_DATETIME;
            } elseif (strpos($columnType, "char") !== False) {
                /**
                 * Chars are chars
                 */
                $definition["type"] = Column::TYPE_CHAR;
            } elseif (strpos($columnType, "date") !== False) {
                /**
                 * Date are dates
                 */
                $definition["type"] = Column::TYPE_DATE;
            } elseif (strpos($columnType, "timestamp") !== False) {
                /**
                 * Timestamp are dates
                 */
                $definition["type"] = Column::TYPE_TIMESTAMP;
            } elseif (strpos($columnType, "text") !== False) {
                /**
                 * Text are varchars
                 */
                $definition["type"] = Column::TYPE_TEXT;
            } elseif (strpos($columnType, "decimal") !== False) {
                /**
                 * Decimals are floats
                 */
                $definition["type"] = Column::TYPE_DECIMAL;
                $definition["isNumeric"] = true;
                $definition["bindType"] = Column::BIND_PARAM_DECIMAL;
            } elseif (strpos($columnType, "double") !== False) {
                /**
                 * Doubles
                 */
                $definition["type"] = Column::TYPE_DOUBLE;
                $definition["isNumeric"] = true;
                $definition["bindType"] = Column::BIND_PARAM_DECIMAL;
            } elseif (strpos($columnType, "float") !== False) {
                /**
                 * Float/Smallfloats/Decimals are float
                 */
                $definition["type"] = Column::TYPE_FLOAT;
                $definition["isNumeric"] = true;
                $definition["bindType"] = Column::BIND_PARAM_DECIMAL;
            } elseif (strpos($columnType, "bit") !== False) {
                /**
                 * Boolean
                 */
                $definition["type"] = Column::TYPE_BOOLEAN;
                $definition["bindType"] = Column::BIND_PARAM_BOOL;
            } elseif (strpos($columnType, "tinyblob") !== False) {
                /**
                 * Tinyblob
                 */
                $definition["type"] = Column::TYPE_TINYBLOB;
            } elseif (strpos($columnType, "mediumblob") !== False) {
                /**
                 * Mediumblob
                 */
                $definition["type"] = Column::TYPE_MEDIUMBLOB;
            } elseif (strpos($columnType, "longblob") !== False) {
                /**
                 * Longblob
                 */
                $definition["type"] = Column::TYPE_LONGBLOB;
            } elseif (strpos($columnType, "blob") !== False) {
                /**
                 * Blob
                 */
                $definition["type"] = Column::TYPE_BLOB;
            } elseif (strpos($columnType, "json") !== False) {
                /**
                 * Blob
                 */
                $definition["type"] = Column::TYPE_JSON;
            } else {
                /**
                 * By default is string
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
            }

            /**
             * If the column type has a parentheses we try to get the column size from it
             */
            if (strpos($columnType, "(") !== False) {
                $matches = NULL;
                if (preg_match($sizePattern, $columnType, $matches)) {
                    if (isset($matches[1])) {
                        $matchOne = $matches[1];
                        $definition["size"] = (int) $matchOne;
                    }
                    if (isset($matches[2])) {
                        $matchTwo = $matches[2];
                        $definition["scale"] = (int) $matchTwo;
                    }
                }
            }

            /**
             * Check if the column is unsigned, only MySQL support this
             */
            if (strpos($columnType, "unsigned") !== False) {
                $definition["unsigned"] = True;
            }

            /**
             * Positions
             */
            if ($oldColumn == NULL) {
                $definition["first"] = True;
            } else {
                $definition["after"] = $oldColumn;
            }

            /**
             * Check if the field is primary key
             */
            if ($field[3] == "PRI") {
                $definition["primary"] = True;
            }

            /**
             * Check if the column allows NULL VALUES
             */
            if ($field[2] == "NO") {
                $definition["notNull"] = True;
            }

            /**
             * Check if the column is auto increment
             */
            if ($field[5] == "auto_increment") {
                $definition["autoIncrement"] = True;
            }

            /**
             * Check if the column is default values
             */
            if (gettype($field[4]) != "null") {
                $definition["default"] = $field[4];
            }

            /**
             * Every route is stored as a Phalcon\Db\Column
             */
            $columnName = $field[0];
            $columns[] = new Column($columnName, $definition);
            $oldColumn = $columnName;
        }

        return $columns;
    }
}
