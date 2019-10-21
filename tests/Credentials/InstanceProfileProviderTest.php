<?php
namespace Aws\Test\Credentials;

use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Aws\Credentials\InstanceProfileProvider;
use Aws\Exception\CredentialsException;
use Aws\Sdk;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @covers InstanceProfileProvider
 */
class InstanceProfileProviderTest extends TestCase
{
    private function getCredentialArray(
        $key,
        $secret,
        $token = null,
        $time = null,
        $success = true
    )
    {
        return [
            'Code' => $success ? 'Success' : 'Failed',
            'AccessKeyId' => $key,
            'SecretAccessKey' => $secret,
            'Token' => $token,
            'Expiration' => $time
        ];
    }

    /**
     * Test client for secure data flow with metadata token requirement
     *
     * @param array $responses
     * @param string $profile
     * @param array $creds
     * @return \Closure
     */
    private function getSecureTestClient(
        $responses = [],
        $profile = 'MockProfile',
        $creds = ['foo_key', 'baz_secret', 'qux_token', null]
    )
    {
        $putRequests = 0;
        $getProfileRequests = 0;
        $getCredsRequests = 0;

        return function (RequestInterface $request) use (
            $responses,
            $profile,
            $creds,
            &$putRequests,
            &$getProfileRequests,
            &$getCredsRequests
        ) {
            if ($request->getMethod() === 'PUT'
                && $request->getUri()->getPath() === '/latest/api/token'
            ) {
                if (empty($request->getHeader('x-aws-ec2-metadata-token-ttl-seconds'))) {
                    return Promise\rejection_for([
                        'exception' => new \Exception('400 Bad Request - TTL header required')
                    ]);
                }
                if (isset($responses['put'])) {
                    return $responses['put'][$putRequests++];
                } else {
                    return Promise\promise_for(
                        new Response(
                            200,
                            [],
                            Psr7\stream_for('MOCK_TOKEN_VALUE')
                        )
                    );
                }
            }
            if ($request->getMethod() === 'GET') {
                if (empty($request->getHeader('x-aws-ec2-metadata-token'))
                    || $request->getHeader('x-aws-ec2-metadata-token')[0]
                    !== 'MOCK_TOKEN_VALUE'
                ) {
                    return Promise\rejection_for([
                        'exception' => new \Exception(
                            '401 Unauthorized - Valid unexpired token required'
                        )
                    ]);
                }
                switch ($request->getUri()->getPath()) {
                    case '/latest/meta-data/iam/security-credentials':
                    case '/latest/meta-data/iam/security-credentials/':
                        if (isset($responses['get_profile'])) {
                            return $responses['get_profile'][$getProfileRequests++];
                        }
                        return Promise\promise_for(
                            new Response(200, [], Psr7\stream_for($profile))
                        );
                        break;

                    case "/latest/meta-data/iam/security-credentials/{$profile}":
                    case "/latest/meta-data/iam/security-credentials/{$profile}/":
                        if (isset($responses['get_creds'])) {
                            return $responses['get_creds'][$getCredsRequests++];
                        }
                        return Promise\promise_for(
                            new Response(
                                200,
                                [],
                                Psr7\stream_for(
                                    json_encode(call_user_func_array(
                                        [$this, 'getCredentialArray'],
                                        $creds
                                    ))
                                )
                            )
                        );
                        break;
                }
            }

            return Promise\rejection_for([
                'exception' => new \Exception(
                    'Invalid path passed to test server'
                )
            ]);
        };
    }

    /**
     * Test client for insecure data flow with no token requirement
     *
     * @param array $responses
     * @param string $profile
     * @param array $creds
     * @return \Closure
     */
    private function getInsecureTestClient(
        $responses = [],
        $profile = 'MockProfile',
        $creds = ['foo_key', 'baz_secret', 'qux_token', null]
    )
    {
        $getProfileRequests = 0;
        $getCredsRequests = 0;

        return function (RequestInterface $request) use (
            $responses,
            $profile,
            $creds,
            &$getProfileRequests,
            &$getCredsRequests
        ) {
            if ($request->getMethod() === 'PUT'
                && $request->getUri()->getPath() === '/latest/api/token'
            ) {
                return Promise\rejection_for([
                    'exception' => new RequestException(
                        '404 Not Found',
                        $request,
                        new Response(404)
                    )
                ]);
            }
            if ($request->getMethod() === 'GET') {
                switch ($request->getUri()->getPath()) {
                    case '/latest/meta-data/iam/security-credentials':
                    case '/latest/meta-data/iam/security-credentials/':
                        if (isset($responses['get_profile'])) {
                            return $responses['get_profile'][$getProfileRequests++];
                        }
                        return Promise\promise_for(
                            new Response(200, [], Psr7\stream_for($profile))
                        );
                        break;

                    case "/latest/meta-data/iam/security-credentials/{$profile}":
                    case "/latest/meta-data/iam/security-credentials/{$profile}/":
                        if (isset($responses['get_creds'])) {
                            return $responses['get_creds'][$getCredsRequests++];
                        }
                        return Promise\promise_for(
                            new Response(
                                200,
                                [],
                                Psr7\stream_for(
                                    json_encode(call_user_func_array(
                                        [$this, 'getCredentialArray'],
                                        $creds
                                    ))
                                )
                            )
                        );
                        break;
                }
            }

            return Promise\rejection_for([
                'exception' => new \Exception(
                    'Invalid path passed to test server'
                )
            ]);
        };
    }

