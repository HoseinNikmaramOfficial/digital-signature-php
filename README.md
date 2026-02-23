# Simple Digital Signature System in PHP

A lightweight and simple library for creating and verifying digital signatures for documents, without the need for complex cryptographic libraries.

## Features

- Generate public/private key pairs
- Digitally sign documents and messages
- Verify signature authenticity
- Detect tampered documents
- Secure key storage
- No OpenSSL or heavy libraries required
- Timing attack protection
- Simple and clean API

## Quick Installation

```bash
git clone https://github.com/HoseinNikmaramOfficial/digital-signature-php
cd digital-signature-php
```

## Usage Examples

### Basic Example - Sign and Verify

```php
<?php
require_once 'src/DigitalSignature.php';

// Create a signer instance
$signer = new DigitalSignature();

// Document to sign
$document = "
CONTRACT AGREEMENT
Between Company A and Company B
Date: 2024-01-15
Amount: $100,000
";

echo "Original Document:\n" . $document . "\n";

// Sign the document
$signature = $signer->sign($document);
echo "Digital Signature:\n";
print_r(json_decode($signature, true));

// Save document and signature
file_put_contents('document.txt', $document);
file_put_contents('signature.json', $signature);

// Later, verify the signature
if ($signer->verify($document, $signature)) {
    echo "Signature is valid. Document has not been tampered.\n";
} else {
    echo "Signature is invalid.\n";
}
```

### Password Hashing Example

```php
<?php
require_once 'src/DigitalSignature.php';

class PasswordManager {
    private $signer;
    
    public function __construct() {
        $this->signer = new DigitalSignature('password_keys.json');
    }
    
    public function hashPassword($password) {
        // Add salt to password for extra security
        $salted = "PASSWORD_SALT_" . $password;
        return $this->signer->sign($salted);
    }
    
    public function verifyPassword($password, $hash) {
        $salted = "PASSWORD_SALT_" . $password;
        return $this->signer->verify($salted, $hash);
    }
}

// Usage example
$pm = new PasswordManager();
$password = "my_secure_password";
$hash = $pm->hashPassword($password);

echo "Password Hash: " . $hash . "\n";

if ($pm->verifyPassword($password, $hash)) {
    echo "Password correct.\n";
} else {
    echo "Password incorrect.\n";
}

// Test with wrong password
if ($pm->verifyPassword("wrong_password", $hash)) {
    echo "This should not happen.\n";
} else {
    echo "Wrong password rejected.\n";
}
```

### File Integrity Checker

```php
<?php
require_once 'src/DigitalSignature.php';

class FileIntegrityChecker {
    private $signer;
    
    public function __construct() {
        $this->signer = new DigitalSignature('file_integrity_keys.json');
    }
    
    public function signFile($filename) {
        if (!file_exists($filename)) {
            throw new Exception("File not found: " . $filename);
        }
        
        $content = file_get_contents($filename);
        $signature = $this->signer->sign($content);
        
        // Save signature alongside the file
        file_put_contents($filename . '.sig', $signature);
        
        return $signature;
    }
    
    public function verifyFile($filename) {
        if (!file_exists($filename)) {
            echo "File not found.\n";
            return false;
        }
        
        if (!file_exists($filename . '.sig')) {
            echo "Signature file not found.\n";
            return false;
        }
        
        $content = file_get_contents($filename);
        $signature = file_get_contents($filename . '.sig');
        
        return $this->signer->verify($content, $signature);
    }
}

// Usage example
$checker = new FileIntegrityChecker();

// Sign a file
$filename = 'important_document.pdf';
if (file_exists($filename)) {
    $checker->signFile($filename);
    echo "File signed successfully.\n";
}

// Check integrity
if ($checker->verifyFile($filename)) {
    echo "File integrity verified. No modifications detected.\n";
} else {
    echo "Warning: File has been modified or signature is invalid.\n";
}
```

### API Authentication Example

