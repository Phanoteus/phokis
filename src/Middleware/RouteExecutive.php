<?php declare(strict_types = 1);
/**
 * A request handler compatible with the PSR-15 ServerMiddlewareInterface.
 * 
 * The RouteExecutive executes a RouteAction object extracted from the Request object.
 *
 * Some code adapted from components at https://github.com/middlewares/psr15-middlewares.
 *
 * @see https://github.com/middlewares/psr15-middlewares PSR-15 Middleware Components
 * @see https://github.com/middlewares/request-handler
 */

namespace Phanoteus\Phokis\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phanoteus\Phokis\Factories\Http\PhokisResponseFactoryInterface;
use Phanoteus\Phokis\Routing\RouteActionInterface;
use Psr\Log\LoggerInterface;
use Auryn\Injector;
use ReflectionMethod;

class RouteExecutive implements MiddlewareInterface
{

    private $injector;
    private $factory;

    /**
     * RouteExecutive constructor.
     *
     * This object has an Auryn injector as a dependency. Doing this is more or less like using the maligned
     * "service locator" pattern. But this, to me, seems better than using a so-called "resolver" to instantiate
     * classes from class names. That is also more or less the same thing as the service locator pattern.
     *
     * What this amounts to here is that object instantation, as much as it can be, is centralized and
     * handled by one component.
     *
     * @param Injector $injector An Auryn injector, which is being used here more or less as a factory for objects.
     * @param PhokisResponseFactoryInterface  $factory  A Phokis response factory (which is compliant with the PSR-7 factory).
     */
    public function __construct(Injector $injector, PhokisResponseFactoryInterface $factory)
    {
        $this->injector = $injector;
        $this->factory = $factory;
    }

    /**
     * Processes a server request and returns a PSR-7 Response.
     * 
     * This function tries to extract a RouteActionInterface object from the attributes
     * of the Request object. If a RouteAction is found, it attempts to execute that action.
     * 
     * If there is no RouteAction or if executing that action returns an invalid response,
     * then the Request object is passed on to the specified RequestHandler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $requestHandler
     * 
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $routeAction = $request->getAttribute(RouteActionInterface::class);

        if (! is_null($routeAction)) {
            $action = $this->verifyAction($routeAction->getAction());
            if ($action === false) {
                throw new \RuntimeException("The specified action is not a valid callable or closure.");
            }

            $response = call_user_func($action, $routeAction->getParameters());

            if ($response instanceof ResponseInterface) {
                return $response;
            }

            // Check if $response is an array or an object:
            if (is_array($response) || is_object($response)) {
                return $this->factory->createJsonResponse($response);
            }

            if (is_null($response) || is_scalar($response)) {
                $createdResponse = $this->factory->createHtmlResponse();

                try {
                    ob_start();
                    $level = ob_get_level();

                    while (ob_get_level() >= $level) {
                        $response = ob_get_clean() . $response;
                    }
                    
                    $body = $createdResponse->getBody();

                    if (!empty($response) && $body->isWritable()) {
                        $body->write($response);
                    }
                    
                    return $createdResponse;
                }
                catch (\Exception $exception) {
                    while (ob_get_level() >= $level) {
                        ob_end_clean();
                    }
        
                    throw $exception;                
                }
            }
        }

        // If:
        // There is no RouteAction embedded in the Request object, or
        // the $response after executing the RouteAction is not a Response Object or
        // the $response is not an array or an object from which to create a json response or
        // the $response is not null or
        // the $response is not a scalar value
        // then pass operation to the supplied request handler.

        return $requestHandler->handle($request);
    }

    /**
     * Verifies that a given action is valid and instantiates objects as necessary.
     *
     * @param callable $action
     * 
     * @return callable|bool $action
     */
    private function verifyAction($action)
    {
        if (is_string($action)) {
            if (strpos($action, '::') !== false) {
                list($class, $method) = explode('::', $action, 2);
                $action = [$class, $method];
            }
            else {
                if (method_exists($action, '__invoke')) {
                    $action = $this->injector->make($action);
                }
            }
        }

        if (is_callable($action)) {

            if (is_string($action)) {
                if (!function_exists($action)) {
                    // It's not a named function.
                    $action = false;
                }
            }
            elseif (is_array($action)) {
                // The handler should be a standard callable array (e.g., ['HomeController', 'browse']).
                // If the method isn't static, instantiate the class.
                list($class, $method) = $action;
                $refMethod = new ReflectionMethod($class, $method);
                if (!$refMethod->isStatic()) {
                    $obj = $this->injector->make($class);
                    $action = [$obj, $method];
                }
            }

            // If the $action is neither a string nor an array, and it's callable, then
            // it's a closure or an invokeable object.

        }
        else {
            $action = false;
        }

        return $action;
    }

}
