<?php declare(strict_types = 1);
/**
 * Just a simple middleware component to log URI information.
 * Really only for the purpose of sanity-checking the middleware stack.
 */

namespace Phanoteus\Phokis\Middleware;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class UriLogger implements ServerMiddlewareInterface
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
     * Processes a server request and returns a PSR-7 Response, optionally invoking a delegate.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $uri = $request->getUri()->__toString();

        $server = $request->getServerParams();

        $address = $server['REMOTE_ADDR'];
        $address  = ($address === '::1' || $address === '127.0.0.1') ? 'localhost' : $address;
        $proxy = $server['HTTP_X_FORWARDED_FOR'];
        $proxy = (empty($proxy)) ? '' : " (or '" . $proxy . "')'";

        $info = "Client at address '{$address}'{$proxy} requested the following URI: {$uri}.";
        $this->logger->addInfo($info, []);

        return $delegate->process($request);
    }
}
