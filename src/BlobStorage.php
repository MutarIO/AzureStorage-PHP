<?php

declare(strict_types=1);

namespace Marco\AzureStorage;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Marco\AzureStorage\Auth\SharedKeyAuthenticator;
use Marco\AzureStorage\Config\BlobStorageConfig;
use RuntimeException;

/**
 * Main class for interacting with Azure Blob Storage
 */
class BlobStorage
{
    private Client $client;
    private ?SharedKeyAuthenticator $authenticator;

    /**
     * @param BlobStorageConfig $config The blob storage configuration
     */
    public function __construct(
        private readonly BlobStorageConfig $config
    ) {
        $this->client = new Client();
        $this->authenticator = $config->getAuthType() === BlobStorageConfig::AUTH_TYPE_SHARED_KEY
            ? new SharedKeyAuthenticator($config)
            : null;
    }

    /**
     * Get request headers based on authentication type
     *
     * @param string $method The HTTP method
     * @param string $path The request path
     * @param array<string, string> $headers Additional headers
     * @param string $contentLength Content length for PUT requests
     * @return array<string, string>
     */
    private function getRequestHeaders(
        string $method,
        string $path,
        array $headers = [],
        string $contentLength = '0'
    ): array {
        if ($this->config->getAuthType() === BlobStorageConfig::AUTH_TYPE_SHARED_KEY) {
            return $this->authenticator->getAuthorizationHeaders($method, $path, $headers, $contentLength);
        }

        // For SAS token authentication, just add the token to the headers
        return $headers;
    }

    /**
     * Get the full URL for a request including SAS token if using SAS authentication
     *
     * @param string $path The request path
     * @return string
     */
    private function getRequestUrl(string $path): string
    {
        $url = $this->config->getBlobEndpoint() . $path;
        
        if ($this->config->getAuthType() === BlobStorageConfig::AUTH_TYPE_SAS) {
            $url .= (str_contains($path, '?') ? '&' : '?') . $this->config->getSasToken();
        }
        
        return $url;
    }

    /**
     * List all blobs in the container
     *
     * @return array<array{name: string, lastModified: string, contentLength: int, contentType: string}>
     * @throws RuntimeException
     */
    public function listBlobs(): array
    {
        try {
            $path = "/{$this->config->getContainerName()}?restype=container&comp=list";
            $headers = $this->getRequestHeaders('GET', $path);

            $response = $this->client->get(
                $this->getRequestUrl($path),
                ['headers' => $headers]
            );

            $xml = simplexml_load_string($response->getBody()->getContents());
            $blobs = [];

            if ($xml === false) {
                throw new RuntimeException('Failed to parse XML response');
            }

            foreach ($xml->Blobs->Blob ?? [] as $blob) {
                $blobs[] = [
                    'name' => (string)$blob->Name,
                    'lastModified' => (string)$blob->Properties->{'Last-Modified'},
                    'contentLength' => (int)$blob->Properties->{'Content-Length'},
                    'contentType' => (string)$blob->Properties->{'Content-Type'},
                ];
            }

            return $blobs;
        } catch (GuzzleException $e) {
            throw new RuntimeException("Failed to list blobs: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Delete a specific blob
     *
     * @param string $blobName The name of the blob to delete
     * @throws RuntimeException
     */
    public function deleteBlob(string $blobName): void
    {
        try {
            $path = "/{$this->config->getContainerName()}/$blobName";
            $headers = $this->getRequestHeaders('DELETE', $path);

            $this->client->delete(
                $this->getRequestUrl($path),
                ['headers' => $headers]
            );
        } catch (GuzzleException $e) {
            throw new RuntimeException("Failed to delete blob: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Upload a file to blob storage
     *
     * @param string $blobName The name to give the blob
     * @param string $filePath The local file path to upload
     * @param string|null $contentType The content type of the file (optional)
     * @throws RuntimeException
     */
    public function uploadBlob(string $blobName, string $filePath, ?string $contentType = null): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        try {
            $path = "/{$this->config->getContainerName()}/$blobName";
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new RuntimeException("Failed to read file: $filePath");
            }

            $headers = [
                'Content-Type' => $contentType ?? mime_content_type($filePath) ?: 'application/octet-stream',
                'Content-Length' => (string)strlen($content),
                'x-ms-blob-type' => 'BlockBlob'
            ];

            $headers = $this->getRequestHeaders('PUT', $path, $headers, (string)strlen($content));

            $this->client->put(
                $this->getRequestUrl($path),
                [
                    'headers' => $headers,
                    'body' => $content
                ]
            );
        } catch (GuzzleException $e) {
            throw new RuntimeException("Failed to upload blob: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Download a blob to a local file
     *
     * @param string $blobName The name of the blob to download
     * @param string $destinationPath The local path to save the file to
     * @throws RuntimeException
     */
    public function downloadBlob(string $blobName, string $destinationPath): void
    {
        try {
            $path = "/{$this->config->getContainerName()}/$blobName";
            $headers = $this->getRequestHeaders('GET', $path);

            $response = $this->client->get(
                $this->getRequestUrl($path),
                [
                    'headers' => $headers,
                    'sink' => $destinationPath
                ]
            );
        } catch (GuzzleException $e) {
            throw new RuntimeException("Failed to download blob: {$e->getMessage()}", 0, $e);
        }
    }
} 