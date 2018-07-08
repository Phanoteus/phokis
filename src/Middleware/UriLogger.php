<?php declare(strict_types = 1);
/**
 * Just a simple middleware component to log URI information.
 * Really only for the purpose of sanity-checking the middleware stack.
 */

namespace Phanoteus\Phokis\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class UriLogger implements MiddlewareInterface
{
    private $logger;

    /**
     * UriLogger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $uri = $request->getUri()->__toString();

        $server = $request->getServerParams();

        $address = $server['REMOTE_ADDR'];
        $address  = ($address === '::1' || $address === '127.0.0.1') ? 'localhost' : $address;

        $proxy = '';
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $proxy = $server['HTTP_X_FORWARDED_FOR'];
        }
        $proxy = (empty($proxy)) ? $proxy : " (or '" . $proxy . "')'";

        $info = "Client at address '{$address}'{$proxy} requested the following URI: {$uri}.";
        $this->logger->addInfo($info, []);

        return $requestHandler->handle($request);
    }
}
