<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Migrations\Migrator;
use Throwable;

class MigrateContinue extends Command
{
    protected $signature = 'migrate:continue';

    protected $description = 'Ejecuta migraciones pendientes y continúa si alguna falla';

    public function handle(): int
    {
        $path = database_path('migrations');

        $files = collect(File::files($path))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->sortBy(fn($file) => $file->getFilename())
            ->values();

        $migrator = app(Migrator::class);

        $ran = $migrator->getRepository()->getRan();

        $pending = $files->filter(
            fn($file) => !in_array(
                $file->getFilenameWithoutExtension(),
                $ran
            )
        );

        if ($pending->isEmpty()) {
            $this->info('No hay migraciones pendientes.');
            return self::SUCCESS;
        }

        $successful = 0;
        $failed = 0;

        foreach ($pending as $file) {
            $migration = $file->getFilenameWithoutExtension();

            $this->newLine();
            $this->info("Ejecutando: {$migration}");

            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/' . $file->getFilename(),
                    '--force' => true,
                ]);

                $output = Artisan::output();

                if (str_contains($output, 'Migrated:')) {
                    $this->info('Correctamente ejecutada.');
                    $successful++;
                } else {
                    $this->warn(trim($output));
                    $failed++;
                }
            } catch (Throwable $e) {
                $this->error("Error: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Migraciones ejecutadas: {$successful}");
        $this->error("Migraciones con errores: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
