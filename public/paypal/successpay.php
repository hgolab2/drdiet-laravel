<?php
$amount = $_GET["amount"];
$createtime = $_GET['createtime'];
$transactionid = $_GET['transactionid'];
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>دفع ناجح</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .text-center{
            text-align: center;
        }
        body{
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .payment-form .container {
            padding: 15px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 376px;
            background-color: #F4F4F4;
            border-radius: 20px;
        }
        .payment-form .image{
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .payment-form .footer{
            margin-top: 20px;
        }
        .payment-form .text{
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="payment-form" dir="rtl">
    <div class="container">
        <div class="text-center d-flex flex-column align-center">
            <div class="image">
                <img src="./assets/img/final-pay.svg" alt="Success">
            </div>
            <h3 class="text-center">
                تم الدفع بنجاح
            </h3>
            <h3 class="text-center text">
                مبلغ : <?php echo $amount; ?>
            </h3>
            <h3 class="text-center text">
                تاریخ : <?php echo date("Y-m-d"); ?>
            </h3>
            <h3 class="text-center text">
                رقم التحویل : <?php echo $transactionid; ?>
            </h3>
            <div class="text-center footer">
                <img src="./assets/img/full-logo.svg" alt="Logo">
            </div>
        </div>
    </div>
</div>
<script>
    const bodyElement = document.getElementsByTagName('body')[0]
    const windowHeight = window.innerHeight

    bodyElement.style.height = windowHeight + 'px'
</script>
</body>
</html>