    /**
     * @dataProvider successTestCases
     *
     * @param $client
     * @param $expected
     */
    public function testHandlesSuccessScenarios(
        callable $client,
        CredentialsInterface $expected
    )
    {
        $provider = new InstanceProfileProvider([
            'client' => $client,
            'retries' => 5
        ]);

        /** @var CredentialsInterface $credentials */
        $credentials = $provider()->wait();
        $this->assertEquals(
            $expected->getAccessKeyId(),
            $credentials->getAccessKeyId()
        );
        $this->assertEquals(
            $expected->getSecretKey(),
            $credentials->getSecretKey()
        );
        $this->assertEquals(
            $expected->getSecurityToken(),
            $credentials->getSecurityToken()
        );
        $this->assertEquals(
            $expected->getExpiration(),
            $credentials->getExpiration()
        );
    }

    public function successTestCases()
    {
        $expiry = time() + 1000;
        $creds = ['foo_key', 'baz_secret', 'qux_token', "@{$expiry}"];
        $credsObject = new Credentials($creds[0], $creds[1], $creds[2], $expiry);

        // Guzzle 5 vs 6 Request namespace differences
        $version = (string) ClientInterface::VERSION;
        if ($version[0] === '5') {
            $getRequest = new \GuzzleHttp\Message\Request(
                'GET',
                '/latest/meta-data/foo'
            );
            $putRequest = new \GuzzleHttp\Message\Request(
                'PUT',
                '/latest/meta-data/foo'
            );
        } else {
            $getRequest = new Request('GET', '/latest/meta-data/foo');
            $putRequest = new Request('PUT', '/latest/meta-data/foo');
        }

        $getThrottleException = new RequestException(
            '503 ThrottlingException',
            $getRequest,
            new Response(503)
        );
        $putThrottleException = new RequestException(
            '503 ThrottlingException',
            $putRequest,
            new Response(503)
        );

        $promiseProfile = Promise\promise_for(
            new Response(200, [], Psr7\stream_for('MockProfile'))
        );
        $promiseCreds = Promise\promise_for(
            new Response(200, [], Psr7\stream_for(
                json_encode(call_user_func_array(
                    [$this, 'getCredentialArray'],
                    $creds
                )))
            )
        );
        $promiseBadJsonCreds = Promise\promise_for(
            new Response(200, [], Psr7\stream_for('{'))
        );

        $rejectionThrottleProfile = Promise\rejection_for([
            'exception' => $getThrottleException
        ]);
        $rejectionThrottleCreds = Promise\rejection_for([
            'exception' => $getThrottleException
        ]);

        return [
            // Secure data flow, happy path
            [
                $this->getSecureTestClient([], 'MockProfile', $creds),
                $credsObject
            ],

            // Insecure data flow, happy path
            [
                $this->getInsecureTestClient([], 'MockProfile', $creds),
                $credsObject
            ],

            // Secure data flow, with retries for request exception
            [
                $this->getSecureTestClient(
                    [
                        'put' => [
                            Promise\rejection_for([
                                'exception' => $putThrottleException
                            ]),
                            Promise\promise_for(
                                new Response(200, [], Psr7\stream_for('MOCK_TOKEN_VALUE'))
                            )
                        ],
                        'get_profile' => [
                            $rejectionThrottleProfile,
                            $promiseProfile
                        ],
                        'get_creds' => [
                            $rejectionThrottleCreds,
                            $promiseCreds
                        ],
                    ],
                    'MockProfile',
                    $creds
                ),
                $credsObject
            ],

            // Insecure data flow, with retries for request exception
            [
                $this->getInsecureTestClient(
                    [
                        'get_profile' => [
                            $rejectionThrottleProfile,
                            $promiseProfile
                        ],
                        'get_creds' => [
                            $rejectionThrottleCreds,
                            $promiseCreds
                        ],
                    ],
                    'MockProfile',
                    $creds
                ),
                $credsObject
            ],

            // Secure data flow, with retries for json exception
            [
                $this->getSecureTestClient(
                    [
                        'get_creds' => [
                            $promiseBadJsonCreds,
                            $promiseCreds
                        ],
                    ],
                    'MockProfile',
                    $creds
                ),
                $credsObject
            ],

            // Insecure data flow, with retries for json exception
            [
                $this->getInsecureTestClient(
                    [
                        'get_creds' => [
                            $promiseBadJsonCreds,
                            $promiseCreds
                        ],
                    ],
                    'MockProfile',
                    $creds
                ),
                $credsObject
            ],
        ];
    }


