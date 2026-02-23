<?php
require_once '../src/DigitalSignature.php';

echo "Digital Signature Generator\n";
echo "==========================\n\n";

$signer = new DigitalSignature();

$document = "
CONTRACT AGREEMENT
Between Party A and Party B
Date: 2024-01-15
Terms and conditions of the agreement...
";

echo "Original Document:\n";
echo $document . "\n";
echo "----------------------------------------\n";

$signature = $signer->sign($document);
echo "Generated Digital Signature:\n";
print_r(json_decode($signature, true));
echo "\n";

// Save document and signature
file_put_contents('document.txt', $document);
file_put_contents('signature.json', $signature);

echo "Document and signature saved successfully.\n";
echo "Public Key: " . $signer->getPublicKey() . "\n";
echo "Key ID: " . $signer->getKeyId() . "\n";