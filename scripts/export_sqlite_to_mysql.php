<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$databasePath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

if (! is_file($databasePath)) {
    fwrite(STDERR, "SQLite database not found at: {$databasePath}\n");
    exit(1);
}

$outputDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'exports';
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Failed to create output directory: {$outputDir}\n");
    exit(1);
}

$timestamp = date('Ymd_His');
$outputPath = $outputDir.DIRECTORY_SEPARATOR."database_export_mysql_{$timestamp}.sql";

$pdo = new PDO('sqlite:'.$databasePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$handle = fopen($outputPath, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Cannot write output file: {$outputPath}\n");
    exit(1);
}

fwrite($handle, "-- MariaDB/MySQL compatible dump generated at ".date('c').PHP_EOL);
fwrite($handle, "SET NAMES utf8mb4;".PHP_EOL);
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;".PHP_EOL.PHP_EOL);

$tables = $pdo->query("
    SELECT name, sql
    FROM sqlite_master
    WHERE type = 'table'
      AND name NOT LIKE 'sqlite_%'
    ORDER BY name
")->fetchAll();

if (! is_array($tables)) {
    fclose($handle);
    fwrite(STDERR, "Unable to read table metadata from sqlite_master.\n");
    exit(1);
}

foreach ($tables as $tableMeta) {
    $tableName = (string) $tableMeta['name'];
    fwrite($handle, 'DROP TABLE IF EXISTS `'.$tableName.'`;'.PHP_EOL);
}

fwrite($handle, PHP_EOL);

foreach ($tables as $tableMeta) {
    $tableName = (string) $tableMeta['name'];
    $tableSql = (string) ($tableMeta['sql'] ?? '');

    $columns = $pdo->query('PRAGMA table_info("'.$tableName.'")')->fetchAll();
    $foreignKeys = $pdo->query('PRAGMA foreign_key_list("'.$tableName.'")')->fetchAll();
    $indexes = $pdo->query('PRAGMA index_list("'.$tableName.'")')->fetchAll();

    if (! is_array($columns) || empty($columns)) {
        continue;
    }

    $autoincrementColumns = detectAutoIncrementColumns($tableSql);
    $pkColumns = primaryKeyColumns($columns);

    $lines = [];
    foreach ($columns as $column) {
        $columnName = (string) $column['name'];
        $type = mapSqliteTypeToMysql((string) ($column['type'] ?? ''), $columnName);
        $nullable = ((int) ($column['notnull'] ?? 0) === 1) ? 'NOT NULL' : 'NULL';
        $default = mysqlDefault((string) ($column['dflt_value'] ?? ''), $nullable);

        $auto = '';
        if (in_array($columnName, $autoincrementColumns, true)) {
            $type = 'BIGINT UNSIGNED';
            $nullable = 'NOT NULL';
            $auto = ' AUTO_INCREMENT';
        }

        $lines[] = '  `'.$columnName.'` '.$type.' '.$nullable.$default.$auto;
    }

    if (! empty($pkColumns)) {
        $lines[] = '  PRIMARY KEY ('.implode(', ', array_map(fn (string $name): string => '`'.$name.'`', $pkColumns)).')';
    }

    if (is_array($indexes)) {
        foreach ($indexes as $index) {
            $indexName = (string) ($index['name'] ?? '');
            if ($indexName === '' || str_starts_with($indexName, 'sqlite_autoindex_')) {
                continue;
            }

            if ((int) ($index['origin'] ?? 0) === 'pk') {
                continue;
            }

            $indexInfo = $pdo->query('PRAGMA index_info("'.$indexName.'")')->fetchAll();
            if (! is_array($indexInfo) || empty($indexInfo)) {
                continue;
            }

            $indexColumns = array_map(
                fn (array $part): string => '`'.(string) $part['name'].'`',
                $indexInfo
            );

            if ((int) ($index['unique'] ?? 0) === 1) {
                $lines[] = '  UNIQUE KEY `'.limitIndexName($indexName).'` ('.implode(', ', $indexColumns).')';
            } else {
                $lines[] = '  KEY `'.limitIndexName($indexName).'` ('.implode(', ', $indexColumns).')';
            }
        }
    }

    if (is_array($foreignKeys) && ! empty($foreignKeys)) {
        $groupedForeign = [];
        foreach ($foreignKeys as $fk) {
            $id = (int) ($fk['id'] ?? 0);
            $seq = (int) ($fk['seq'] ?? 0);
            $groupedForeign[$id][$seq] = $fk;
        }

        foreach ($groupedForeign as $id => $parts) {
            ksort($parts);
            $fromColumns = [];
            $toColumns = [];
            $toTable = null;
            $onDelete = null;
            $onUpdate = null;

            foreach ($parts as $part) {
                $fromColumns[] = '`'.(string) $part['from'].'`';
                $toColumns[] = '`'.(string) $part['to'].'`';
                $toTable = (string) $part['table'];
                $onDelete = strtoupper((string) ($part['on_delete'] ?? 'NO ACTION'));
                $onUpdate = strtoupper((string) ($part['on_update'] ?? 'NO ACTION'));
            }

            if ($toTable === null) {
                continue;
            }

            $fkName = limitIndexName($tableName.'_fk_'.$id);
            $line = '  CONSTRAINT `'.$fkName.'` FOREIGN KEY ('.implode(', ', $fromColumns).') REFERENCES `'.$toTable.'` ('.implode(', ', $toColumns).')';
            if ($onDelete !== 'NO ACTION') {
                $line .= ' ON DELETE '.$onDelete;
            }
            if ($onUpdate !== 'NO ACTION') {
                $line .= ' ON UPDATE '.$onUpdate;
            }
            $lines[] = $line;
        }
    }

    fwrite($handle, 'CREATE TABLE `'.$tableName.'` ('.PHP_EOL);
    fwrite($handle, implode(','.PHP_EOL, $lines).PHP_EOL);
    fwrite($handle, ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'.PHP_EOL.PHP_EOL);
}

foreach ($tables as $tableMeta) {
    $tableName = (string) $tableMeta['name'];
    $rowsStmt = $pdo->query('SELECT * FROM "'.$tableName.'"');
    $rows = $rowsStmt?->fetchAll();

    if (! is_array($rows) || empty($rows)) {
        continue;
    }

    $columns = array_keys($rows[0]);
    $columnSql = implode(', ', array_map(fn (string $column): string => '`'.$column.'`', $columns));

    $chunkSize = 250;
    $chunks = array_chunk($rows, $chunkSize);
    foreach ($chunks as $chunk) {
        $valueRows = [];
        foreach ($chunk as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = mysqlLiteral($row[$column] ?? null);
            }
            $valueRows[] = '('.implode(', ', $values).')';
        }

        fwrite(
            $handle,
            'INSERT INTO `'.$tableName.'` ('.$columnSql.') VALUES '.implode(', ', $valueRows).';'.PHP_EOL
        );
    }
    fwrite($handle, PHP_EOL);
}

fwrite($handle, 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL);
fclose($handle);

echo $outputPath.PHP_EOL;

/**
 * @param array<int, array<string, mixed>> $columns
 * @return array<int, string>
 */
function primaryKeyColumns(array $columns): array
{
    $pk = [];
    foreach ($columns as $column) {
        $order = (int) ($column['pk'] ?? 0);
        if ($order > 0) {
            $pk[$order] = (string) $column['name'];
        }
    }
    ksort($pk);

    return array_values($pk);
}

/**
 * @return array<int, string>
 */
function detectAutoIncrementColumns(string $tableSql): array
{
    $matches = [];
    preg_match_all('/["`]([^"`]+)["`]\s+integer\s+primary\s+key\s+autoincrement/i', $tableSql, $matches);
    if (! isset($matches[1]) || ! is_array($matches[1])) {
        return [];
    }

    return array_values(array_unique(array_map('strval', $matches[1])));
}

function mapSqliteTypeToMysql(string $sqliteType, string $columnName): string
{
    $type = strtoupper(trim($sqliteType));
    if ($type === '') {
        $type = 'TEXT';
    }

    if (str_contains($type, 'INT')) {
        return str_ends_with(strtolower($columnName), '_id') || $columnName === 'id'
            ? 'BIGINT UNSIGNED'
            : 'BIGINT';
    }

    if (str_starts_with($type, 'VARCHAR')) {
        if (preg_match('/VARCHAR\s*\((\d+)\)/i', $type, $m)) {
            return 'VARCHAR('.((int) $m[1]).')';
        }

        return 'VARCHAR(255)';
    }

    if (str_contains($type, 'CHAR')) {
        if (preg_match('/CHAR\s*\((\d+)\)/i', $type, $m)) {
            return 'CHAR('.((int) $m[1]).')';
        }

        return 'VARCHAR(255)';
    }

    if (str_contains($type, 'BOOL')) {
        return 'TINYINT(1)';
    }

    if (str_contains($type, 'DATE') && ! str_contains($type, 'TIME')) {
        return 'DATE';
    }

    if (str_contains($type, 'DATETIME') || str_contains($type, 'TIMESTAMP')) {
        return 'DATETIME';
    }

    if ($type === 'TIME') {
        return 'TIME';
    }

    if (str_contains($type, 'DECIMAL') || str_contains($type, 'NUMERIC')) {
        if (preg_match('/(DECIMAL|NUMERIC)\s*\((\d+)\s*,\s*(\d+)\)/i', $type, $m)) {
            return 'DECIMAL('.((int) $m[2]).', '.((int) $m[3]).')';
        }

        return 'DECIMAL(12, 2)';
    }

    if (str_contains($type, 'REAL') || str_contains($type, 'FLOAT') || str_contains($type, 'DOUBLE')) {
        return 'DOUBLE';
    }

    if (str_contains($type, 'JSON')) {
        return 'JSON';
    }

    if (str_contains($type, 'BLOB')) {
        return 'LONGBLOB';
    }

    if (str_contains($type, 'TEXT')) {
        return 'LONGTEXT';
    }

    return 'LONGTEXT';
}

function mysqlDefault(string $sqliteDefault, string $nullable): string
{
    $defaultRaw = trim($sqliteDefault);
    if ($defaultRaw === '') {
        return $nullable === 'NULL' ? ' DEFAULT NULL' : '';
    }

    $upper = strtoupper($defaultRaw);
    if ($upper === 'NULL') {
        return ' DEFAULT NULL';
    }
    if ($upper === 'CURRENT_TIMESTAMP') {
        return ' DEFAULT CURRENT_TIMESTAMP';
    }

    // Keep numeric defaults as numeric; everything else as string literal.
    if (preg_match('/^[+-]?\d+(\.\d+)?$/', $defaultRaw) === 1) {
        return ' DEFAULT '.$defaultRaw;
    }

    $trimmed = trim($defaultRaw, "'\"");
    $escaped = str_replace("'", "''", $trimmed);

    return " DEFAULT '".$escaped."'";
}

function mysqlLiteral(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
        return $value;
    }

    $escaped = str_replace(
        ["\\", "\0", "\n", "\r", "'", "\"", "\x1a"],
        ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
        (string) $value
    );

    return "'".$escaped."'";
}

function limitIndexName(string $name): string
{
    if (strlen($name) <= 60) {
        return $name;
    }

    return substr($name, 0, 48).'_'.substr(md5($name), 0, 11);
}

