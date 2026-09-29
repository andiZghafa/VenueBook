<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\DbDumper\Databases\MySql;
use Illuminate\Support\Facades\File;

#[Signature('backup:raw')]
#[Description('Backup the database directly to a raw .sql file')]
class BackupDatabaseCommand extends Command
{
    public function handle()
    {
        $this->info('Starting raw database backup...');

        $databaseName = config('database.connections.mysql.database');
        $userName = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        
        // Grab the XAMPP path we configured earlier
        $dumpPath = config('database.connections.mysql.dump.dump_binary_path', '');

        $backupDir = storage_path('app/backups');
        
        // Ensure the directory exists
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $date = now()->format('Y-m-d_H-i-s');
        $fileName = "{$databaseName}-{$date}.sql";
        $filePath = "{$backupDir}/{$fileName}";

        try {
            $dumper = MySql::create()
                ->setDbName($databaseName)
                ->setUserName($userName)
                ->setPassword($password)
                ->useSingleTransaction(); // Prevent table locking

            if (!empty($dumpPath)) {
                $dumper->setDumpBinaryPath($dumpPath);
            }

            $dumper->dumpToFile($filePath);

            $this->info("Backup completed successfully! Saved to: {$filePath}");
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
        }
    }
}
