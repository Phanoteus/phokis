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

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phanoteus\Phokis\Factories\Http\Diactoros\Factory;
use Psr\Log\LoggerInterface;
use Auryn\Injector;

class RequestHandler implements ServerMiddlewareInterface
{

    private $injector;
    private $factory;

    private $handlerAttribute = 'request-handler';
    private $parametersAttribute = 'request-parameters';

    /**
     * RequestHandler constructor.
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
     * This function, in compliance with the ServerMiddlewareInterface, accepts a delegate
     * but doesn't call it.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $parameters = $request->getAttribute($this->parametersAttribute);
        $handler = $request->getAttribute($this->handlerAttribute);

        return $this->executeHandler($handler, $parameters);
    }


    private function executeHandler($handler, array $parameters = [])
    {
        $handler = $this->verifyHandler($handler);
        if ($handler === false) {
            throw new \RuntimeException("The specified handler is not a valid callable or closure.");
        }

        ob_start();
        $level = ob_get_level();

        try {

            // Using call_user_func in order to retain associative array for parameters.
            // The call_user_func_array function passes an indexed array.
            $return = call_user_func($handler, $parameters);

            if ($return instanceof ResponseInterface) {
                $response = $return;
                $return = '';
            }
            elseif (is_null($return) || is_scalar($return) || (is_object($return) && method_exists($return, '__toString'))) {
                $response = $this->factory->createResponse();
            }
            else {
                throw new \UnexpectedValueException(
                    'The value returned must be scalar (e.g., a string) or an object with an implementation of the __toString method.'
                );
            }

            while (ob_get_level() >= $level) {
                $return = ob_get_clean() . $return;
            }

            // Get body as a stream.
            $body = $response->getBody();

            if ($return !== '' && $body->isWritable()) {
                $body->write($return);
            }

            return $response;
        } catch (\Exception $exception) {
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }

            throw $exception;
        }
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
