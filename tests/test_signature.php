<?php
require_once '../src/DigitalSignature.php';

class SignatureTest {
    
    public function run() {
        echo "Running Digital Signature Tests\n";
        echo "================================\n\n";
        
        $this->testSignatureCreation();
        $this->testSignatureVerification();
        $this->testTamperedDocument();
        $this->testMultipleSignatures();
    }
    
    private function testSignatureCreation() {
        $signer = new DigitalSignature('test_keys.json');
        $doc = "Test Document";
        
        $sig = $signer->sign($doc);
        
        assert(!empty($sig), "Signature is empty!");
        echo "[PASS] Signature creation test passed\n";
    }
    
    private function testSignatureVerification() {
        $signer = new DigitalSignature('test_keys.json');
        $doc = "Test Document";
        
        $sig = $signer->sign($doc);
        $result = $signer->verify($doc, $sig);
        
        assert($result === true, "Signature should be verified!");
        echo "[PASS] Signature verification test passed\n";
    }
    
    private function testTamperedDocument() {
        $signer = new DigitalSignature('test_keys.json');
        $doc = "Test Document";
        
        $sig = $signer->sign($doc);
        $result = $signer->verify("Fake Document", $sig);
        
        assert($result === false, "Fake document should not be verified!");
        echo "[PASS] Tampered document detection test passed\n";
    }
    
    private function testMultipleSignatures() {
        $signer = new DigitalSignature('test_keys.json');
        
        $docs = [
            "Test Document 1",
            "Test Document 2",
            "Test Document 3",
        ];
        
        foreach ($docs as $doc) {
            $sig = $signer->sign($doc);
            assert($signer->verify($doc, $sig), "Signature of '$doc' not verified!");
        }
        
        echo "[PASS] Multiple signatures test passed\n";
    }
}

// Run the tests
$test = new SignatureTest();
$test->run();