    /**
     * @dataProvider failureTestCases
     *
     * @param $client
     * @param \Exception $expected
     */
    public function testHandlesFailureScenarios($client, \Exception $expected)
    {
        $provider = new InstanceProfileProvider([
            'client' => $client,
            'retries' => 1,
        ]);

        try {
            $provider()->wait();
            $this->fail('Provider should have thrown an exception.');
        } catch (\Exception $e) {
            $this->assertEquals(get_class($expected), get_class($e));
            $this->assertEquals($expected->getMessage(), $e->getMessage());
        }
    }

    public function failureTestCases()
    {
        // Guzzle 5 vs 6 Request namespace differences
        $version = (string) ClientInterface::VERSION;
        if ($version[0] === '5') {
            $getRequest = new \GuzzleHttp\Message\Request(
                'GET',
                '/latest/meta-data/foo'
            );
            $putRequest = new \GuzzleHttp\Message\Request(
                'PUT',
                '/latest/meta-data/foo'
            );
        } else {
            $getRequest = new Request('GET', '/latest/meta-data/foo');
            $putRequest = new Request('PUT', '/latest/meta-data/foo');
        }

        $promiseBadJsonCreds = Promise\promise_for(
            new Response(200, [], Psr7\stream_for('{'))
        );
        $rejectionToken = Promise\rejection_for([
            'exception' => new RequestException(
                '403 Forbidden',
                $putRequest,
                new Response(403)
            )
        ]);
        $rejectionThrottleToken = Promise\rejection_for([
            'exception' => new RequestException(
                '503 ThrottlingException',
                $putRequest,
                new Response(503)
            )
        ]);
        $rejectionProfile = Promise\rejection_for([
            'exception' => new RequestException(
                '401 Unathorized',
                $getRequest,
                new Response(401)
            )
        ]);
        $rejectionThrottleProfile = Promise\rejection_for([
            'exception' => new RequestException(
                '503 ThrottlingException',
                $getRequest,
                new Response(503)
            )
        ]);
        $rejectionCreds = Promise\rejection_for([
            'exception' => new RequestException(
                '401 Unathorized',
                $getRequest,
                new Response(401)
            )
        ]);
        $rejectionThrottleCreds = Promise\rejection_for([
            'exception' => new RequestException(
                '503 ThrottlingException',
                $getRequest,
                new Response(503)
            )
        ]);

        return [
            // Secure data flow, token call, non-retryable error
            [
                $this->getSecureTestClient(
                    [
                        'put' => [$rejectionToken]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                        . 'metadata server. (Error retrieving metadata token)'
                )
            ],

            // Secure data flow, profile call, non-retryable error
            [
                $this->getSecureTestClient(
                    [
                        'get_profile' => [$rejectionProfile]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (401 Unathorized)'
                )
            ],

            // Insecure data flow, profile call, non-retryable error
            [
                $this->getInsecureTestClient(
                    [
                        'get_profile' => [$rejectionProfile]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (401 Unathorized)'
                )
            ],

            // Secure data flow, credentials call, non-retryable error
            [
                $this->getSecureTestClient(
                    [
                        'get_creds' => [$rejectionCreds]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (401 Unathorized)'
                )
            ],

            // Insecure data flow, credentials call, non-retryable error
            [
                $this->getInsecureTestClient(
                    [
                        'get_creds' => [$rejectionCreds]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (401 Unathorized)'
                )
            ],

            // Secure data flow, token call, retryable error
            [
                $this->getSecureTestClient(
                    [
                        'put' => [
                            $rejectionThrottleToken,
                            $rejectionThrottleToken,
                            $rejectionThrottleToken,
                        ]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (Error retrieving metadata token)'
                )
            ],

            // Secure data flow, profile call, retryable error
            [
                $this->getSecureTestClient(
                    [
                        'get_profile' => [
                            $rejectionThrottleProfile,
                            $rejectionThrottleProfile,
                            $rejectionThrottleProfile,
                        ]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (503 ThrottlingException)'
                )
            ],

            // Insecure data flow, profile call, retryable error
            [
                $this->getInsecureTestClient(
                    [
                        'get_profile' => [
                            $rejectionThrottleProfile,
                            $rejectionThrottleProfile,
                            $rejectionThrottleProfile,
                        ],
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (503 ThrottlingException)'
                )
            ],

            // Secure data flow, credentials call, retryable error
            [
                $this->getSecureTestClient(
                    [
                        'get_creds' => [
                            $rejectionThrottleCreds,
                            $rejectionThrottleCreds,
                            $rejectionThrottleCreds,
                        ],
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (503 ThrottlingException)'
                )
            ],

            // Insecure data flow, credentials call, retryable error
            [
                $this->getInsecureTestClient(
                    [
                        'get_creds' => [
                            $rejectionThrottleCreds,
                            $rejectionThrottleCreds,
                            $rejectionThrottleCreds,
                        ],
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (503 ThrottlingException)'
                )
            ],

            // Secure data flow, credentials call, retryable invalid json error
            [
                $this->getSecureTestClient(
                    [
                        'get_creds' => [
                            $promiseBadJsonCreds,
                            $promiseBadJsonCreds,
                            $promiseBadJsonCreds
                        ]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (Invalid JSON response, retries exhausted)'
                )
            ],

            // Insecure data flow, credentials call, retryable invalid json error
            [
                $this->getInsecureTestClient(
                    [
                        'get_creds' => [
                            $promiseBadJsonCreds,
                            $promiseBadJsonCreds,
                            $promiseBadJsonCreds
                        ]
                    ],
                    'MockProfile'
                ),
                new CredentialsException(
                    'Error retrieving credentials from the instance profile '
                    . 'metadata server. (Invalid JSON response, retries exhausted)'
                )
            ],
        ];
    }

    private function getTestCreds(
        $result,
        $profile = null,
        array $args = []
    ) {
        $args['profile'] = $profile;
        $args['client'] = $this->getSecureTestClient([], $profile, $result);
        $provider = new InstanceProfileProvider($args);

        return $provider();
    }

    public function testAddsUserAgentToRequest()
    {
        $response = new Response(200, [], Psr7\stream_for('test'));
        $client = function (RequestInterface $request) use ($response) {
            $this->assertEquals(
                'aws-sdk-php/' . Sdk::VERSION . ' ' . \Aws\default_user_agent(),
                $request->getHeader('User-Agent')[0]
            );

            return Promise\promise_for($response);
        };
        $provider = new InstanceProfileProvider(['client' => $client]);
        return $provider();
    }

    public function testSeedsInitialCredentials()
    {
        $t = time() + 1000;
        $c = $this->getTestCreds(
            ['foo', 'baz', null, "@{$t}"],
            'foo'
        )->wait();
        $this->assertEquals('foo', $c->getAccessKeyId());
        $this->assertEquals('baz', $c->getSecretKey());
        $this->assertNull($c->getSecurityToken());
        $this->assertEquals($t, $c->getExpiration());
    }

    /**
     * @expectedException \Aws\Exception\CredentialsException
     * @expectedExceptionMessage Unexpected instance profile response
     */
    public function testThrowsExceptionOnInvalidMetadata()
    {
        $this->getTestCreds(
            $this->getCredentialArray(null, null, null, null, false),
            'foo'
        )->wait();
    }

    public function testDoesNotRequireConfig()
    {
        new InstanceProfileProvider();
    }

    public function testEnvDisableFlag()
    {
        $flag = getenv(InstanceProfileProvider::ENV_DISABLE);

        try {
            putenv(InstanceProfileProvider::ENV_DISABLE . '=true');
            $t = time() + 1000;
            $this->getTestCreds(
                json_encode($this->getCredentialArray('foo', 'baz', null, "@{$t}"))
            )->wait();
            $this->fail('Did not throw expected CredentialException.');
        } catch (CredentialsException $e) {
            if (strstr($e->getMessage(), 'EC2 metadata server access disabled') === false) {
                $this->fail('Did not throw expected CredentialException when '
                    . 'provider is disabled.');
            }
        } finally {
            putenv(InstanceProfileProvider::ENV_DISABLE . '=' . $flag);
        }
    }
}
