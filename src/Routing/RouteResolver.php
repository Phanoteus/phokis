<?php declare(strict_types = 1);
/**
 * Phokis Route Resolver
 *
 * Determines whether a given route (HTTP method, path, query, etc.) has a handler of some sort
 * registered with the system. Used by the Router middleware component.
 *
 * Based on nikic's FastRoute (as is everything) and Oscar Otero's FastRoute PSR-15 middleware adaptation.
 *
 * @see   https://github.com/nikic/FastRoute
 * @see   https://github.com/middlewares/fast-route
 */

namespace Phanoteus\Phokis\Routing;

use Psr\Http\Message\ServerRequestInterface;

class RouteResolver
{
    private $parserClass;
    private $generatorClass;
    private $dispatcherClass;
    private $dispatcher;

    /**
     * RouteResolver constructor.
     * @param string $parserClass
     * @param string $generatorClass
     */
    public function __construct(
        string $parserClass = 'FastRoute\\RouteParser\\Std',
        string $generatorClass = 'FastRoute\\DataGenerator\\GroupCountBased'
    )
    {
        $this->parserClass = trim($parserClass);
        $this->generatorClass = trim($generatorClass);
        $this->dispatcherClass = 'FastRoute\\Dispatcher' . strrchr($this->generatorClass, '\\');
    }

    /**
     * Initializes the RouteResolver with routes.
     *
     * @param  callable     $routeDefinitionCallback
     * @param  string       $cacheFile
     * @param  bool|boolean $cacheDisabled
     * @return void
     */
    public function initialize(
        callable $routeDefinitionCallback,
        string $cacheFile = '',
        bool $cacheDisabled = false
    )
    {
        $cacheFile = trim($cacheFile);
        $cacheDisabled = $cacheDisabled || empty($cacheFile);

        $options = [
            'routeParser' => $this->parserClass,
            'dataGenerator' => $this->generatorClass,
            'dispatcher' => $this->dispatcherClass,
            'cacheDisabled' => $cacheDisabled,
            'cacheFile' => $cacheFile
        ];

        $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, $options);
    }

    /**
     * Identifies whether a route resolution exists based on a URI path and a given HTTP method.
     *
     * @param  string $path
     * @param  string $method
     * @return array A FastRoute routing array.
     */
    public function resolveRouteFromPath(string $path, string $method = 'GET')
    {
        if (!isset($this->dispatcher))
            throw new \Exception(sprintf('The RouteResolver object must be initialized (by calling its `initialize()` function) before it can resolve routes.', __FUNCTION__));
        return $this->dispatcher->dispatch($method, $path);
    }

    /**
     * A convenience method to identify a route resolution by passing in a Request object.
     *
     * @param  ServerRequestInterface $request [description]
     * @return array A FastRoute routing array.
     */
    public function resolveRouteFromRequest(ServerRequestInterface $request)
    {
        return $this->resolveRouteFromPath($request->getUri()->getPath(), $request->getMethod());
    }
}
