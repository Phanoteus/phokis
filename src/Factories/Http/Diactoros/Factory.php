<?php declare(strict_types = 1);
/**
 * A simple implementation of Request and Response factory interfaces to be used by certain middleware components
 * to create PSR-7 objects.
 *
 * Essentially an adapter for the Zend Diactoros object-creation methods.
 *
 */

namespace Phanoteus\Phokis\Factories\Http\Diactoros;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;

use Zend\Diactoros;

class Factory implements ServerRequestFactoryInterface, ResponseFactoryInterface, StreamFactoryInterface
{

    /**
     * Fixes the Content-Length header.
     *
     * Adapted/stolen from Oscar Otero's PSR-15 middleware utilities.
     *
     * @param  ResponseInterface $response
     * @return ResponseInterface
     */
    public function fixContentLength(ResponseInterface $response)
    {
        if (!$response->hasHeader('Content-Length')) {
            return $response;
        }

        if ($response->getBody()->getSize() !== null) {
            return $response->withHeader('Content-Length', (string) $response->getBody()->getSize());
        }

        return $response->withoutHeader('Content-Length');
    }

    /**
     * Creates a PSR-7 ServerRequest object.
     *
     * @param  string $method
     * @param  UriInterface $uri
     * @return ServerRequestInterface
     */
    public function createServerRequest($method, $uri): ServerRequestInterface
    {
        // TODO: May need to check that $uri is an instance of UriInterface,
        // but it's not stipulated in the type-hinting from the interface.
        // TODO: May provision $body = null and $headers = [] parameters.
        return new Diactoros\ServerRequest($uri, $method, null, []);
    }

    /**
     * Creates a PSR-7 ServerRequest instance from globals.
     *
     * @param  array  $server Generally should be $_SERVER superglobal.
     * @return ServerRequestInterface
     */
    public function createServerRequestFromArray(array $server = []): ServerRequestInterface
    {
        if (empty($server)) {
            return Diactoros\ServerRequestFactory::fromGlobals();
        }
        return Diactoros\ServerRequestFactory::fromGlobals($server);
    }

    /**
     * Creates a PRS-7 Response object.
     *
     * @param  integer $code    Status code.
     * @param  string|Stream  $body    Body content.
     * @param  array   $headers HTML headers
     * @return ResponseInterface
     */
    public function createResponse($code = 200, $body = null, $headers = []): ResponseInterface
    {
        // Could just return a new Diactoros\Response but taking advantage of the HtmlResponse in Diactoros.
        if (is_null($body)) {
            return new Diactoros\Response\HtmlResponse('', $code, $headers);
        }
        return new Diactoros\Response\HtmlResponse($body, $code, $headers);
    }

    /**
     * Creates a PSR-7 Stream object.
     *
     * @param  string $content
     * @return StreamInterface
     */
    public function createStream($content = ''): StreamInterface
    {
        $stream = $this->createStreamFromFile('php://temp', 'r+');
        $stream->write($content);
        return $stream;
    }

    /**
     * Creates a PSR-7 Stream object from a file.
     *
     * @param  string $filename
     * @param  string $mode
     * @return StreamInterface
     */
    public function createStreamFromFile($filename, $mode = 'r'): StreamInterface
    {
        return $this->createStreamFromResource(fopen($filename, $mode));
    }

    /**
     * Creates a PSR-7 Stream object from a resource.
     *
     * @param  resource $resource
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Diactoros\Stream($resource);
    }
}
