<?php
class DigitalSignature {
    
    private $privateKey;
    private $publicKey;
    private $keyPairFile;
    
    /**
     * Constructor - loads existing keys or generates new ones
     */
    public function __construct($keyPairFile = 'keys.json') {
        $this->keyPairFile = $keyPairFile;
        
        if (file_exists($keyPairFile)) {
            $this->loadKeys();
        } else {
            $this->generateKeyPair();
            $this->saveKeys();
        }
    }
    
    /**
     * Generate public/private key pair
     */
    private function generateKeyPair() {
        $this->privateKey = bin2hex(random_bytes(32));
        
        $salt = "DigitalSignatureSystemV1";
        $this->publicKey = hash('sha256', $salt . $this->privateKey);
        
        $testSignature = $this->sign("TEST");
        if (!$this->verify("TEST", $testSignature)) {
            throw new Exception("Key pair generation failed: Test signature invalid");
        }
    }
    
    /**
     * Save keys to file
     */
    private function saveKeys() {
        $keys = [
            'private_key' => $this->encryptKey($this->privateKey),
            'public_key' => $this->publicKey,
            'created_at' => date('Y-m-d H:i:s'),
            'key_id' => substr($this->publicKey, 0, 16)
        ];
        
        file_put_contents($this->keyPairFile, json_encode($keys, JSON_PRETTY_PRINT));
        chmod($this->keyPairFile, 0600); // Read/write for owner only
    }
    
    /**
     * Load keys from file
     */
    private function loadKeys() {
        $keys = json_decode(file_get_contents($this->keyPairFile), true);
        
        $this->privateKey = $this->decryptKey($keys['private_key']);
        $this->publicKey = $keys['public_key'];
    }
    
    /**
     * Encrypt private key (simplified - use better encryption in production)
     */
    private function encryptKey($key) {
        $method = "AES-256-CBC";
        $password = "YourSecretPassword"; // Should be from environment variable
        $iv = substr(hash('sha256', $password), 0, 16);
        
        return openssl_encrypt($key, $method, $password, 0, $iv);
    }
    
    /**
     * Decrypt private key
     */
    private function decryptKey($encryptedKey) {
        $method = "AES-256-CBC";
        $password = "YourSecretPassword";
        $iv = substr(hash('sha256', $password), 0, 16);
        
        return openssl_decrypt($encryptedKey, $method, $password, 0, $iv);
    }
    
    /**
     * Sign a message
     */
    public function sign($message) {
        $messageHash = hash('sha256', $message);
        
        $timestamp = time();
        $nonce = bin2hex(random_bytes(8));
        
        $dataToSign = $messageHash . $timestamp . $nonce;
        
        $signature = hash_hmac('sha256', $dataToSign, $this->privateKey);
        
        return json_encode([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'public_key_id' => substr($this->publicKey, 0, 16),
            'version' => '1.0'
        ]);
    }
    
    /**
     * Verify a signature
     */
    public function verify($message, $signatureJson, $publicKey = null) {
        if ($publicKey === null) {
            $publicKey = $this->publicKey;
        }
        
        $signatureData = json_decode($signatureJson, true);
        if (!$signatureData) {
            return false;
        }
        
        $signature = $signatureData['signature'];
        $timestamp = $signatureData['timestamp'];
        $nonce = $signatureData['nonce'];
        
        // Check if signature has expired (optional - 24 hours)
        if (time() - $timestamp > 86400) {
            return false; 
        }
        
        return $this->verifyWithPublicKey($message, $signatureData, $publicKey);
    }
    
    /**
     * Verify signature using public key
     */
    private function verifyWithPublicKey($message, $signatureData, $publicKey) {
        $messageHash = hash('sha256', $message);
        $combined = $messageHash . $signatureData['timestamp'] . $signatureData['nonce'];
        
        $expectedSignature = hash_hmac('sha256', $combined, $publicKey);
        
        return hash_equals($signatureData['signature'], $expectedSignature);
    }
    
    /**
     * Get public key
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
    
    /**
     * Get key ID (first 16 chars of public key)
     */
    public function getKeyId() {
        return substr($this->publicKey, 0, 16);
    }
}