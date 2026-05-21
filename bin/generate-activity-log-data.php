<?php

declare(strict_types=1);

$jsonPath = dirname(__DIR__) . '/var/fixture-export/activity_log.json';
$outPath = dirname(__DIR__) . '/src/DataFixtures/Data/ActivityLogData.php';

if (!is_file($jsonPath)) {
    fwrite(STDERR, "Run bin/export-fixture-data.php first.\n");
    exit(1);
}

$rows = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

$export = var_export($rows, true);
$php = <<<PHP
<?php

declare(strict_types=1);

namespace App\DataFixtures\Data;

/**
 * Exported from local database activity_log table.
 */
final class ActivityLogData
{
    public static function rows(): array
    {
        return {$export};
    }
}

PHP;

if (!is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0777, true);
}

file_put_contents($outPath, $php);
echo "Written {$outPath} (" . count($rows) . " rows)\n";
