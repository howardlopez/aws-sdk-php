<?php

namespace Aws\ClientSideMonitoring;

use Aws\Api\ApiProvider;
use Aws\Api\Service;
use Aws\Command;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\Result;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers ApiCallAttemptMonitoringMiddleware
 * @covers AbstractMonitoringMiddleware
 */
class ApiCallAttemptMonitoringMiddlewareTest extends TestCase
{

    protected function getConfiguration()
    {
        return new Configuration(true, 31000, 'TestApp');
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

    protected function getResponse($promise)
    {
        $this->resetMiddlewareSocket();
        $list = new HandlerList();
        $list->setHandler(function ($command, $request) use ($promise) {
            return $promise;
        });

        $list->appendSign(ApiCallAttemptMonitoringMiddleware::wrap(
            $this->getCredentialProvider(),
            $this->getConfiguration(),
            'us-east-1',
            'ec2'
        ));
        $handler = $list->resolve();

        return $handler($this->getTestCommand(),
            new Request('POST', 'http://foo.com/bar/baz'))->wait();
    }

    protected function getTestCommand()
    {
        return new Command('RunScheduledInstances', [
            'LaunchSpecification' => [
                'ImageId' => 'test-image',
            ],
            'ScheduledInstanceId' => 'test-instance-id',
            'InstanceCount' => 1,
        ]);
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

    public function testPopulatesMonitoringData()
    {
        $promise = Promise\promise_for(new Result([
            '@metadata' => [
                'statusCode' => 200,
                'headers' => [
                    'x-amz-request-id' => 'testrequestid1',
                    'x-amzn-RequestId' => 'testrequestid2',
                    'x-amz-id-2' => 'testamzid'
                ]
            ]
        ]));
        $response = $this->getResponse($promise);

        $this->assertArraySubset(
            [
                'AccessKey' => 'testkey',
                'Api' => 'RunScheduledInstances',
                'Fqdn' => 'foo.com',
                'HttpStatusCode' => 200,
                'Region' => 'us-east-1',
                'Type' => 'ApiCallAttempt',
                'Service' => 'ec2',
                'XAmzRequestId' => 'testrequestid1',
                'XAmznRequestId' => 'testrequestid2',
                'XAmzId2' => 'testamzid'
            ],
            $response['@monitoringEvents'][0]
        );
    }

    public function testPopulatesAwsExceptionData()
    {
        $message = 'This is a test exception message!';
        $code = 'TestExceptionCode';
        $promise = Promise\rejection_for(new AwsException(
            $message,
            $this->getTestCommand(),
            [
                'message' => $message,
                'code' => $code,
                'response' => new Response(405)
            ]
        ));
        $response = $this->getResponse($promise);
        $events = $response->getMonitoringEvents();

        $this->assertArraySubset(
            [
                'AwsException' => $code,
                'AwsExceptionMessage' => $message,
                'HttpStatusCode' => 405
            ],
            $events[0]
        );
    }

    public function testPopulatesSdkExceptionData()
    {
        $message = 'This is a test exception message!';
        $code = 111;
        $promise = Promise\rejection_for(new \Exception($message, $code));
        $response = $this->getResponse($promise);
        $events = $response->getMonitoringEvents();

        $this->assertArraySubset(
            [
                'SdkException' => $code,
                'SdkExceptionMessage' => $message
            ],
            $events[0]
        );
    }

    public function testSerializesData()
    {
        $serializeEventData = $this->getMethod('serializeEventData');
        $middleware = new ApiCallAttemptMonitoringMiddleware(function(){}, function(){}, [], 'test', 'test');
        $eventData = [
            'AwsException' => str_repeat('a', 300),
            'AttemptLatency' => 314.15,
            'Fqdn' => 's3-eu-west-1.amazonaws.com',
            'HttpStatusCode' => 200,
            'UserAgent' => 'Test User Agent With Spaces'
        ];
        $awsExceptionMax = $middleware::getDataConfiguration()['AwsException']['maxLength'];
        $expected = '{"AwsException":"' . substr($eventData['AwsException'], 0, $awsExceptionMax) .
            '","AttemptLatency":314.15,"Fqdn":"s3-eu-west-1.amazonaws.com",' .
            '"HttpStatusCode":200,"UserAgent":"Test User Agent With Spaces"}';

        $this->assertSame($expected,
            $serializeEventData->invokeArgs($middleware, array($eventData)));
    }
}
