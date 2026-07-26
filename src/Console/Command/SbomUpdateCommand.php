<?php
declare(strict_types=1);

namespace App\Console\Command;

use App\Console\Command;
use Exception;

class SbomUpdateCommand extends Command
{
    public string $signature = 'sbom:update --base-directory= --builds-directory=';
    public string $description = 'Publish PHP SBOM metadata';

    protected ?string $baseDirectory = null;

    public function handle(): int
    {
        try {
            $this->baseDirectory = $this->options['base-directory'] ?? null;
            if (!$this->baseDirectory) {
                throw new Exception('Base directory is required');
            }

            $buildsDirectory = $this->options['builds-directory'] ?? null;
            if (!$buildsDirectory) {
                throw new Exception('Build directory is required');
            }

            $sbomDirectory = $buildsDirectory . '/sbom';
            if (!is_dir($sbomDirectory)) {
                return Command::SUCCESS;
            }

            $tasks = glob($sbomDirectory . '/sbom-update-*.json');
            $pendingTasks = [];

            foreach ($tasks as $taskFile) {
                $lockFile = $taskFile . '.lock';
                if (!file_exists($lockFile)) {
                    touch($lockFile);
                    $pendingTasks[] = $taskFile;
                }
            }

            foreach ($pendingTasks as $taskFile) {
                $this->publishSbomMetadata($this->decodeTask($taskFile));
                unlink($taskFile);
                unlink($taskFile . '.lock');
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            echo $e->getMessage();
            return Command::FAILURE;
        }
    }

    private function decodeTask(string $taskFile): array
    {
        $data = json_decode(file_get_contents($taskFile), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['php_version']) || !is_string($data['php_version'])) {
            throw new Exception('Missing field: php_version');
        }
        if (!isset($data['sbom']) || !is_array($data['sbom'])) {
            throw new Exception('Missing field: sbom');
        }

        return $data;
    }

    private function publishSbomMetadata(array $data): void
    {
        $phpVersion = $data['php_version'];
        if (preg_match('/^(?:\d+\.\d+|master)$/', $phpVersion) !== 1) {
            throw new Exception('Invalid SBOM task');
        }

        $metadata = $data['sbom'];
        if (!is_string($metadata['license'] ?? null)
                || $metadata['license'] === ''
                || !is_array($metadata['components'] ?? null)
                || $metadata['components'] === []) {
            throw new Exception('Invalid SBOM metadata');
        }

        foreach ($metadata['components'] as $component) {
            if (!is_array($component)
                    || !is_string($component['name'] ?? null)
                    || !is_string($component['path'] ?? null)
                    || !is_string($component['license'] ?? null)
                    || !is_string($component['purl'] ?? null)
                    || $component['name'] === ''
                    || $component['path'] === ''
                    || $component['license'] === ''
                    || $component['purl'] === ''
                    || (isset($component['version'])
                        && (!is_string($component['version']) || $component['version'] === ''))) {
                throw new Exception('Invalid SBOM component');
            }
        }

        $destinationDirectory = $this->baseDirectory . '/php-sdk/sbom';
        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0755, true);
        }

        $destination = $destinationDirectory . '/php-' . $phpVersion . '.json';
        $temporary = tempnam($destinationDirectory, '.php-sbom-');
        if ($temporary === false
                || file_put_contents(
                    $temporary,
                    json_encode($metadata, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                ) === false
                || !chmod($temporary, 0644)
                || !rename($temporary, $destination)) {
            throw new Exception("Could not publish '$destination'");
        }
    }
}
