<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseController;
use App\Validator;

class SbomUpdateController extends BaseController
{
    protected function validate(array $data): bool
    {
        $validator = new Validator([
            'php_version' => 'required|string|regex:/^(?:\d+\.\d+|master)$/',
            'sbom' => 'required|array',
        ]);

        $validator->validate($data);

        if (!$validator->isValid) {
            http_response_code(400);
            echo 'Invalid request: ' . $validator;
            return false;
        }

        return true;
    }

    protected function execute(array $data): void
    {
        $directory = rtrim((string) getenv('BUILDS_DIRECTORY'), '/');
        if ($directory === '') {
            http_response_code(500);
            echo 'Invalid server configuration: BUILDS_DIRECTORY is not set.';
            return;
        }

        $sbomDirectory = $directory . '/sbom';
        if (!is_dir($sbomDirectory)) {
            mkdir($sbomDirectory, 0755, true);
        }

        $hash = hash('sha256', $data['php_version'] . json_encode($data['sbom'])) . uniqid('', true);
        $file = $sbomDirectory . '/sbom-update-' . $hash . '.json';

        file_put_contents($file, json_encode($data));
    }
}
