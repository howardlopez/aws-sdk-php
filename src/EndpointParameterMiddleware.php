<?php
namespace Aws;

use Aws\Api\Service;
use Psr\Http\Message\RequestInterface;

/**
 * Used to update the host based on a modeled endpoint trait
 *
 * IMPORTANT: this middleware must be added after the "build" step.
 *
 * @internal
 */
class EndpointParameterMiddleware
{

    /**
     * Create a middleware wrapper function
     *
     * @param Service $service
     * @return \Closure
     */
    public static function wrap(Service $service)
    {
        return function (callable $handler) use ($service) {
            return new self($handler, $service);
        };
    }

    public function __construct(callable $nextHandler, Service $service)
    {
        $this->nextHandler = $nextHandler;
        $this->service = $service;
    }

    public function __invoke(CommandInterface $command, RequestInterface $request)
    {
        $nextHandler = $this->nextHandler;
        $operation = $this->service->getOperation($command->getName());

        if (!empty($operation['endpoint']['host'])) {
            $host = $operation['endpoint']['host'];
            preg_match_all("/\{([a-zA-Z0-9]+)}/", $host, $parameters);

            if (!empty($parameters[1])) {
                foreach ($parameters[1] as $index => $parameter) {
                    if (empty($command[$parameter])) {
                        throw new \InvalidArgumentException("The parameter '{$parameter}' must be set and not empty.");
                    }
                    $host = str_replace(
                        $parameters[0][$index],
                        $command[$parameter],
                        $host
                    );
                    unset($command[$parameter]);
                }
            }

            $uri = $request->getUri();
            $host = str_replace('{@}', $uri->getHost(), $host);
            $request = $request->withUri($uri->withHost($host));
        }

        return $nextHandler($command, $request);
    }
}
