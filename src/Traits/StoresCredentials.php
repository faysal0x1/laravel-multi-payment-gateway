<?php

namespace MultiPayment\Traits;

trait StoresCredentials
{
    /**
     * The credentials storage.
     *
     * @var array
     */
    protected $credentials = [];

    /**
     * Set credentials for a specific gateway.
     *
     * @param string $gateway
     * @param array $credentials
     * @return void
     */
    public function setCredentials(string $gateway, array $credentials)
    {
        $this->credentials[$gateway] = $credentials;
    }

    /**
     * Get credentials for a specific gateway.
     *
     * @param string $gateway
     * @return array|null
     */
    public function getCredentials(string $gateway)
    {
        return $this->credentials[$gateway] ?? null;
    }

    /**
     * Check if credentials exist for a specific gateway.
     *
     * @param string $gateway
     * @return bool
     */
    public function hasCredentials(string $gateway)
    {
        return isset($this->credentials[$gateway]);
    }

    /**
     * Remove credentials for a specific gateway.
     *
     * @param string $gateway
     * @return void
     */
    public function removeCredentials(string $gateway)
    {
        unset($this->credentials[$gateway]);
    }

    /**
     * Get all stored credentials.
     *
     * @return array
     */
    public function getAllCredentials()
    {
        return $this->credentials;
    }
}
