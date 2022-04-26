<?php

namespace App\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\ClientRequestWatcher;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TelescopeGuzzleMiddleware extends ClientRequestWatcher
{
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, $options) use ($handler) {
            return $handler($request, $options)->then(function (ResponseInterface $response) use ($request) {
                if (Telescope::isRecording()) {
                    $httpRequest = new Request($request);
                    Telescope::recordClientRequest(IncomingEntry::make([
                        'method' => $httpRequest->method(),
                        'uri' => $httpRequest->url(),
                        'headers' => $this->headers($httpRequest->headers()),
                        'payload' => $this->payload($this->input($httpRequest)),
                        'response_status' => $response->getStatusCode(),
                        'response_headers' => $this->headers($response->getHeaders()),
                        'response' => $this->response(new Response($response)),
                    ]));
                }
                return $response;
            });
        };
    }
}
