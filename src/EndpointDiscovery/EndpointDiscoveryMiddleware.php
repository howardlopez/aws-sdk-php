<?php
namespace Aws\EndpointDiscovery;

use Aws\Api\Operation;
use Aws\Api\Service;
use Aws\AwsClient;
use Aws\CacheInterface;
use Aws\CommandInterface;
use Aws\Credentials\CredentialsInterface;
use Aws\Exception\AwsException;
use Aws\Exception\UnresolvedEndpointException;
use Aws\LruArrayCache;
use Aws\Middleware;
use Psr\Http\Message\RequestInterface;

class EndpointDiscoveryMiddleware
{
    /**
     * @var CacheInterface
     */
    private static $cache;

    private $args;
    private $client;
    private $config;
    private $credentials;
    private $discoveryTimes;
    private $nextHandler;
    private $service;

    public static function wrap(
        $client,
        $args,
        $credentials,
        $service,
        $config
    ) {
        return function (callable $handler) use (
            $client,
            $args,
            $credentials,
            $service,
            $config
        ) {
            return new static(
                $handler,
                $client,
                $args,
                $credentials,
                $service,
                $config
            );
        };
    }

    public function __construct(
        callable $handler,
        AwsClient $client,
        array $args,
        callable $credentials,
        Service $service,
        $config
    ) {
        $this->nextHandler = $handler;
        $this->client = $client;
        $this->args = $args;
        $this->credentials = $credentials;
        $this->service = $service;
        $this->config = $config;
        $this->discoveryTimes = [];
    }

    public function __invoke(CommandInterface $cmd, RequestInterface $request)
    {
        $nextHandler = $this->nextHandler;
        $op = $this->service->getOperation($cmd->getName())->toArray();

        // Continue only if endpointdiscovery trait is set
        if (isset($op['endpointdiscovery'])) {

            $config = ConfigurationProvider::unwrap($this->config);
            $isRequired = !empty($op['endpointdiscovery']['required']);

            // Continue only if required by operation or enabled by config
            if ($isRequired || $config->isEnabled()) {
                if (isset($op['endpointoperation'])) {
                    throw new UnresolvedEndpointException('This operation is contradictorily marked both as using endpoint discovery and being the endpoint discovery operation. Please verify the accuracy of your model files.');
                }

                // Original endpoint may be used if discovery optional
                $originalUri = $request->getUri();

                // Get identifiers
                $inputShape = $this->service->getShapeMap()->resolve($op['input'])->toArray();
                $identifiers = [];
                foreach ($inputShape['members'] as $key => $member) {
                    if (!empty($member['endpointdiscoveryid'])) {
                        $identifiers[] = $key;
                    }
                }

                $credentials = $this->credentials;
                $cacheKey = $this->getCacheKey(
                    $credentials()->wait(),
                    $cmd,
                    $identifiers
                );

                // Check/create cache
                if (!isset(self::$cache)) {
                    self::$cache = new LruArrayCache($config->getCacheLimit());
                }

                $endpoint = null;
                $endpointList = self::$cache->get($cacheKey);
                if (!empty($endpointList)) {
                    $endpoint = $endpointList->getActive();
                }

                // Retrieve endpoints if there is no active endpoint
                if (empty($endpoint)) {

                    try {
                        $endpoint = $this->discoverEndpoint(
                            $cacheKey,
                            $cmd,
                            $identifiers
                        );
                    } catch (\Exception $e) {
                        // Use expired endpoint if any remain in the list
                        if (!empty($endpointList)) {
                            $endpoint = $endpointList->getExpired();
                        }

                        if (empty($endpoint)) {
                            // If no cached endpoints but discovery isn't required,
                            // use original endpoint
                            if (!$isRequired) {
                                $endpoint = $originalUri->getHost() . $originalUri->getPath();
                                $request = $this->modifyRequest($request, $endpoint);
                                return $nextHandler($cmd, $request);
                            }
                            // If discovery is required, throw exception
                            throw new AwsException(
                                'The endpoint required for this service is currently unable to be retrieved, and your request can not be fulfilled unless you manually specify an endpoint.',
                                $cmd,
                                [],
                                $e
                            );
                        }
                    }
                }

                $request = $this->modifyRequest($request, $endpoint);

                $f = function ($value) {
                    return $value;
                };

                $g = function ($value) use (
                    $cacheKey,
                    $cmd,
                    $endpoint,
                    $endpointList,
                    $identifiers,
                    $isRequired,
                    $nextHandler,
                    $originalUri,
                    $request,
                    $f,
                    &$g
                ) {
                    if ($value instanceof AwsException
                        && ($value->getAwsErrorCode() == 'InvalidEndpointException'
                            || $value->getStatusCode() == 421
                        )
                    ) {
                        if ($endpointList instanceof EndpointList) {

                            // Remove invalid endpoints from cached list
                            $endpointList->remove($endpoint);

                            // If possible, get new endpoint from active or
                            // expired cache
                            $newEndpoint = $endpointList->getActive();
                            if (empty($newEndpoint)) {
                                $newEndpoint = $endpointList->getExpired();
                            }
                        }
                        if (empty($newEndpoint)) {

                            // If no more cached endpoints, make discovery call
                            // if none made within last 60 seconds for given key
                            if (time() - $this->discoveryTimes[$cacheKey] < 60) {

                                // If no more cached endpoints and it's required,
                                // fail with original exception
                                if ($isRequired) {
                                    return $value;
                                }

                                // Use original endpoint if not required
                                $newEndpoint = $originalUri->getHost() . $originalUri->getPath();
                            } else {
                                $newEndpoint = $this->discoverEndpoint(
                                    $cacheKey,
                                    $cmd,
                                    $identifiers
                                );
                            }
                        }
                        $request = $this->modifyRequest($request, $newEndpoint);
                        return $nextHandler($cmd, $request)->then($f, $g);
                    }
                };

                return $nextHandler($cmd, $request)->then($f, $g);
            }
        }

        return $nextHandler($cmd, $request);
    }

