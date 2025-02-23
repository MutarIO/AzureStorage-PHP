# Azure Blob Storage PHP Library

A modern PHP library for interacting with Azure Blob Storage, with support for local Azurite emulator.

## Features

- List all files in a blob storage container
- Delete specific files
- Upload new files
- Download specific files
- Support for local Azurite blob storage emulator
- Multiple authentication methods (Shared Key and SAS Token)
- Modern PHP 8.1+ syntax with strict typing
- Professional OOP design
- Comprehensive error handling

## Installation

Install the package via composer:

```bash
composer require marco/azure-storage-php
```

## Usage

### Initialize with Cloud Storage (Shared Key Authentication)

```php
use Marco\AzureStorage\BlobStorage;
use Marco\AzureStorage\Config\BlobStorageConfig;

// Initialize with cloud storage credentials
$config = new BlobStorageConfig(
    accountName: 'your-account-name',
    containerName: 'your-container-name',
    blobEndpoint: 'https://your-account.blob.core.windows.net',
    authType: BlobStorageConfig::AUTH_TYPE_SHARED_KEY,
    accountKey: 'your-account-key'
);

$storage = new BlobStorage($config);
```

### Initialize with SAS Token Authentication

```php
use Marco\AzureStorage\BlobStorage;
use Marco\AzureStorage\Config\BlobStorageConfig;

// Initialize with SAS token
$config = BlobStorageConfig::createSasConfig(
    accountName: 'your-account-name',
    containerName: 'your-container-name',
    sasToken: 'sv=2020-08-04&ss=b&srt=sco&sp=rwdlacitfx&se=2023-12-31T23:59:59Z&st=2023-01-01T00:00:00Z&spr=https&sig=...'
);

$storage = new BlobStorage($config);
```

### Initialize with Azurite (Local Development)

```php
use Marco\AzureStorage\BlobStorage;
use Marco\AzureStorage\Config\BlobStorageConfig;

// Initialize with Azurite emulator
$config = BlobStorageConfig::createAzuriteConfig('your-container-name');
$storage = new BlobStorage($config);
```

### List All Blobs

```php
try {
    $blobs = $storage->listBlobs();
    foreach ($blobs as $blob) {
        echo "Name: {$blob['name']}\n";
        echo "Last Modified: {$blob['lastModified']}\n";
        echo "Size: {$blob['contentLength']} bytes\n";
        echo "Content Type: {$blob['contentType']}\n";
        echo "-------------------\n";
    }
} catch (RuntimeException $e) {
    echo "Error listing blobs: " . $e->getMessage();
}
```

### Upload a File

```php
try {
    $storage->uploadBlob(
        blobName: 'path/to/remote/file.txt',
        filePath: '/path/to/local/file.txt',
        contentType: 'text/plain' // optional
    );
    echo "File uploaded successfully!";
} catch (RuntimeException $e) {
    echo "Error uploading file: " . $e->getMessage();
}
```

### Download a File

```php
try {
    $storage->downloadBlob(
        blobName: 'path/to/remote/file.txt',
        destinationPath: '/path/to/local/destination.txt'
    );
    echo "File downloaded successfully!";
} catch (RuntimeException $e) {
    echo "Error downloading file: " . $e->getMessage();
}
```

### Delete a File

```php
try {
    $storage->deleteBlob('path/to/remote/file.txt');
    echo "File deleted successfully!";
} catch (RuntimeException $e) {
    echo "Error deleting file: " . $e->getMessage();
}
```

## Error Handling

All methods throw `RuntimeException` when an error occurs. The exception message contains details about what went wrong. It's recommended to always wrap calls in try-catch blocks as shown in the examples above.

## Authentication Methods

### Shared Key Authentication
This is the traditional authentication method using your storage account key. It's recommended for server-side applications where you can securely store the account key.

### SAS Token Authentication
SAS (Shared Access Signature) tokens provide limited access to your storage account resources. They are ideal for:
- Client-side applications where you don't want to expose your account key
- Providing temporary access to specific resources
- Implementing fine-grained access control

When using SAS tokens, make sure to:
- Generate tokens with the minimum required permissions
- Set appropriate expiration times
- Rotate tokens regularly for security
- Remove the leading '?' from the SAS token string if present

## Development with Azurite

When developing locally, you can use the Azurite emulator. The library includes built-in support for Azurite with the correct default credentials and endpoint. Simply use the `BlobStorageConfig::createAzuriteConfig()` factory method to create a configuration for local development.

Make sure you have Azurite running locally on the default ports before using the library in local mode.

## Requirements

- PHP 8.1 or higher
- ext-json
- GuzzleHttp 7.0 or higher

## License

MIT License 