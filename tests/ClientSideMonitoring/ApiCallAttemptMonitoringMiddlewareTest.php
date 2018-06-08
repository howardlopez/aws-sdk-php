<?php

namespace Aws\Test\ClientSideMonitoring;

use Aws\Api\Parser\Exception\ParserException;
use Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware;
use Aws\ClientSideMonitoring\Configuration;
use Aws\Command;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\MonitoringEventsInterface;
use Aws\Result;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware
 * @covers \Aws\ClientSideMonitoring\AbstractMonitoringMiddleware
 * @todo Use data provider pattern for testing data population
 */
class ApiCallAttemptMonitoringMiddlewareTest extends TestCase
{

    protected function getConfiguration()
    {
        return new Configuration(true, 31000, 'AwsPhpSdkTestApp');
    }

    protected function getCredentialProvider()
    {
        return CredentialProvider::fromCredentials(
            new Credentials('testkey', 'testsecret', 'testtoken')
        );
    }

    /**
     * Used to get non-public methods for testing
     *
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected function getMethod($name)
    {
        $class = new \ReflectionClass('Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    protected function resetMiddlewareSocket()
    {
        $prepareSocket = $this->getMethod('prepareSocket');
        $middleware = new ApiCallAttemptMonitoringMiddleware(function(){},
            $this->getCredentialProvider(),
            $this->getConfiguration(),
            'test',
            'test');
        $prepareSocket->invokeArgs($middleware, array(true));
    }

    public function getMonitoringDataTests()
    {
        $command = new Command('RunScheduledInstances', [
            'LaunchSpecification' => [
                'ImageId' => 'test-image',
            ],
            'ScheduledInstanceId' => 'test-instance-id',
            'InstanceCount' => 1,
        ]);
        $request = new Request(
            'POST',
            'http://foo.com',
            [
                'User-Agent' => 'foo-agent'
            ]
        );

        $tests = [
            [
                $command,
                $request,
                [
                    '@metadata' => [
                        'statusCode' => 200,
                        'headers' => [
                            'x-amz-request-id' => 'testrequestid1',
                            'x-amzn-RequestId' => 'testrequestid2',
                            'x-amz-id-2' => 'testamzid',
                        ],
                        'transferStats' => [
                            'http' => [
                                [
                                    'total_time' => .12,
                                    'primary_ip' => '12.34.56.78',
                                    'namelookup_time' => .012,
                                ],
                            ]
                        ],
                    ],
                ],
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'AttemptLatency' => 120,
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'DestinationIp' => '12.34.56.78',
                    'DnsLatency' => 12,
                    'Fqdn' => 'foo.com',
                    'HttpStatusCode' => 200,
                    'Region' => 'us-east-1',
                    'Service' => 'ec2',
                    'SessionToken' => 'testtoken',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                    'XAmzRequestId' => 'testrequestid1',
                    'XAmznRequestId' => 'testrequestid2',
                    'XAmzId2' => 'testamzid',
                ]
            ],
            [
                $command,
                $request,
                [
                    '@metadata' => [
                        'statusCode' => 200,
                        'transferStats' => [
                            'http' => []
                        ],
                    ],
                ],
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'Fqdn' => 'foo.com',
                    'HttpStatusCode' => 200,
                    'Region' => 'us-east-1',
                    'Service' => 'ec2',
                    'SessionToken' => 'testtoken',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                ]
            ],
        ];

        $data = ApiCallAttemptMonitoringMiddleware::getResponseDataConfiguration();
        if (!empty($data['AwsException']['maxLength'])) {
            $maxLength = $data['AwsException']['maxLength'];

            $message = 'This is a test exception message!';
            $code = str_repeat('a', 2 * $maxLength);
            $tests []= [
                $command,
                $request,
                new AwsException(
                    $message,
                    $command,
                    [
                        'message' => $message,
                        'code' => $code,
                        'response' => new Response(405, [
                            'x-amz-request-id' => 'testrequestid1',
                            'x-amzn-RequestId' => 'testrequestid2',
                            'x-amz-id-2' => 'testamzid'
                        ]),
                        'result' => [
                            '@metadata' => [
                                'statusCode' => 200,
                                'headers' => [
                                    'x-amz-request-id' => 'testrequestid1',
                                    'x-amzn-RequestId' => 'testrequestid2',
                                    'x-amz-id-2' => 'testamzid',
                                ],
                                'transferStats' => [
                                    'http' => [
                                        [
                                            'total_time' => .12,
                                            'primary_ip' => '12.34.56.78',
                                            'namelookup_time' => .012,
                                        ],
                                    ]
                                ],
                            ],
                        ],
                        'transfer_stats' => [
                            'total_time' => .12,
                            'primary_ip' => '12.34.56.78',
                            'namelookup_time' => .012,
                        ],
                    ]
                ),
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'AttemptLatency' => 120,
                    'AwsException' => str_repeat('a', $maxLength),
                    'AwsExceptionMessage' => $message,
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'DestinationIp' => '12.34.56.78',
                    'DnsLatency' => 12,
                    'Fqdn' => 'foo.com',
                    'HttpStatusCode' => 405,
                    'Region' => 'us-east-1',
                    'Service' => 'ec2',
                    'SessionToken' => 'testtoken',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                    'XAmzRequestId' => 'testrequestid1',
                    'XAmznRequestId' => 'testrequestid2',
                    'XAmzId2' => 'testamzid',
                ]
            ];
            $tests []= [
                $command,
                $request,
                new ParserException(
                    $message
                ),
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'Fqdn' => 'foo.com',
                    'Region' => 'us-east-1',
                    'SdkException' => ParserException::class,
                    'SdkExceptionMessage' => $message,
                    'Service' => 'ec2',
                    'SessionToken' => 'testtoken',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                ]
            ];
            $tests []= [
                $command,
                $request,
                new AwsException(
                    $message,
                    $command,
                    [
                        'message' => $message,
                        'code' => $code,
                        'response' => new Response(405)
                    ]
                ),
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'AwsException' => str_repeat('a', $maxLength),
                    'AwsExceptionMessage' => $message,
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'Fqdn' => 'foo.com',
                    'Region' => 'us-east-1',
                    'SessionToken' => 'testtoken',
                    'Service' => 'ec2',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                ]
            ];
            $tests []= [
                $command,
                $request,
                new AwsException(
                    $message,
                    $command,
                    [
                        'message' => $message,
                        'code' => $code,
                    ]
                ),
                [
                    'AccessKey' => 'testkey',
                    'Api' => 'RunScheduledInstances',
                    'AwsException' => str_repeat('a', $maxLength),
                    'AwsExceptionMessage' => $message,
                    'ClientId' => 'AwsPhpSdkTestApp',
                    'Fqdn' => 'foo.com',
                    'Region' => 'us-east-1',
                    'SessionToken' => 'testtoken',
                    'Service' => 'ec2',
                    'Type' => 'ApiCallAttempt',
                    'UserAgent' => 'foo-agent' . ' ' .
                        \GuzzleHttp\default_user_agent(),
                    'Version' => 1,
                ]
            ];
        }

        return $tests;
    }

    /**
     * @dataProvider getMonitoringDataTests
     */
    public function testPopulatesMonitoringData(
        $command,
        $request,
        $result,
        $expected
    ) {
        $this->resetMiddlewareSocket();
        $called = false;
        $isResultException = $result instanceof \Exception
            || $result instanceof \Throwable;

        $list = new HandlerList();
        $list->setHandler(function ($command, $request) use (
            $result,
            $isResultException,
            &$called
        ) {
            $called = true;
            if ($isResultException) {
                return Promise\rejection_for($result);
            }
            return Promise\promise_for(new Result($result));
        });
        $list->appendBuild(ApiCallAttemptMonitoringMiddleware::wrap(
            $this->getCredentialProvider(),
            $this->getConfiguration(),
            'us-east-1',
            'ec2'
        ));
        $handler = $list->resolve();

        try {
            /** @var MonitoringEventsInterface $response */
            $response = $handler(
                $command,
                $request
            )->wait();

            if ($isResultException) {
                $this->fail('Should have received a rejection.');
            }

            $eventData = $response->getMonitoringEvents()[0];
        } catch (\Exception $e) {
            if (!$isResultException) {
                $this->fail('Should not have received a rejection.');
            } else if (!($e instanceof MonitoringEventsInterface)) {
                $this->fail('Unable to validate the specified behavior');
            }
            $eventData = $e->getMonitoringEvents()[0];
        }

        $this->assertTrue($called);
        $this->assertArraySubset($expected, $eventData);
        $this->assertInternalType('int', $eventData['Timestamp']);
    }
}
