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
$paymentID = $_POST['paymentID'];
$payerID = $_POST['payerID'];
$paymentAmount = $_POST['paymentAmount'];

// Get the payment details from PayPal
try {
    $payment = Payment::get($paymentID, $apiContext);
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}

// Execute the payment and process the order
$execution = new PaymentExecution();
$execution->setPayerId($payerID);

$transaction = $payment->getTransactions()[0];
$amount = $transaction->getAmount();
if ($amount->getTotal() != $paymentAmount) {
    die('Payment amount mismatch');
}

try {
    $result = $payment->execute($execution, $apiContext);

    if ($result->getState() == 'approved') {
        // TODO: Process the order and update the database
        //echo 'Payment completed successfully!';
       	$state = $result->getState(); // Payment state (approved, failed, etc.)
        $id = $result->getId(); // Payment ID
        $create_time = $result->getCreateTime(); // Payment creation time
        $intent = $result->getIntent(); // Payment intent (sale, authorize, etc.)
        $transactions = $result->getTransactions(); // Array of transactions


        // Accessing the first transaction's details
        $transaction = $transactions[0];
        $transaction_id = $transaction->getId(); // Transaction ID
        $amount = $transaction->getAmount(); // Transaction amount
        $currency = $amount->getCurrency(); // Transaction currency
        $total = $amount->getTotal(); // Transaction total amount
        
        // header('Location: https://drdietapp.com/paypal/successpay.php?transactionid='.$transaction_id.'&createtime='.$create_time.'&amount='.$amount);
         
         
        $data = ['transactionId' => $transaction_id, 'createTime' => $create_time, 'amount' => $amount];
        header('Content-Type: application/json');
        echo json_encode($data);

    } else {
        echo 'Payment not completed';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}