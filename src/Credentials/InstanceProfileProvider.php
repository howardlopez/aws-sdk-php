<?php
namespace Aws\Credentials;

use Aws\Exception\CredentialsException;
use Aws\Exception\InvalidJsonException;
use Aws\Sdk;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Credential provider that provides credentials from the EC2 metadata server.
 */
class InstanceProfileProvider
{
    const SERVER_URI = 'http://169.254.169.254/latest/';
    const CRED_PATH = 'meta-data/iam/security-credentials/';
    const TOKEN_PATH = 'api/token';

    const ENV_DISABLE = 'AWS_EC2_METADATA_DISABLED';

    /** @var string */
    private $profile;

    /** @var callable */
    private $client;

    /** @var int */
    private $retries;

    /** @var int */
    private $attempts;

    /** @var float|mixed */
    private $timeout;

    /** @var bool */
    private $secureMode = true;

    /**
     * The constructor accepts the following options:
     *
     * - timeout: Connection timeout, in seconds.
     * - profile: Optional EC2 profile name, if known.
     * - retries: Optional number of retries to be attempted.
     *
     * @param array $config Configuration options.
     */
    public function __construct(array $config = [])
    {
        $this->timeout = isset($config['timeout']) ? $config['timeout'] : 1.0;
        $this->profile = isset($config['profile']) ? $config['profile'] : null;
        $this->retries = isset($config['retries']) ? $config['retries'] : 3;
        $this->attempts = 0;
        $this->client = isset($config['client'])
            ? $config['client'] // internal use only
            : \Aws\default_http_handler();
    }

    /**
     * Loads instance profile credentials.
     *
     * @return PromiseInterface
     */
    public function __invoke()
    {
        return Promise\coroutine(function () {

            // Retrieve token or switch out of secure mode
            $token = null;
            while ($this->secureMode && is_null($token)) {
                try {
                    $token = (yield $this->request(
                        self::TOKEN_PATH,
                        'PUT',
                        [
                            'x-aws-ec2-metadata-token-ttl-seconds' => 21600
                        ]
                    ));
                } catch (RequestException $e) {
                    if (!empty($e->getResponse())
                        && !in_array(
                            $e->getResponse()->getStatusCode(),
                            [400, 403, 500, 502, 503, 504]
                        )
                    ) {
                        $this->secureMode = false;
                    } else {
                        $this->handleRetryableException(
                            $e,
                            [ 'blacklist' => [401, 403] ],
                            $this->createErrorMessage(
                                'Error retrieving metadata token'
                            )
                        );
                    }
                }
                $this->attempts++;
            }

            // Set token header only for secure mode
            $headers = [];
            if ($this->secureMode) {
                $headers = [
                    'x-aws-ec2-metadata-token' => $token
                ];
            }

            // Retrieve profile
            while (!$this->profile) {
                try {
                    $this->profile = (yield $this->request(
                        self::CRED_PATH,
                        'GET',
                        $headers
                    ));
                } catch (RequestException $e) {
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage($e->getMessage())
                    );
                }

                $this->attempts++;
            }

            // Retrieve credentials
            $result = null;
            while ($result == null) {
                try {
                    $json = (yield $this->request(
                        self::CRED_PATH . $this->profile,
                        'GET',
                        $headers
                    ));
                    $result = $this->decodeResult($json);
                } catch (InvalidJsonException $e) {
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage(
                            'Invalid JSON response, retries exhausted'
                        )
                    );
                } catch (RequestException $e) {
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage($e->getMessage())
                    );
                }
                $this->attempts++;
            }
            yield new Credentials(
                $result['AccessKeyId'],
                $result['SecretAccessKey'],
                $result['Token'],
                strtotime($result['Expiration'])
            );
        });
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $headers
     * @return PromiseInterface Returns a promise that is fulfilled with the
     *                          body of the response as a string.
     */
    private function request($url, $method = 'GET', $headers = [])
    {
        $disabled = getenv(self::ENV_DISABLE) ?: false;
        if (strcasecmp($disabled, 'true') === 0) {
            throw new CredentialsException(
                $this->createErrorMessage('EC2 metadata server access disabled')
            );
        }

        $fn = $this->client;
        $request = new Request($method, self::SERVER_URI . $url);
        $userAgent = 'aws-sdk-php/' . Sdk::VERSION;
        if (defined('HHVM_VERSION')) {
            $userAgent .= ' HHVM/' . HHVM_VERSION;
        }
        $userAgent .= ' ' . \Aws\default_user_agent();
        $request = $request->withHeader('User-Agent', $userAgent);
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $fn($request, ['timeout' => $this->timeout, 'debug' => true])
            ->then(function (ResponseInterface $response) {
                return (string) $response->getBody();
            })->otherwise(function (array $reason) {
                $reason = $reason['exception'];
                if ($reason instanceof \GuzzleHttp\Exception\RequestException) {
                    throw $reason;
                }
                $msg = $reason->getMessage();
                throw new CredentialsException(
                    $this->createErrorMessage($msg)
                );
            });
    }

    private function handleRetryableException(
        \Exception $e,
        $retryOptions,
        $message
    ) {
        $isRetryable = true;
        if (!empty($status = $this->getExceptionStatusCode($e))
            && isset($retryOptions['blacklist'])
            && in_array($status, $retryOptions['blacklist'])
        ) {
            $isRetryable = false;
        }
        if ($isRetryable && $this->attempts < $this->retries) {
            sleep(pow(1.2, $this->attempts));
        } else {
            throw new CredentialsException($message);
        }
    }

    private function getExceptionStatusCode(\Exception $e)
    {
        if (method_exists($e, 'getResponse')
            && !empty($e->getResponse())
        ) {
            return $e->getResponse()->getStatusCode();
        }
        return null;
    }

    private function createErrorMessage($previous)
    {
        return "Error retrieving credentials from the instance profile "
            . "metadata server. ({$previous})";
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if (json_last_error() > 0) {
            throw new InvalidJsonException();
        }

        if ($result['Code'] !== 'Success') {
            throw new CredentialsException('Unexpected instance profile '
                .  'response code: ' . $result['Code']);
        }

        return $result;
    }
}
