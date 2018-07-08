<?php

namespace Phanoteus\Phokis\Factories\Http;

use Interop\Http\Factory\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

interface PhokisResponseFactoryInterface extends ResponseFactoryInterface
{
    /**
     * Create a new response.
     *
     * @param integer $code HTTP status code
     *
     * @return ResponseInterface
     */
    public function createResponse($code = 200): ResponseInterface;

    /**
     * Create a new HTML response.
     *
     * @param string|Stream $body
     * @param integer $code HTTP status code
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function createHtmlResponse($body = null, $code = 200, $headers = []): ResponseInterface;

    /**
     * Create a new JSON response.
     *
     * @param mixed $data Data that can be JSON encoded.
     * @param integer $code HTTP status code
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function createJsonResponse($data = null, $code = 200, $headers = []): ResponseInterface;
}