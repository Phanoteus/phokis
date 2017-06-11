<?php declare(strict_types = 1);
/**
 * This is a straightforward adaptation/copy of Zend's SAPI emitter code. Just using something simple
 * to send the response to the browser for now and this works well enough.
 *
 * @see  https://github.com/zendframework/zend-diactoros/blob/master/src/Response/SapiEmitterTrait.php
 */

namespace Phanoteus\Phokis;

use Psr\Http\Message\ResponseInterface;

abstract class Server
{
    public static function emit(ResponseInterface $response)
    {
        // Status
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf('HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ));

        // Headers
        foreach ($response->getHeaders() as $header => $values) {
            $name  = self::filterHeader($header);
            $first = true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first);
                $first = false;
            }
        }

        // Flush buffer.
        $maxBufferLevel = ob_get_level();
        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
        }

        // Send response body to client.
        echo $response->getBody();
    }

    private static function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
