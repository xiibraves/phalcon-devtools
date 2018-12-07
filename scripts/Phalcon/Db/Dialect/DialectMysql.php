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

namespace Phalcon\Db\Dialect;

use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect\DialectMysql
 *
 * @package Phalcon\Db\Dialect
 */
class DialectMysql extends Mysql
{
    /**
     * Generates SQL to add an foreign key to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ReferenceInterface $reference
     * @return string
     */
    public function addForeignKey($tableName, $schemaName, ReferenceInterface $reference)
    {
        $sql = 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ADD';
        if ($reference->getName()) {
            $sql .= ' CONSTRAINT `' . $reference->getName() . '`';
        }
        $sql .= ' FOREIGN KEY (' . $this->getColumnList($reference->getColumns()) . ') REFERENCES ' .
            $this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()) . '(' .
            $this->getColumnList($reference->getReferencedColumns()) . ')';

        $onDelete = $reference->getOnDelete();
        if ($onDelete) {
            $sql .= " ON DELETE " . $onDelete;
        }

        $onUpdate = $reference->getOnUpdate();
        if ($onUpdate) {
            $sql .= " ON UPDATE " . $onUpdate;
        }

        return $sql;
    }

    /**
     * Generates SQL to check DB parameter FOREIGN_KEY_CHECKS.
     *
     * @return string
     */
    public function getForeignKeyChecks()
    {
        $sql = 'SELECT @@foreign_key_checks';

        return $sql;
    }

    /**
     * Gets the column name in MySQL
     */
    public function getColumnDefinition($column) {
        $columnSql = "";

        $type = $column->getType();
        if (gettype($type) == "string") {
            $columnSql .= $type;
            $type = $column->getTypeReference();
        }

        switch ($type) {

            case Column::TYPE_INTEGER:
                if (!$columnSql) {
                    $columnSql .= "INT";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;
            case Column::TYPE_DATE:
                if (!$columnSql) {
                    $columnSql .= "DATE";
                }
                break;
            case Column::TYPE_VARCHAR:
                if (!$columnSql) {
                    $columnSql .= "VARCHAR";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                break;
            case Column::TYPE_DECIMAL:
                if (!$columnSql) {
                    $columnSql .= "DECIMAL";
                }
                $columnSql .= "(" . $column->getSize() . "," . $column->getScale() . ")";
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;
            case Column::TYPE_DATETIME:
                if (!$columnSql) {
                    $columnSql .= "DATETIME";
                }
                break;
            case Column::TYPE_TIMESTAMP:
                if (!$columnSql) {
                    $columnSql .= "TIMESTAMP";
                }
                break;
            case Column::TYPE_CHAR:
                if (!$columnSql) {
                    $columnSql .= "CHAR";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                break;
            case Column::TYPE_TEXT:
                if (!$columnSql) {
                    $columnSql .= "TEXT";
                }
                break;
            case Column::TYPE_BOOLEAN:
                if (!$columnSql) {
                    $columnSql .= "TINYINT(1)";
                }
                break;
            case Column::TYPE_FLOAT:
                if (!$columnSql) {
                    $columnSql .= "FLOAT";
                }
                $size = $column->getSize();
                if ($size) {
                    $scale = $column->getScale();
                    if ($scale) {
                        $columnSql .= "(" . $size . "," . $scale . ")";
                    } else {
                        $columnSql .= "(" . $size . ")";
                    }
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;
             case Column::TYPE_DOUBLE:
                if (!$columnSql) {
                    $columnSql .= "DOUBLE";
                }
                $size = $column->getSize();
                if ($size) {
                    $scale = $column->getScale();
                    $columnSql .= "(" . $size;
                    if ($scale) {
                        $columnSql .= "," . $scale . ")";
                    } else {
                        $columnSql .= ")";
                    }
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;
             case Column::TYPE_BIGINTEGER:
                if (!$columnSql) {
                    $columnSql .= "BIGINT";
                }
                $scale = $column->getSize();
                if ($scale) {
                    $columnSql .= "(" . $column->getSize() . ")";
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;
            case Column::TYPE_TINYBLOB:
                if (!$columnSql) {
                    $columnSql .= "TINYBLOB";
                }
                break;
            case Column::TYPE_BLOB:
                if (!$columnSql) {
                    $columnSql .= "BLOB";
                }
                break;
            case Column::TYPE_MEDIUMBLOB:
                if (!$columnSql) {
                    $columnSql .= "MEDIUMBLOB";
                }
                break;
            case Column::TYPE_LONGBLOB:
                if (!$columnSql) {
                    $columnSql .= "LONGBLOB";
                }
                break;
            case Column::TYPE_JSON:
                if (!$columnSql) {
                    $columnSql .= "JSON";
                }
                break;
            case Column::TYPE_JSONB:
                if (!$columnSql) {
                    $columnSql .= "JSON";
                }
                break;

            default:
                if (!$columnSql) {
                    throw new Exception("Unrecognized MySQL data type at column " . $column->getName());
                }

                $typeValues = $column->getTypeValues();
                if ($typeValues) {
                    if (gettype($typeValues == "array")) {
                        $valueSql = "";
                        foreach ($typeValues as $value) {
                            $valueSql .= "\"" . addcslashes($value, "\"") . "\", ";

                        }
                        $columnSql .= "(" . substr($valueSql, 0, -2) . ")";
                    } else {
                        $columnSql .= "(\"" . addcslashes($typeValues, "\"") . "\")";
                    }
                }

        }

        return $columnSql;

    }
}
