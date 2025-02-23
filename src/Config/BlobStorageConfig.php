<?php

declare(strict_types=1);

namespace Mutario\AzureStorage\Config;

/**
 * Configuration class for Azure Blob Storage
 */
class BlobStorageConfig
{
    public const AZURITE_ACCOUNT_NAME = 'devstoreaccount1';
    public const AZURITE_ACCOUNT_KEY = 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==';
    public const AZURITE_BLOB_ENDPOINT = 'http://127.0.0.1:10000/devstoreaccount1';

    /**
     * Authentication types
     */
    public const AUTH_TYPE_SHARED_KEY = 'shared_key';
    public const AUTH_TYPE_SAS = 'sas';

    /**
     * @param string $accountName The storage account name
     * @param string $containerName The container name
     * @param string $blobEndpoint The blob storage endpoint
     * @param string $authType The authentication type (shared_key or sas)
     * @param string|null $accountKey The storage account key (required for shared key auth)
     * @param string|null $sasToken The SAS token (required for SAS auth)
     * @param bool $useAzurite Whether to use Azurite emulator
     */
    public function __construct(
        private readonly string $accountName,
        private readonly string $containerName,
        private readonly string $blobEndpoint,
        private readonly string $authType = self::AUTH_TYPE_SHARED_KEY,
        private readonly ?string $accountKey = null,
        private readonly ?string $sasToken = null,
        private readonly bool $useAzurite = false
    ) {
        if ($authType === self::AUTH_TYPE_SHARED_KEY && $accountKey === null) {
            throw new \InvalidArgumentException('Account key is required for shared key authentication');
        }

        if ($authType === self::AUTH_TYPE_SAS && $sasToken === null) {
            throw new \InvalidArgumentException('SAS token is required for SAS authentication');
        }
    }

    /**
     * Create a configuration instance for Azurite local development
     *
     * @param string $containerName The container name to use
     * @return self
     */
    public static function createAzuriteConfig(string $containerName): self
    {
        return new self(
            accountName: self::AZURITE_ACCOUNT_NAME,
            containerName: $containerName,
            blobEndpoint: self::AZURITE_BLOB_ENDPOINT,
            authType: self::AUTH_TYPE_SHARED_KEY,
            accountKey: self::AZURITE_ACCOUNT_KEY,
            useAzurite: true
        );
    }

    /**
     * Create a configuration instance for SAS token authentication
     *
     * @param string $accountName The storage account name
     * @param string $containerName The container name
     * @param string $sasToken The SAS token (without leading '?')
     * @return self
     */
    public static function createSasConfig(
        string $accountName,
        string $containerName,
        string $sasToken
    ): self {
        // Remove leading '?' if present
        $sasToken = ltrim($sasToken, '?');
        
        return new self(
            accountName: $accountName,
            containerName: $containerName,
            blobEndpoint: "https://$accountName.blob.core.windows.net",
            authType: self::AUTH_TYPE_SAS,
            sasToken: $sasToken
        );
    }

    /**
     * Get the storage account name
     *
     * @return string
     */
    public function getAccountName(): string
    {
        return $this->accountName;
    }

    /**
     * Get the storage account key
     *
     * @return string|null
     */
    public function getAccountKey(): ?string
    {
        return $this->accountKey;
    }

    /**
     * Get the SAS token
     *
     * @return string|null
     */
    public function getSasToken(): ?string
    {
        return $this->sasToken;
    }

    /**
     * Get the container name
     *
     * @return string
     */
    public function getContainerName(): string
    {
        return $this->containerName;
    }

    /**
     * Get the blob endpoint
     *
     * @return string
     */
    public function getBlobEndpoint(): string
    {
        return $this->blobEndpoint;
    }

    /**
     * Get the authentication type
     *
     * @return string
     */
    public function getAuthType(): string
    {
        return $this->authType;
    }

    /**
     * Check if using Azurite emulator
     *
     * @return bool
     */
    public function isUsingAzurite(): bool
    {
        return $this->useAzurite;
    }
} 