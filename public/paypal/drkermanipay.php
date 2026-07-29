<?php

$amount = $_GET["amount"];
$createtime = $_GET['createtime'];
$transactionid = $_GET['transactionid'];

echo '<div class="container">';
echo '<div class="text-center d-flex flex-column align-center">';
echo '<div class="mb-8">';
echo '<img src="./assets/img/final-pay.svg" alt="Success">';
echo '</div>';
echo '<h3 class="text-center">';
echo 'تم الدفع بنجاح';      
echo '</h3>';
echo '<h3 class="text-center mt-4">';
echo 'مبلغ : '. $amount;
echo '</h3>';
echo '<h3 class="text-center mt-4">';
echo 'تاریخ : '.$createtime;
echo '</h3>';
echo '<h3 class="text-center mt-4">';
echo 'رقم التحویل : '. $transactionid;
echo '</h3>';
echo '<div class="text-center mt-8">';
echo '<img src="./assets/img/full-logo.svg" alt="Logo">';
echo '</div>';
echo '</div>';
echo '</div>';

?>