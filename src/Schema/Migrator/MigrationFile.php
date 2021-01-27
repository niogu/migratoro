<?php

namespace Migratoro\Schema\Migrator;

use Illuminate\Support\Str;

class MigrationFile
{
    private static $migrationNumber = 0;

    public static function build(TableDefinition $t, $isChange = false)
    {
        $classes = self::getMigrationClasses();

        do {
            $prefix = $isChange ? 'Update' : 'Create';
            $endsWith = Str::ucfirst(self::generateRandomString());
            $studlyName = Str::studly($t->getName());

            $class = "{$prefix}{$studlyName}Table{$endsWith}";

        } while (isset($classes[$class]) || class_exists($class) || self::classFileExists($class));

        $filename = sprintf('%s%03d_%s.php', date('Y_m_d_His'), self::$migrationNumber, Str::snake($class));
        $contents = require __DIR__.'/migration_template.php';
        self::$migrationNumber++;

        return [$filename, $contents];
    }

    /**
     * @param $class
     *
     * @return bool
     */
    public static function classFileExists($class): bool
    {
        return count(glob(database_path('migrations/*_'.Str::snake($class).'.php'))) > 0;
    }

    /**
     * @return array
     */
    public static function getMigrationClasses(): array
    {
        $classes = [];
        $m = [];
        foreach (glob(database_path('migrations/*_*.php')) as $file) {
            if (preg_match('/class\s*(.*?)\s/', file_get_contents($file), $m)) {
                $classes[$m[1]] = true;
            }
        }

        return $classes;
    }

    private static function generateRandomString(): string
    {
        $string = Str::lower(Str::random(8));
        $charFromString = preg_replace('/\d+/u', '', $string)[0] ?? 'a';

        return $charFromString . $string;
    }
}
