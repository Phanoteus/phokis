<?php declare(strict_types = 1);
/**
 * A router compatible with the PSR-15 ServerMiddlewareInterface.
 *
 * Some code adapted from components at https://github.com/middlewares/psr15-middlewares.
 *
 * @see https://github.com/middlewares/psr15-middlewares PSR-15 Middleware Components
 */

namespace Phanoteus\Phokis\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher;
use Phanoteus\Phokis\Factories\Http\PhokisResponseFactoryInterface;
use Phanoteus\Phokis\Routing\RouteResolver;
use Phanoteus\Phokis\Routing\RouteActionInterface;

class Router implements MiddlewareInterface
{
    private $resolver;
    private $factory;
    private $action;

    public function __construct(RouteResolver $resolver, PhokisResponseFactoryInterface $factory, RouteActionInterface $defaultAction)
    {
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->action = $defaultAction;
    }

    /**
     * Processes a server request and returns a PSR-7 Response, optionally invoking a RequestHandler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $requestHandler
     * 
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        // The array returned from the FastRoute dispatch() method can be one of the following:
        // [0]  =   The routing path (pattern) is not found.
        // [1, $callable, ['varName1 => 'value', 'varName2' => 'value', ...]]    =   The route is found and the method is allowed.
        // [2, ['GET', 'OTHER_ALLOWED_METHODS']]    =   The specified method is not allowed.

        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        try {
            $routingArray = $this->resolver->resolveRouteFromPath($path, $method);
        }
        catch (\Exception $e) {
            $this->action->setParameters(['message' => $e->getMessage(), 'code' => 500]);
            $this->action->setAction(
                function(array $parameters) {
                    return $this->factory->createHtmlResponse($parameters['message'], $parameters['code']);
                }
            );
            // Add the Route Action to the Request object and pass the request to the Request Handler.
            $request = $this->setRoutingAction($request, $this->action);
            return $requestHandler->handle($request);
        }

        $routeStatus = $routingArray[0];

        switch ($routeStatus) {
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowed = implode(', ', $routingArray[1]);
                $this->action->setParameters(
                    [
                        'message' => sprintf('405 Error: The %s method is not allowed. These are the allowed methods: %s.', $method, $allowed),
                        'code' => 405,                        
                        'headers' => ['Allow' => $allowed]
                    ]
                );
                $this->action->setAction(
                    function(array $parameters) {
                        return $this->factory->createHtmlResponse($parameters['message'], $parameters['code'], $parameters['headers']);
                    }
                );                    
                break;

            case Dispatcher::FOUND:
                // Parse the action data found from resolving the route.
                $actionData = $routingArray[1];

                if (is_array($actionData)) {
                    if (isset($actionData[0])) {
                        $callable = $actionData[0];
                        if (isset($actionData[1])) {
                            $this->action->setParameters($actionData[1]);
                        }        
                    }
                }
                else {
                    $callable = $actionData;
                }                
                
                // If the identified callable is valid, set the RouteAction with it and set the parameters and then break.
                // If not, fall through to the next case. (If the identified callable isn't valid, it's equivalent to
                // the circumstance where the route isn't found by the RouteResolver.)

                // Merging Parameters:
                // 1. Action Parameters (which come from action data stored with route definitions
                //    and which should already be included in the RouteAction object),
                // 2. FastRoute Parameters (which are based on URI patterns, e.g., /home/{section}/{area}[/],
                //    and which are available from the action data if the route was found) and . . .
                // 3. Query Parameters (from the query string, e.g., /home?parents=divorced&children=spoiled).

                if (isset($callable)) {
                    $this->action->mergeParameters($routingArray[2]);
                    $this->action->mergeParameters($request->getQueryParams());
                    $this->action->setAction($callable);
                    break;
                }

            case DISPATCHER::NOT_FOUND:
                // Use the default action if it's available; otherwise, set an action and parameters as needed.
                if (is_null($this->action->getAction())) {
                    $this->action->setParameters(
                        [
                            'message' => sprintf('404 Error: The requested URI (%1$s) and the specified HTTP method (%2$s) are not mapped to a suitable action and no default action is available. No response can be generated.', $path, $method),
                            'code' => 404
                        ]
                    );
                    $this->action->setAction(
                        function(array $parameters) {
                            return $this->factory->createHtmlResponse($parameters['message'], $parameters['code']);
                        }
                    );                    
                }                    
        }

        // Add the Route Action to the Request object and pass the request to the Request Handler.
        $request = $this->setRoutingAction($request, $this->action);
        return $requestHandler->handle($request);
    }

    /**
     * Embeds the RouteAction object as an attribute of the Request object.
     *
     * @param ServerRequestInterface $request
     * @param RouteActionInterface $action
     * 
     * @return ServerRequestInterface
     */
    protected function setRoutingAction(ServerRequestInterface $request, RouteActionInterface $action): ServerRequestInterface
    {
        return $request->withAttribute(RouteActionInterface::class, $action);
    }
}
