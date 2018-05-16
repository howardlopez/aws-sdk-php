<?php

namespace Aws\ClientSideMonitoring;

use Aws\CommandInterface;
use Aws\ResultInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class ApiCallMonitoringMiddleware extends AbstractMonitoringMiddleware
{
    /**
     * @inheritdoc
     */
    protected static function getDataConfiguration()
    {
        static $callDataConfig;
        if (empty($callDataConfig)) {
            $callDataConfig = [
                [
                    'valueObject' => CommandInterface::class,
                    'valueAccessor' => function (CommandInterface $cmd) {
                        return $cmd->getName();
                    },
                    'eventKey' => 'Api',
                ],
            ];
        }

        static $dataConfig;
        if (empty($dataConfig)) {
            $dataConfig = array_merge(
                $callDataConfig,
                parent::getDataConfiguration()
            );
        }

        return $dataConfig;
    }

    /**
     * @inheritdoc
     */
    protected function populateRequestEventData(
        CommandInterface $cmd,
        RequestInterface $request,
        array $event
    ) {
        $event = parent::populateRequestEventData($cmd, $request, $event);
        // Do local changes
        return $event;
    }

    /**
     * @inheritdoc
     */
    protected function populateResponseEventData(
        ResultInterface $result,
        array $event
    ) {
        $event = parent::populateResponseEventData($result, $event);
        // Do local changes
        return $event;
    }
}