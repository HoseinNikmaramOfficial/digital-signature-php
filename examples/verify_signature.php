<?php
require_once '../src/DigitalSignature.php';

echo "Digital Signature Verifier\n";
echo "==========================\n\n";

// Load document and signature
$document = file_get_contents('document.txt');
$signature = file_get_contents('signature.json');

echo "Loaded Document:\n" . $document . "\n";
echo "Loaded Signature:\n" . $signature . "\n\n";
echo "----------------------------------------\n";

$verifier = new DigitalSignature();

// Verify original document
if ($verifier->verify($document, $signature)) {
    echo "[VALID] Signature verified successfully. Document is authentic.\n";
} else {
    echo "[INVALID] Signature verification failed. Document may be tampered.\n";
}

// Test with tampered document
$fakeDocument = $document . "\n\n(Modified content - UNAUTHORIZED)";
echo "\nTesting with modified document:\n";
if ($verifier->verify($fakeDocument, $signature)) {
    echo "[SECURITY ALERT] Tampered document was accepted!\n";
} else {
    echo "[SECURE] Tampered document was correctly rejected.\n";
}