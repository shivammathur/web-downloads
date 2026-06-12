<?php
declare(strict_types=1);

use App\Http\Controllers\IndexController;
use PHPUnit\Framework\TestCase;
use App\Router;

class RouterTest extends TestCase {
    /**
     * @throws JsonException
     */
    public function testHandleIndexRequest() {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $router = new Router();
        $router->registerRoute('/', 'GET', IndexController::class
        );
        ob_start();
        $router->handleRequest();
        $output = ob_get_clean();
        $this->assertEquals('Welcome!', $output, 'Should respond with Welcome! for index route.');
    }

    /**
     * @throws JsonException
     */
    public function testHandleRequestUnauthorized() {
        $_SERVER['REQUEST_URI'] = '/protected';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $router = new Router();
        $router->registerRoute('/protected', 'GET', 'TestHandler', true);
        ob_start();
        $router->handleRequest();
        $output = ob_get_clean();
        $this->assertEquals('Unauthorized', $output, 'Should respond with Unauthorized for protected routes.');
    }

    /**
     * @throws JsonException
     */
    public function testHandleRequestMethodNotAllowed() {
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $router = new Router();
        $router->registerRoute('/test', 'GET', 'TestHandler');
        ob_start();
        $router->handleRequest();
        $output = ob_get_clean();
        $this->assertStringContainsString('Method Not Allowed', $output, 'Should respond with Method Not Allowed.');
    }

    /**
     * @throws JsonException
     */
    public function testHandleRequestNotFound() {
        $_SERVER['REQUEST_URI'] = '/nonexistent';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $router = new Router();
        ob_start();
        $router->handleRequest();
        $output = ob_get_clean();
        $this->assertEquals('Not Found', $output, 'Should respond with Not Found for unregistered routes.');
    }
}
