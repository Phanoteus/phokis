<?php declare(strict_types = 1);
/**
 * A request handler compatible with the PSR-15 ServerMiddlewareInterface.
 *
 * Some code adapted from components at https://github.com/middlewares/psr15-middlewares.
 *
 * @see https://github.com/middlewares/psr15-middlewares PSR-15 Middleware Components
 * @see https://github.com/middlewares/request-handler
 */

namespace Phanoteus\Phokis\Middleware;

use Psr\Http\Server\MiddlewareInterface;
// use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phanoteus\Phokis\Factories\Http\Diactoros\Factory;
use Psr\Log\LoggerInterface;
use Auryn\Injector;
use ReflectionMethod;

class ResponseGenerator implements MiddlewareInterface
{

    private $injector;
    private $factory;

    private $handlerAttribute = 'request-handler';
    private $parametersAttribute = 'request-parameters';

    /**
     * ResponseGenerator constructor.
     *
     * This object has an Auryn injector as a dependency. Doing this is more or less like using the maligned
     * "service locator" pattern. But this, to me, seems better than using a so-called "resolver" to instantiate
     * classes from class names. That is also more or less the same thing as the service locator pattern.
     *
     * What this amounts to here is that object instantation, as much as it can be, is centralized and
     * handled by one component.
     *
     * @param Injector $injector An Auryn injector, which is being used here more or less as a factory for objects.
     * @param Factory  $factory  An HTTP PSR-7 factory.
     */
    public function __construct(Injector $injector, Factory $factory)
    {
        $this->injector = $injector;
        $this->factory = $factory;
    }

    /**
     * Sets the attribute name for storing the handler reference in the Request object.
     *
     * @param string $attribute
     * @return self
     */
    public function handlerAttribute($attribute)
    {
        $this->handlerAttribute = $attribute;

        return $this;
    }

    /**
     * Sets the attribute name for storing the parameters array associated with the handler in the Request object.
     *
     * @param string $attribute
     * @return self
     */
    public function parametersAttribute($attribute)
    {
        $this->parametersAttribute = $attribute;

        return $this;
    }

    /**
     * Processes a server request and returns a PSR-7 Response.
     *
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface  $requestHandler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $parameters = $request->getAttribute($this->parametersAttribute);
        $handler = $request->getAttribute($this->handlerAttribute);

        $handler = $this->verifyHandler($handler);
        if ($handler === false) {
            throw new \RuntimeException("The specified handler is not a valid callable or closure.");
        }

        $response = call_user_func($handler, $parameters);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (is_null($response) || is_scalar($response) || (is_object($response) && method_exists($response, '__toString'))) {
            $createdResponse = $this->factory->createResponse();

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

        // If the $response isn't an HTML Response or null or a scalar value or an object that can be converted
        // into a string, pass operation to the supplied request handler.

        return $requestHandler->handle($request);
    }

    /**
     * Verifies that a given handler is valid and instantiates objects as necessary.
     *
     * @param  mixed $handler
     * @return mixed $handler
     */
    private function verifyHandler($handler)
    {
        if (is_string($handler)) {
            if (strpos($handler, '::') !== false) {
                list($class, $method) = explode('::', $handler, 2);
                $handler = [$class, $method];
            }
            else {
                if (method_exists($handler, '__invoke')) {
                    $handler = $this->injector->make($handler);
                }
            }
        }

        if (is_callable($handler)) {

            if (is_string($handler)) {
                if (!function_exists($handler)) {
                    // It's not a named function.
                    $handler = false;
                }
            }
            elseif (is_array($handler)) {
                // The handler should be a standard callable array (e.g., ['HomeController', 'browse']).
                // If the method isn't static, instantiate the class.
                list($class, $method) = $handler;
                $refMethod = new ReflectionMethod($class, $method);
                if (!$refMethod->isStatic()) {
                    $obj = $this->injector->make($class);
                    $handler = [$obj, $method];
                }
            }

            // If the $handler is neither a string nor an array, and it's callable, then
            // it's a closure or an invokeable object.

        }
        else {
            $handler = false;
        }

        return $handler;
    }

}