    private function discoverEndpoint($cacheKey, $cmd, $identifiers)
    {
        $discCmd = $this->getDiscoveryCommand($cmd, $identifiers);
        $this->discoveryTimes[$cacheKey] = time();
        $result = $this->client->execute($discCmd);

        if (isset($result['Endpoints'])) {
            $endpointData = [];
            foreach ($result['Endpoints'] as $datum) {
                $endpointData[$datum['Address']] = time()
                    + ($datum['CachePeriodInMinutes'] * 60);
            }
            $endpointList = new EndpointList($endpointData);
            self::$cache->set($cacheKey, $endpointList);
            return $endpointList->getActive();
        } else {
            throw new UnresolvedEndpointException('The endpoint discovery operation yielded a response that did not contain properly formatted endpoint data.');
        }
    }

    private function getCacheKey(
        CredentialsInterface $creds,
        CommandInterface $cmd,
        array $identifiers
    ) {
        $key = $creds->getAccessKeyId();
        if (!empty($identifiers)) {
            $key .= '_' . $cmd->getName();
            foreach ($identifiers as $identifier) {
                $key .= "_{$cmd[$identifier]}";
            }
        }

        return $key;
    }

    private function getDiscoveryCommand(CommandInterface $cmd, array $identifiers)
    {
        foreach ($this->service->getOperations() as $op) {
            if (isset($op['endpointoperation'])) {
                $endpointOperation = $op->toArray()['name'];
                break;
            }
        }

        if (!isset($endpointOperation)) {
            throw new AwsException('This command is set to use endpoint discovery, but no endpoint discovery operation was found. Please verify the accuracy of your model files.', $cmd);
        }

        $params = [];
        if (!empty($identifiers)) {
            $params['Operation'] = $cmd->getName();
            $params['Identifiers'] = [];
            foreach($identifiers as $identifier) {
                $params['Identifiers'][$identifier] = $cmd[$identifier];
            }
        }
        $command = $this->client->getCommand($endpointOperation, $params);
        $command->getHandlerList()->appendBuild(
            Middleware::mapRequest(function (RequestInterface $r) {
                return $r->withHeader('x-amz-api-version', $this->service->getApiVersion());
            }),
            'x-amz-api-version-header'
        );

        return $command;
    }

    private function modifyRequest(RequestInterface $request, $endpoint)
    {
        $parsed = $this->parseEndpoint($endpoint);
        if (!empty($request->getHeader('User-Agent'))) {
            $userAgent = $request->getHeader('User-Agent')[0];
            if (strpos($userAgent, 'endpoint-discovery') === false) {
                $userAgent = $userAgent . ' endpoint-discovery';
            }
        } else {
            $userAgent = 'endpoint-discovery';
        }

        return $request
            ->withUri($request->getUri()
                ->withHost($parsed['host'])
                ->withPath($parsed['path'])
            )
            ->withHeader('User-Agent', $userAgent);
    }

    /**
     * Parses an endpoint returned from the discovery API into an array with
     * 'host' and 'path' keys.
     *
     * @param $endpoint
     * @return array
     */
    private function parseEndpoint($endpoint)
    {
        $parsed = parse_url($endpoint);

        // parse_url() will correctly parse full URIs with schemes
        if (isset($parsed['host'])) {
            return $parsed;
        }

        // parse_url() will put host & path in 'path' if scheme is not provided
        if (isset($parsed['path'])) {
            $split = explode('/', $parsed['path'], 2);
            $parsed['host'] = $split[0];
            if (isset($split[1])) {
                $parsed['path'] = $split[1];
            } else {
                $parsed['path'] = '';
            }
            return $parsed;
        }

        throw new UnresolvedEndpointException("The supplied endpoint '{$endpoint}' is invalid.");
    }
}