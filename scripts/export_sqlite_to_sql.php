<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$databasePath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

if (! is_file($databasePath)) {
    fwrite(STDERR, "Database file not found: {$databasePath}\n");
    exit(1);
}

$outputDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'exports';
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
    exit(1);
}

$timestamp = date('Ymd_His');
$outputPath = $outputDir.DIRECTORY_SEPARATOR."database_export_{$timestamp}.sql";

$pdo = new PDO('sqlite:'.$databasePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$handle = fopen($outputPath, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Unable to open output file: {$outputPath}\n");
    exit(1);
}

fwrite($handle, "-- Database export generated at ".date('c').PHP_EOL);
fwrite($handle, "PRAGMA foreign_keys=OFF;".PHP_EOL);
fwrite($handle, "BEGIN TRANSACTION;".PHP_EOL.PHP_EOL);

$schemaRows = $pdo->query("
    SELECT type, name, tbl_name, sql
    FROM sqlite_master
    WHERE sql IS NOT NULL
      AND name NOT LIKE 'sqlite_%'
    ORDER BY
      CASE type
        WHEN 'table' THEN 1
        WHEN 'index' THEN 2
        WHEN 'trigger' THEN 3
        WHEN 'view' THEN 4
        ELSE 5
      END,
      name
")->fetchAll();

$tableNames = [];
foreach ($schemaRows as $row) {
    $type = (string) $row['type'];
    $name = (string) $row['name'];
    $sql = trim((string) $row['sql']);

    fwrite($handle, $sql.';'.PHP_EOL);

    if ($type === 'table') {
        $tableNames[] = $name;
    }
}

fwrite($handle, PHP_EOL);

foreach ($tableNames as $tableName) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM "'.$tableName.'"')->fetchColumn();
    if ($count === 0) {
        continue;
    }

    $rows = $pdo->query('SELECT * FROM "'.$tableName.'"');

    while (($row = $rows->fetch()) !== false) {
        $columns = array_keys($row);
        $columnSql = implode(', ', array_map(static fn (string $column): string => '"'.$column.'"', $columns));
        $valueSql = implode(', ', array_map(
            static function ($value) use ($pdo): string {
                if ($value === null) {
                    return 'NULL';
                }
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }
                if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
                    return $value;
                }

                return (string) $pdo->quote((string) $value);
            },
            array_values($row)
        ));

        fwrite(
            $handle,
            'INSERT INTO "'.$tableName.'" ('.$columnSql.') VALUES ('.$valueSql.');'.PHP_EOL
        );
    }

    fwrite($handle, PHP_EOL);
}

fwrite($handle, 'COMMIT;'.PHP_EOL);
fclose($handle);

echo $outputPath.PHP_EOL;
