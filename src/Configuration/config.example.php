<?php declare(strict_types = 1);
/**
 * A sample configuration file for initializing the Auryn injector using the InjectorConfig object.
 *
 * The idea for configuring Auryn in this way was stolen from
 * Dan Ackroyd's Tier system (https://github.com/Danack/TierJigSkeleton).
 */

use Phanoteus\Phokis\Configuration\InjectorConfig;

// For aliasing, the key is the interface (the name of a thing) and the value is the alias
// or nickname for that thing:
//
// 'Nature\Kingdom\Animal' => 'MyAnimals\Pangolin'
//
// And then for $injector configuration functions (e.g., `define`, `delegate`, `share`, `prepare`),
// refer to the specific implementation:
//
// $injector->define('MyAnimals\Pangolin', [':size' => 'large']);
//
// But when you make something with the $injector, refer to the interface:
//
// $animal = $injector->make('Nature\Kingdom\Animal');
//
// You can also execute `make` using the alias (i.e., the specific implementation),
// but then if you want to swap in another implementation of a given interface later,
// you'd have to make sure to change all of your `make` calls.
//
// And you type-hint against the interface, not the specific implementation:
//
// class Pet {
//     private $animal;
//     public __construct(Nature\Kingdom\Animal $animal) {
//         $this->animal = $animal;
//     }
//     public function type() {
//         return get_class($this->animal);
//     }
// }
//
// $pet = $injector->make('Pet');
// var_dump($pet->type()); // 'MyAnimals\Pangolin'

$aliases = [
    // Examples:
    // 'Psr\Log\LoggerInterface' => 'Monolog\Logger',
    // 'Psr\Http\Message\ServerRequestInterface' => 'Zend\Diactoros\ServerRequest'
];

$definitions = [
    // Examples:
    // 'Phanoteus\Phokis\Middleware\Router' =>
    //     [
    //         ':defaultAction' =>
    //             [
    //                 'handler' => 'Controllers\HomeController::browse',
    //                 'parameters' => []
    //             ]
    //     ]
];

$delegates = [
    // Examples:
    // 'Monolog\Logger' => function() {
    //     $log = new Monolog\Logger('debug-logger');
    //     $format = "[%datetime%] %channel% %level_name%  >  %message% %context%\n";
    //     $formatter = new Monolog\Formatter\LineFormatter($format);
    //     $stream = new Monolog\Handler\StreamHandler(YOUR_SITE_ROOT . 'debug.txt', Monolog\Logger::DEBUG);
    //     $stream->setFormatter($formatter);
    //     $log->pushHandler($stream);
    //     return $log;
    // },
    // 'Zend\Diactoros\ServerRequest' => [Zend\Diactoros\ServerRequestFactory::class, 'fromGlobals']
];

$prepares = [
    // Example:
    // 'Phanoteus\Phokis\Routing\RouteResolver' => function(Phanoteus\Phokis\Routing\RouteResolver $resolver) {
    //     $resolver->initialize(function(FastRoute\RouteCollector $rc) {
    //         $routes =
    //         [
    //             [
    //                 'method' => 'GET',
    //                 'pattern' => '/home[/]',
    //                 'action' => [
    //                     'handler' => 'Controllers\HomeController::browse',
    //                     // OR:
    //                     // 'route_handler' => [Controllers\HomeController::class, 'browse']
    //                     // OR:
    //                     // 'route_handler' => function() { return '<p>I\'m a closure!</p>'; }
    //                     // OR:
    //                     // 'route_handler' => 'Controllers\InvokeClass'
    //                     'parameters' => ['route_id' => 1]
    //                 ]
    //             ],
    //             [
    //                 'method' => 'GET',
    //                 'pattern' => '/home/{section}[/]',
    //                 'action' => [
    //                     'handler' => 'Controllers\HomeController::browse',
    //                     'parameters' => ['route_id' => 2]
    //                 ]
    //             ],
    //             // etc.
    //         ];

    //         foreach ($routes as $route) {
    //             $rc->addRoute($route['method'], $route['pattern'], $route['action']);
    //         }
    //     },
    //     YOUR_SITE_ROOT . 'routes.cache',
    //     false // Whether caching is disabled (default is false).
    //     );
    // }
];

$shares = [
    // Examples:
    // 'Monolog\Logger',
    // 'Phokis\Factories\Diactoros\Factory'
];

$configuration = new InjectorConfig($aliases, $definitions, $delegates, $prepares, $shares);

return $configuration;
