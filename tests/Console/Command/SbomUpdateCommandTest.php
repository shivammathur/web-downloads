<?php
declare(strict_types=1);

namespace Console\Command;

use App\Console\Command\SbomUpdateCommand;
use App\Helpers\Helpers;
use PHPUnit\Framework\TestCase;

class SbomUpdateCommandTest extends TestCase
{
    private string $baseDirectory;
    private string $buildsDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDirectory = sys_get_temp_dir() . '/sbom_update_base_' . uniqid();
        $this->buildsDirectory = sys_get_temp_dir() . '/sbom_update_builds_' . uniqid();

        mkdir($this->baseDirectory, 0755, true);
        mkdir($this->buildsDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        Helpers::rmdirr($this->baseDirectory);
        Helpers::rmdirr($this->buildsDirectory);
        parent::tearDown();
    }

    public function testSucceedsWhenNoTasksAreQueued(): void
    {
        $this->assertSame(0, $this->createCommand()->handle());
    }

    public function testPublishesSbomMetadata(): void
    {
        $taskFile = $this->createTask([
            'php_version' => '8.2',
            'sbom' => $this->metadata(),
        ]);

        $this->assertSame(0, $this->createCommand()->handle());

        $destination = $this->baseDirectory . '/php-sdk/sbom/php-8.2.json';
        $this->assertFileExists($destination);
        $this->assertSame(0644, fileperms($destination) & 0777);
        $metadata = json_decode(file_get_contents($destination), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('PHP-3.01', $metadata['license']);
        $this->assertSame('pcre2lib', $metadata['components'][0]['name']);
        $this->assertFileDoesNotExist($taskFile);
        $this->assertFileDoesNotExist($taskFile . '.lock');
    }

    private function createCommand(): SbomUpdateCommand
    {
        $command = new SbomUpdateCommand();
        $command->options = [
            'base-directory' => $this->baseDirectory,
            'builds-directory' => $this->buildsDirectory,
        ];

        return $command;
    }

    private function createTask(array $data): string
    {
        $sbomDirectory = $this->buildsDirectory . '/sbom';
        if (!is_dir($sbomDirectory)) {
            mkdir($sbomDirectory, 0755, true);
        }

        $taskFile = $sbomDirectory . '/sbom-update-' . uniqid() . '.json';
        file_put_contents($taskFile, json_encode($data));

        return $taskFile;
    }

    private function metadata(): array
    {
        return [
            'license' => 'PHP-3.01',
            'components' => [
                [
                    'name' => 'pcre2lib',
                    'version' => '10.40',
                    'path' => 'ext/pcre/pcre2lib',
                    'license' => 'BSD-3-Clause WITH PCRE2-exception',
                    'purl' => 'pkg:generic/pcre2@10.40',
                ],
            ],
        ];
    }
}
