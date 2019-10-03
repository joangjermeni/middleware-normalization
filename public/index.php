<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use TestApp\UrlNormalizer;
use function DI\create;
use Relay\Relay;
use Zend\Diactoros\ServerRequestFactory;
use FastRoute\RouteCollector;
use Middlewares\FastRoute;
use Middlewares\RequestHandler;
use function FastRoute\simpleDispatcher;
use function DI\get;
use Zend\Diactoros\Response;
use Narrowspark\HttpEmitter\SapiEmitter;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Get URL link
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $link = "https";
} else {
    $link = "http";
}
$link .= "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

// Defining the dependency injection container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    UrlNormalizer::class => create(UrlNormalizer::class)
        ->constructor(get('url'), get('Response')),
    'url' => $link,
    'Response' => function() {
        return new Response();
    },
]);

$container = $containerBuilder->build();
// Add middleware to handle the request
$route = simpleDispatcher(function (RouteCollector $route) {
    $route->get($_SERVER['REQUEST_URI'], UrlNormalizer::class);
});

$middleware[] = new FastRoute($route);
$middleware[] = new RequestHandler($container);
$requestHandler = new Relay($middleware);
$response = $requestHandler->handle(ServerRequestFactory::fromGlobals());

// Receive Response from the dispatcher and pass it to the emitter
$emitter = new SapiEmitter();
return $emitter->emit($response);
