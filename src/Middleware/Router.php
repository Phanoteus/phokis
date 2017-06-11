<?php declare(strict_types = 1);
/**
 * A router compatible with the PSR-15 ServerMiddlewareInterface.
 *
 * Some code adapted from components at https://github.com/middlewares/psr15-middlewares.
 *
 * @see https://github.com/middlewares/psr15-middlewares PSR-15 Middleware Components
 */

namespace Phanoteus\Phokis\Middleware;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher;
use Phanoteus\Phokis\Routing\RouteResolver;
use Phanoteus\Phokis\Factories\Http\Diactoros\Factory;

class Router implements ServerMiddlewareInterface
{
    private $resolver;
    private $factory;
    private $defaultAction;

    private $handlerAttribute = 'request-handler';
    private $parametersAttribute = 'request-parameters';

    public function __construct(RouteResolver $resolver, Factory $factory, array $defaultAction = [])
    {
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->defaultAction = $defaultAction;
    }

    public function handlerAttribute($attribute) {
        $this->handlerAttribute = $attribute;

        return $this;
    }

    public function parametersAttribute($attribute) {
        $this->parametersAttribute = $attribute;

        return $this;
    }

    /**
     * Processes a server request and returns a PSR-7 Response, optionally invoking a delegate.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // The array returned from the FastRoute dispatch() method can be one of the following:
        // [0]  =   The routing path (pattern) is not found.
        // [1, $handler, ['varName1 => 'value', 'varName2' => 'value', ...]]    =   The route is found and the method is allowed.
        // [2, ['GET', 'OTHER_ALLOWED_METHODS']]    =   The specified method is not allowed.

        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        try {
            $routingArray = $this->resolver->resolveRouteFromPath($path, $method);
        }
        catch (\Exception $e) {
            $body = $e->getMessage();
            return $this->factory->createResponse(500, $body);
        }

        $routeStatus = $routingArray[0];

        // Creating simple body content when things go wrong just for the sake of development. In production, log errors
        // and return an error page.

        if ($routeStatus === Dispatcher::NOT_FOUND) {
            $body = sprintf('The requested URI (%1$s) and the specified HTTP method (%2$s) are not mapped to a suitable application handler. No response can be generated.', $path, $method);
            return $this->factory->createResponse(404, $body);
        }

        if ($routeStatus === Dispatcher::METHOD_NOT_ALLOWED) {
            $allowed = implode(', ', $routingArray[1]);
            $body = sprintf('The %s method is not allowed. These are the allowed methods: %s.', $method, $allowed);
            return $this->factory->createResponse(405, $body)->withHeader('Allow', $allowed);
        }

        $action = $routingArray[1];
        $parameters = [];
        if (is_array($action)) {
            if (isset($action['handler'])) {
                $handler = $action['handler'];
            }
            elseif (isset($defaultAction['handler'])) {
                $handler = $defaultAction['handler'];
            }

            if (isset($action['parameters'])) {
                $parameters = $action['parameters'];
            }
            elseif (isset($defaultAction['parameters'])) {
                $defaultAction['parameters'];
            }
        }
        else {
            $handler = $action;
        }

        if (!isset($handler)) {
            $body = sprintf('The requested URI (%1$s) and the specified HTTP method (%2$s) are not mapped to a suitable application handler. No response can be generated.', $path, $method);
            return $this->factory->createResponse(404, $body);
        }

        $request = $this->setHandler($request, $handler);

        // Merge
        // 1. Action Parameters (which come from action data stored with route definitions),
        // 2. FastRoute Parameters (which are based on URI patterns, e.g., /home/{section}/{area}[/]), and
        // 3. Query Parameters (from the query string, e.g., /home?parents=divorced&children=spoiled).
        $parameters = array_merge($parameters, $routingArray[2]);
        $parameters = array_merge($parameters, $request->getQueryParams());

        $request = $this->setParameters($request, $parameters);

        return $delegate->process($request);
    }

    /**
     * Sets the value of the request handler attribute of the Request object.
     *
     * @param ServerRequestInterface $request
     * @param callable|string|array  $handler
     * @return ServerRequestInterface
     */
    protected function setHandler(ServerRequestInterface $request, $handler)
    {
        return $request->withAttribute($this->handlerAttribute, $handler);
    }

    /**
     * Sets the value of the parameter attribute of the Request object.
     *
     * @param ServerRequestInterface $request
     * @param array  $parameters
     * @return ServerRequestInterface
     */
    protected function setParameters(ServerRequestInterface $request, $parameters = [])
    {
        return $request->withAttribute($this->parametersAttribute, $parameters);
    }
}
