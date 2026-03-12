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
$outputPath = $outputDir.DIRECTORY_SEPARATOR."database_data_only_mysql_{$timestamp}.sql";

$pdo = new PDO('sqlite:'.$databasePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$handle = fopen($outputPath, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Cannot write output file: {$outputPath}\n");
    exit(1);
}

fwrite($handle, "-- MySQL/MariaDB data-only dump generated at ".date('c').PHP_EOL);
fwrite($handle, "-- No schema statements included.".PHP_EOL);
fwrite($handle, "SET NAMES utf8mb4;".PHP_EOL);
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;".PHP_EOL.PHP_EOL);

$tables = $pdo->query("
    SELECT name
    FROM sqlite_master
    WHERE type = 'table'
      AND name NOT LIKE 'sqlite_%'
    ORDER BY name
")->fetchAll(PDO::FETCH_COLUMN);

if (! is_array($tables)) {
    fclose($handle);
    fwrite(STDERR, "Unable to read table list.\n");
    exit(1);
}

foreach ($tables as $tableName) {
    fwrite($handle, 'DELETE FROM `'.$tableName.'`;'.PHP_EOL);
}

fwrite($handle, PHP_EOL);

foreach ($tables as $tableName) {
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

fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;".PHP_EOL);
fclose($handle);

echo $outputPath.PHP_EOL;

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

