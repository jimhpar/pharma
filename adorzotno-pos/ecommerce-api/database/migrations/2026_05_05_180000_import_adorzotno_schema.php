<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->statements() as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse($this->tableNames()) as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function statements(): array
    {
        $sql = File::get(database_path('schema/adorzotno.sql'));
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*![\s\S]*?\*\//', '', $sql);
        $sql = preg_replace('/^START TRANSACTION;$/m', '', $sql);
        $sql = preg_replace('/^COMMIT;$/m', '', $sql);

        $statements = preg_split('/;\s*\n/', (string) $sql) ?: [];

        return array_values(array_filter(array_map(function (string $statement) {
            $statement = trim($statement);

            if ($statement === '' || str_contains($statement, '`migrations`')) {
                return null;
            }

            return $statement . ';';
        }, $statements)));
    }

    private function tableNames(): array
    {
        preg_match_all('/CREATE TABLE `([^`]+)`/', File::get(database_path('schema/adorzotno.sql')), $matches);

        return array_values(array_filter($matches[1] ?? [], fn (string $table) => $table !== 'migrations'));
    }
};
