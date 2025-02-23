<?php

declare(strict_types=1);

namespace Mutario\AzureStorage\Auth;

use Mutario\AzureStorage\Config\BlobStorageConfig;
use DateTime;
use DateTimeZone;

/**
 * Handles Shared Key authentication for Azure Blob Storage
 */
class SharedKeyAuthenticator
{
    /**
     * @param BlobStorageConfig $config The blob storage configuration
     */
    public function __construct(
        private readonly BlobStorageConfig $config
    ) {
    }

    /**
     * Generate the authorization header for a request
     *
     * @param string $method The HTTP method (GET, PUT, DELETE, etc.)
     * @param string $path The request path
     * @param array<string, string> $headers The request headers
     * @param string $contentLength The content length (default '0')
     * @return array<string, string> The headers with authorization
     */
    public function getAuthorizationHeaders(
        string $method,
        string $path,
        array $headers = [],
        string $contentLength = '0'
    ): array {
        $date = (new DateTime('now', new DateTimeZone('GMT')))->format('D, d M Y H:i:s T');
        
        $headers['x-ms-date'] = $date;
        $headers['x-ms-version'] = '2020-04-08';
        
        // Construct the string to sign
        $stringToSign = implode("\n", [
            $method,
            $headers['Content-MD5'] ?? '',
            $headers['Content-Type'] ?? '',
            '',  // If x-ms-date exists, this should be empty
            $this->getCanonicalizedHeaders($headers),
            $this->getCanonicalizedResource($path)
        ]);

        // Generate the signature
        $signature = base64_encode(
            hash_hmac('sha256', $stringToSign, base64_decode($this->config->getAccountKey()), true)
        );

        // Add the authorization header
        $headers['Authorization'] = sprintf(
            'SharedKey %s:%s',
            $this->config->getAccountName(),
            $signature
        );

        return $headers;
    }

    /**
     * Get canonicalized headers string
     *
     * @param array<string, string> $headers The request headers
     * @return string
     */
    private function getCanonicalizedHeaders(array $headers): string
    {
        $msHeaders = [];
        foreach ($headers as $key => $value) {
            $normalizedKey = strtolower($key);
            if (str_starts_with($normalizedKey, 'x-ms-')) {
                $msHeaders[$normalizedKey] = $value;
            }
        }
        
        ksort($msHeaders);
        
        $canonicalizedHeaders = '';
        foreach ($msHeaders as $key => $value) {
            $canonicalizedHeaders .= "$key:$value\n";
        }
        
        return rtrim($canonicalizedHeaders, "\n");
    }

    /**
     * Get canonicalized resource string
     *
     * @param string $path The request path
     * @return string
     */
    private function getCanonicalizedResource(string $path): string
    {
        $accountName = $this->config->getAccountName();
        return "/$accountName$path";
    }
} 