<?php

namespace Aws\ClientSideMonitoring;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MonitoringEventsInterface;
use Aws\ResponseContainerInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
abstract class AbstractMonitoringMiddleware
    implements MonitoringMiddlewareInterface
{
    private static $socket;

    private $nextHandler;
    private $options;
    protected $credentialProvider;
    protected $region;
    protected $service;

    protected static function getExceptionHeaderAccessor($headerName)
    {
        return function (AwsException $e) use ($headerName) {
            $response = $e->getResponse();
            if ($response !== null) {
                $header = $response->getHeader($headerName);
                if (!empty($header[0])) {
                    return $header[0];
                }
            }
            return null;
        };
    }

    protected static function getResultHeaderAccessor($headerName)
    {
        return function (ResultInterface $result) use ($headerName) {
            if (isset($result['@metadata']['headers'][$headerName])) {
                return $result['@metadata']['headers'][$headerName];
            }
            return null;
        };
    }

    protected static function getResponseContainerHeaderAccessor($headerName)
    {
        return function (ResponseContainerInterface $responseContainer) use ($headerName) {
            $response = $responseContainer->getResponse();
            if ($response instanceof ResponseInterface) {
                $header = $response->getHeader($headerName);
                if (!empty($header[0])) {
                    return $header[0];
                }
            }
            return null;
        };
    }

    /**
     * Standard middleware wrapper function with CSM options passed in.
     *
     * @param callable $credentialProvider
     * @param mixed  $options
     * @param string $region
     * @param string $service
     * @return callable
     */
    public static function wrap(
        callable $credentialProvider,
        $options,
        $region,
        $service
    ) {
        return function (callable $handler) use (
            $credentialProvider,
            $options,
            $region,
            $service
        ) {
            return new static(
                $handler,
                $credentialProvider,
                $options,
                $region,
                $service
            );
        };
    }

    /**
     * Constructor stores the passed in handler and options.
     *
     * @param callable $handler
     * @param callable $credentialProvider
     * @param array $options
     * @param $region
     * @param $service
     */
    public function __construct(
        callable $handler,
        callable $credentialProvider,
        $options,
        $region,
        $service
    ) {
        $this->nextHandler = $handler;
        $this->credentialProvider = $credentialProvider;
        $this->options = $options;
        $this->region = $region;
        $this->service = $service;
    }

    /**
     * Standard invoke pattern for middleware execution to be implemented by
     * child classes.
     *
     * @param  CommandInterface $cmd
     * @param  RequestInterface $request
     * @return Promise
     */
    public function __invoke(CommandInterface $cmd, RequestInterface $request)
    {
        $handler = $this->nextHandler;
        $eventData = null;
        $enabled = $this->isEnabled();

        if ($enabled) {
            $cmd['@http']['collect_stats'] = true;
            $eventData = $this->populateRequestEventData(
                $cmd,
                $request,
                $this->getNewEvent($cmd, $request)
            );
        }

        $g = function ($value) use ($eventData, $enabled) {
            if ($enabled) {
                $eventData = $this->populateResultEventData(
                    $value,
                    $eventData
                );
                $this->sendEventData($eventData);

                if ($value instanceof MonitoringEventsInterface) {
                    $value->addMonitoringEvent($eventData);
                }
            }
            if ($value instanceof \Exception || $value instanceof \Throwable) {
                return Promise\rejection_for($value);
            }
            return $value;
        };

        return $handler($cmd, $request)->then($g, $g);
    }

    private function getClientId()
    {
        return $this->unwrappedOptions()->getClientId();
    }

    private function getNewEvent(
        CommandInterface $cmd,
        RequestInterface $request
    ) {
        $event = [
            'Api' => $cmd->getName(),
            'ClientId' => $this->getClientId(),
            'Region' => $this->getRegion(),
            'Service' => $this->getService(),
            'Timestamp' => (int) floor(microtime(true) * 1000),
            'Version' => 1
        ];
        return $event;
    }

    private function getPort()
    {
        return $this->unwrappedOptions()->getPort();
    }

    private function getRegion()
    {
        return $this->region;
    }

    private function getService()
    {
        return $this->service;
    }

    /**
     * Returns enabled flag from options, unwrapping options if necessary.
     *
     * @return bool
     */
    private function isEnabled()
    {
        return $this->unwrappedOptions()->isEnabled();
    }

    private function getTruncatedValue($value, $datum) {
        if (!empty($datum['maxLength'])) {
            return substr(
                $value,
                0,
                $datum['maxLength']
            );
        }
        return $value;
    }

    /**
     * Returns $eventData array with information from the request and command.
     *
     * @param CommandInterface $cmd
     * @param RequestInterface $request
     * @param array $event
     * @return array
     */
    protected function populateRequestEventData(
        CommandInterface $cmd,
        RequestInterface $request,
        array $event
    ) {
        $dataFormat = static::getRequestDataConfiguration();
        foreach ($dataFormat as $eventKey => $datum) {
            $value = null;
            foreach ($datum['valueAccessors'] as $klass => $accessor) {
                $value = $accessor($cmd, $request);
                if ($value !== null) {
                    $event[$eventKey] = $this->getTruncatedValue($value, $datum);
                    continue 2;
                }
            }
        }
        return $event;
    }

    /**
     * Returns $eventData array with information from the response, including
     * the calculation for attempt latency.
     *
     * @param ResultInterface|\Exception $result
     * @param array $event
     * @return array
     */
    protected function populateResultEventData(
        $result,
        array $event
    ) {
        $dataFormat = static::getResponseDataConfiguration();
        foreach ($dataFormat as $eventKey => $datum) {
            $value = null;
            foreach ($datum['valueAccessors'] as $klass => $accessor) {
                if ($result instanceof $klass) {
                    $value = $accessor($result);
                }
                if ($value !== null) {
                    $event[$eventKey] = $this->getTruncatedValue($value, $datum);
                    continue 2;
                }
            }
        }
        return $event;
    }

    /**
     * Creates a UDP socket resource and stores it with the class, or retrieves
     * it if already instantiated and connected. Handles error-checking and
     * re-connecting if necessary. If $forceNewConnection is set to true, a new
     * socket will be created.
     *
     * @param bool $forceNewConnection
     * @return Resource
     */
    private function prepareSocket($forceNewConnection = false)
    {
        if (!is_resource(self::$socket)
            || $forceNewConnection
            || socket_last_error(self::$socket)
        ) {
            self::$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_clear_error(self::$socket);
            socket_connect(self::$socket, '127.0.0.1', $this->getPort());
        }

        return self::$socket;
    }

    /**
     * Sends formatted monitoring event data via the UDP socket connection to
     * the CSM agent endpoint.
     *
     * @param array $eventData
     * @return int
     */
    private function sendEventData(array $eventData)
    {
        $socket = $this->prepareSocket();
        $datagram = json_encode($eventData);
        $result = socket_write($socket, $datagram, strlen($datagram));
        if ($result === false) {
            $this->prepareSocket(true);
        }
        return $result;
    }

    /**
     * Unwraps options, if needed, and returns them.
     *
     * @return ConfigurationInterface
     */
    private function unwrappedOptions()
    {
        if (!($this->options instanceof ConfigurationInterface)) {
            $this->options = ConfigurationProvider::unwrap($this->options);
        }
        return $this->options;
    }
}