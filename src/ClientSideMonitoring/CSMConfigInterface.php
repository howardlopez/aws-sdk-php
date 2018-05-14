<?php
namespace Aws\ClientSideMonitoring;

/**
 * Provides access to the AWS CSM configuration options:
 * 'client_id', 'enabled', 'port'
 */
interface CSMConfigInterface
{
    /**
     * Returns whether or not CSM is enabled
     *
     * @return bool
     */
    public function getEnabled();

    /**
     * Returns the client ID if available
     *
     * @return string|null
     */
    public function getClientId();

    /**
     * Returns the port if available
     *
     * @return int|null
     */
    public function getPort();

    /**
     * Converts the config to an associative array.
     *
     * @return array
     */
    public function toArray();
}
