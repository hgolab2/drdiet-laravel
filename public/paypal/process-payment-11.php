<?php

require_once 'vendor/autoload.php';

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

// Set up the PayPal API context
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        'AW5ThXjTyCwNX6DbOg8JLJTaoRN4n-SniYxXgRbu-TN-a1HHqI29czcna8OmsoCc-e2BJ_TeeY0IlxPS',     // Replace with your own client ID
        'ECur2G0VPBpjUtuNu8HqzdsLYapZ5SbA1ORdBdfo-HpxfVz0IzS8mC-Qu1mQxhLAhAwH6VECoUsXgO7F'  // Replace with your own client secret
    )
);
$apiContext->setConfig(['mode' => 'live']);


// Get the payment ID, payer ID, and payment amount from the POST data
$paymentID = $_POST['paymentID'] ?? null;
$payerID = $_POST['payerID'] ?? null;
$paymentAmount = $_POST['paymentAmount'] ?? null;

// Validate that all necessary POST data exists
if (!$paymentID || !$payerID || !$paymentAmount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required payment data']);
    exit();
}


// Get the payment details from PayPal
try {
    $payment = Payment::get($paymentID, $apiContext);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error retrieving payment: ' . $e->getMessage()]);
    exit();
}

// Execute the payment and process the order
$execution = new PaymentExecution();
$execution->setPayerId($payerID);

$transaction = $payment->getTransactions()[0];
$amount = $transaction->getAmount();
if ($amount->getTotal() != $paymentAmount) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment amount mismatch']);
    exit();
}

try {
    $result = $payment->execute($execution, $apiContext);

    if ($result->getState() === 'approved') {
        // TODO: Process the order and update the database
        //echo 'Payment completed successfully!';
        $transaction = $result->getTransactions()[0];
        $transaction_id = $transaction->getRelatedResources()[0]->getSale()->getId(); // Get the Sale ID
        $create_time = $result->getCreateTime();
        $total = $transaction->getAmount()->getTotal();

        $data = ['transactionId' => $transaction_id, 'createTime' => $create_time, 'amount' => $amount];
        header('Content-Type: application/json');
        echo json_encode($data);

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Payment not approved']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error executing payment: ' . $e->getMessage()]);
}