```php
<?php
require_once 'src/DigitalSignature.php';

class APIAuthenticator {
    private $signer;
    private $apiKeys = [];
    private $keysFile;
    
    public function __construct($keysFile = 'api_keys.json') {
        $this->keysFile = $keysFile;
        $this->signer = new DigitalSignature('api_signer_keys.json');
        $this->loadKeys();
    }
    
    public function generateAPIKey($clientName) {
        $apiKey = bin2hex(random_bytes(16));
        $signature = $this->signer->sign($apiKey);
        
        $this->apiKeys[$clientName] = [
            'key' => $apiKey,
            'signature' => $signature,
            'created' => time(),
            'last_used' => null
        ];
        
        $this->saveKeys();
        
        return [
            'api_key' => $apiKey,
            'client' => $clientName,
            'message' => 'Store this key securely. It will not be shown again.'
        ];
    }
    
    public function validateAPIKey($apiKey) {
        foreach ($this->apiKeys as $client => $data) {
            if ($this->signer->verify($apiKey, $data['signature'])) {
                // Update last used timestamp
                $this->apiKeys[$client]['last_used'] = time();
                $this->saveKeys();
                
                return $client;
            }
        }
        return false;
    }
    
    public function revokeAPIKey($clientName) {
        if (isset($this->apiKeys[$clientName])) {
            unset($this->apiKeys[$clientName]);
            $this->saveKeys();
            return true;
        }
        return false;
    }
    
    private function loadKeys() {
        if (file_exists($this->keysFile)) {
            $this->apiKeys = json_decode(file_get_contents($this->keysFile), true);
        }
    }
    
    private function saveKeys() {
        file_put_contents($this->keysFile, json_encode($this->apiKeys, JSON_PRETTY_PRINT));
        chmod($this->keysFile, 0600);
    }
}

// Usage example
$auth = new APIAuthenticator();

// Generate API key for a client
$result = $auth->generateAPIKey("Client XYZ");
echo "New API Key generated:\n";
echo "Client: " . $result['client'] . "\n";
echo "API Key: " . $result['api_key'] . "\n";
echo $result['message'] . "\n\n";

// Validate API key
$testKey = $result['api_key'];
$client = $auth->validateAPIKey($testKey);

if ($client) {
    echo "Valid API key for: " . $client . "\n";
} else {
    echo "Invalid API key.\n";
}

// Try with invalid key
$fakeKey = "invalid_key_123";
$client = $auth->validateAPIKey($fakeKey);

if ($client) {
    echo "This should not happen.\n";
} else {
    echo "Invalid key rejected.\n";
}
```

### Multi-Signature Document Example

```php
<?php
require_once 'src/DigitalSignature.php';

class MultiSignatureDocument {
    private $document;
    private $signatures = [];
    private $documentId;
    
    public function __construct($content) {
        $this->document = $content;
        $this->documentId = hash('sha256', $content);
    }
    
    public function addSignature($signerName, $privateKeyFile = null) {
        $signatureTool = new DigitalSignature($privateKeyFile);
        $signature = $signatureTool->sign($this->document);
        
        $signatureData = [
            'signer' => $signerName,
            'public_key_id' => $signatureTool->getKeyId(),
            'signature' => $signature,
            'timestamp' => time(),
            'verified' => false
        ];
        
        $this->signatures[] = $signatureData;
        
        return count($this->signatures);
    }
    
    public function verifyAllSignatures() {
        $results = [];
        
        foreach ($this->signatures as $index => $sig) {
            $verifier = new DigitalSignature();
            $isValid = $verifier->verify($this->document, $sig['signature']);
            
            $this->signatures[$index]['verified'] = $isValid;
            
            $results[] = [
                'signer' => $sig['signer'],
                'valid' => $isValid,
                'timestamp' => date('Y-m-d H:i:s', $sig['timestamp'])
            ];
        }
        
        return $results;
    }
    
    public function getSignatureSummary() {
        $total = count($this->signatures);
        $valid = count(array_filter($this->signatures, function($sig) {
            return $sig['verified'];
        }));
        
        return [
            'document_id' => $this->documentId,
            'total_signatures' => $total,
            'valid_signatures' => $valid,
            'all_valid' => ($total === $valid)
        ];
    }
    
    public function exportDocument() {
        return [
            'document_id' => $this->documentId,
            'content' => $this->document,
            'signatures' => $this->signatures,
            'signed_at' => time()
        ];
    }
}

// Usage example
$contract = "
CONTRACT OF EMPLOYMENT
Between: Tech Company Inc.
And: John Doe
Position: Senior Developer
Salary: $120,000 per year
Date: January 15, 2024
";

$multiDoc = new MultiSignatureDocument($contract);

// Add multiple signatures
$multiDoc->addSignature("HR Manager");
$multiDoc->addSignature("Legal Department");
$multiDoc->addSignature("John Doe (Employee)");

echo "Document signed by 3 parties.\n";

// Verify all signatures
$results = $multiDoc->verifyAllSignatures();

echo "\nVerification Results:\n";
foreach ($results as $result) {
    $status = $result['valid'] ? "VALID" : "INVALID";
    echo "- " . $result['signer'] . ": " . $status . " (" . $result['timestamp'] . ")\n";
}

// Get summary
$summary = $multiDoc->getSignatureSummary();
echo "\nSummary:\n";
echo "Document ID: " . $summary['document_id'] . "\n";
echo "Valid Signatures: " . $summary['valid_signatures'] . "/" . $summary['total_signatures'] . "\n";
```

## How It Works

1. **Key Generation**: Creates a pair of keys using random_bytes and hashing algorithms
2. **Signing**: Uses HMAC-SHA256 on the message hash combined with timestamp and nonce
3. **Verification**: Securely compares signatures with protection against timing attacks

## Security Features

- Random salt generation for each signature
- Timestamp to prevent replay attacks
- Nonce for uniqueness
- Timing attack safe comparison (hash_equals)
- Secure file permissions for key storage
- Encrypted private key storage

## Testing

Run the test suite:

```bash
php tests/test_signature.php
```

## Requirements

- PHP 7.0 or higher
- OpenSSL extension (optional, for enhanced encryption)

## License

MIT License - feel free to use this in your projects.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
