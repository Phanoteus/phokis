<?php declare(strict_types = 1);
/**
 * An implementation of PhokisResponseFactoryInterface.
 */

namespace Phanoteus\Phokis\Factories\Http\Diactoros;

use Phanoteus\Phokis\Factories\Http\PhokisResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros;

class ResponseFactory implements PhokisResponseFactoryInterface
{
    /**
     * Fixes the Content-Length header.
     *
     * Adapted/stolen from Oscar Otero's PSR-15 middleware utilities.
     *
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public function fixContentLength(ResponseInterface $response): ResponseInterface
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
     * Creates a PRS-7 Response object.
     *
     * @param integer $code Status code.
     * 
     * @return ResponseInterface
     */
    public function createResponse($code = 200): ResponseInterface
    {
        return (new Response())->withStatus($code);
    }    

    /**
     * Creates a PRS-7 HTML Response object.
     *
     * @param string|Stream $body Body content.
     * @param integer $code Status code.
     * @param array $headers HTML headers
     * 
     * @return ResponseInterface
     */
    public function createHtmlResponse($body = null, $code = 200, $headers = []): ResponseInterface
    {
        if (is_null($body)) {
            return new Diactoros\Response\HtmlResponse('', $code, $headers);
        }
        return new Diactoros\Response\HtmlResponse($body, $code, $headers);
    }

    /**
     * Creates a PRS-7 JSON Response object.
     * 
     * @param mixed $data Data suitable for JSON encoding.
     * @param integer $code Status code.
     * @param array $headers HTML headers
     * 
     * @return ResponseInterface* 
     */
    public function createJsonResponse($data = null, $code = 200, $headers = []): ResponseInterface
    {
        if (is_null($data)) {
            $data = '';
        }
        return new Diactoros\Response\JsonResponse($data, $code, $headers);
    }
}