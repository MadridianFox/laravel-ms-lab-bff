<?php

namespace App\Providers;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\GuzzleMiddleware;
use Ackintosh\Ganesha\Storage\Adapter\Apcu as ApcuAdapter;
use Ensi\GuzzleMultibyte\BodySummarizer;
use Ensi\LaravelInitialEventPropagation\PropagateInitialEventLaravelGuzzleMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Lab\CommentsClient\CommentsClientProvider;
use Lab\CommentsClient\Configuration as CommentsConfiguration;
use Lab\PostsClient\Configuration as PostsConfiguration;
use Lab\PostsClient\PostsClientProvider;
use LogicException;

class OpenApiClientsServiceProvider extends ServiceProvider
{
    private const DEFAULT_TIMEOUT = 30;

    public function register(): void
    {
        $handler = $this->configureHandler();

        $this->registerService(
            handler: $handler,
            serviceName: 'posts',
            configurationClassName: PostsConfiguration::class,
            apisClassNames: PostsClientProvider::$apis
        );

        $this->registerService(
            handler: $handler,
            serviceName: 'comments',
            configurationClassName: CommentsConfiguration::class,
            apisClassNames: CommentsClientProvider::$apis
        );
    }

    private function configureHandler(): HandlerStack
    {
        $stack = new HandlerStack(Utils::chooseHandler());

        $stack->push(Middleware::httpErrors(new BodySummarizer()), 'http_errors');
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::prepareBody(), 'prepare_body');
        if (!config('ganesha.disable_middleware', false)) {
            $stack->push($this->configureGaneshaMiddleware());
        }

        $stack->push(new PropagateInitialEventLaravelGuzzleMiddleware());

        return $stack;
    }

    private function configureGaneshaMiddleware(): GuzzleMiddleware
    {
        $config = config('ganesha');

        $ganesha = Builder::withRateStrategy()
            ->timeWindow($config['time_window'])
            ->failureRateThreshold($config['failure_rate_threshold'])
            ->minimumRequests($config['minimum_requests'])
            ->intervalToHalfOpen($config['interval_to_half_open'])
            ->adapter(new ApcuAdapter())
            ->build();


        $ganesha->subscribe(function ($event, $service, $message) {
            switch ($event) {
                case Ganesha::EVENT_TRIPPED:
                    Log::warning(
                        "Ganesha has tripped! It seems that a failure has occurred in {$service}. {$message}."
                    );

                    break;
                case Ganesha::EVENT_CALMED_DOWN:
                    Log::info(
                        "The failure in {$service} seems to have calmed down :). {$message}."
                    );

                    break;
                case Ganesha::EVENT_STORAGE_ERROR:
                    Log::error($message);

                    break;
            }
        });

        return new GuzzleMiddleware($ganesha);
    }

    private function registerService(HandlerStack $handler, string $serviceName, string $configurationClassName, array $apisClassNames): void
    {
        $config = config("openapi-clients.$serviceName");
        if (!$config) {
            throw new LogicException("Config openapi-clients.$serviceName is not found");
        }

        $baseUri = $config['base_uri'];
        $this->app->bind($this->trimFQCN($configurationClassName), fn () => (new $configurationClassName())->setHost($baseUri));
        foreach ($apisClassNames as $api) {
            $this->app->when($this->trimFQCN($api))
                ->needs(ClientInterface::class)
                ->give(fn () => new Client([
                    'handler' => $handler,
                    'base_uri' => $baseUri,
                    'ganesha.service_name' => $serviceName,
                    'timeout' => $config['timeout'] ?? self::DEFAULT_TIMEOUT,
                ]));
        }
    }

    private function trimFQCN(string $name): string
    {
        return ltrim($name, '\\');
    }
}
