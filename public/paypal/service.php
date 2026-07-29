<?php
$amount = isset($_GET['amount']) && is_numeric($_GET['amount']) ? (int)$_GET['amount'] : 250;
// $description = $amount <= 100 ? 'النظام غذائي' : 'النظام غذائي و برنامج رياضي';
// $timeperiod = $amount <= 100 ? '3 اشهر' : '6 اشهر';





if($amount <= 100 ){
    $description = 'النظام غذائي';
    $timeperiod = '3 اشهر';
}else{
    $description = 'النظام غذائي و برنامج رياضي';
    $timeperiod = '6 اشهر';
}

$productDescription = 'حمية دايت كلوب ';

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment</title>
    <script src="https://www.paypal.com/sdk/js?client-id=AW5ThXjTyCwNX6DbOg8JLJTaoRN4n-SniYxXgRbu-TN-a1HHqI29czcna8OmsoCc-e2BJ_TeeY0IlxPS"></script>
</head>
<body>
<div class="container p_container">
    <div class="d-flex flex-center mb-4">
        <img src="./assets/img/full-logo.svg" alt="Logo" />
    </div>
    <div class="list-wrapper">
        <div class="flex jc-between mb-3">
        <span class="item-txt">
          تاریخ:
        </span>
            <span id="today_date" class="item-txt">
          ---- -- --
        </span>
        </div>
        <div class="flex jc-between mb-3">
        <span class="item-txt">
          المنتج:
        </span>
            <span class="item-txt">
          <?php echo $productDescription; ?>
        </span>
        </div>
       
        <div class="flex jc-between">
        <span class="item-txt">
          المبلغ:
        </span>
            <span class="item-txt">
            <?php echo $amount.' USD'; ?> 
        </span>
        </div>

    </div>
    <div id="paypal-button-container" class="text-center" style="max-width: 400px; margin: 1.5rem auto"></div>
</div>
<script>
    // Render the PayPal button
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?php echo $amount; ?>' // Set the payment amount here
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                
                // Send the payment details to the server for processing
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'process-payment-11.php');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        
                        var response = JSON.parse(xhr.responseText);
                        window.location.href = 'https://drdietapp.com/paypal/successpay.php?transactionid='+response.transactionId+'&createtime='+response.createTime+'&amount='+response.amount; 
                    } else {
                        // alert('Payment failed, please try again!');
                        // window.location.href = 'https://drdietapp.com/paypal/successpay.php?transactionid=' + "<?php echo date("YmdHis").rand(0,10000) ?>" +  '&createtime=' + '<?php date("Y-m-d") ?>' + '&amount=' + "<?php echo $amount; ?>";
                        window.location.href = 'https://drdietapp.com/paypal/successpay.php?transactionid=' + data.orderID + '&createtime=' + "<?php date("Y-m-d") ?>" + '&amount=' + details.purchase_units[0].amount.value;
                    }
                };
                xhr.send('paymentID=' + data.orderID + '&payerID=' + data.payerID + '&paymentAmount=' + details.purchase_units[0].amount.value);
            });
        }
    }).render('#paypal-button-container');
    function setDate() {
        const date = new Date();
        let currentDay= String(date.getDate()).padStart(2, '0');
        let currentMonth = String(date.getMonth()+1).padStart(2,"0");
        let currentYear = date.getFullYear();

        let currentDate = `${currentDay}-${currentMonth}-${currentYear}`;
        console.log(currentDate);
        let todayDate = document.getElementById("today_date")
        todayDate.innerText = currentDate
    }
    setDate()


</script>
</body>
</html>
<style>

    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: normal;
        src: url('./assets/fonts/Cairo-Regular.woff') format('woff');
    }
    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: 100;
        src: url('./assets/fonts/Cairo-ExtraLight.woff') format('woff');
    }
    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: 300;
        src: url('./assets/fonts/Cairo-Light.woff') format('woff');
    }
    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: 600;
        src: url('./assets/fonts/Cairo-SemiBold.woff') format('woff');
    }
    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: 700;
        src: url('./assets/fonts/Cairo-Bold.woff') format('woff');
    }
    @font-face {
        font-family: 'cairo';
        font-style: normal;
        font-weight: 900;
        src: url('./assets/fonts/Cairo-Black.woff') format('woff');
    }
    html {
        direction: rtl;
    }
    body {
        height: 100%;
        direction: rtl;
        font-family: cairo;
        font-weight:normal;
        font-style:normal;
        font-size:13px;
        line-height:27px;
        background: white;
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: cairo;
    }
    button {
        border: none;
        outline: none;
        cursor: pointer;
    }
    .container {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto
    }

    @media (min-width: 576px) {
        .container {
            max-width: 540px
        }
    }

    @media (min-width: 768px) {
        .container {
            max-width: 720px
        }
    }

    @media (min-width: 992px) {
        .container {
            max-width: 960px
        }
    }

    @media (min-width: 1200px) {
        .container {
            max-width: 1140px
        }
    }

    .flex {
        display: -webkit-box !important;
        display: -ms-flexbox !important;
        display: flex !important
    }
    .flex-center {
        display: -webkit-box !important;
        display: -ms-flexbox !important;
        display: flex !important;
        justify-content: center;
    }
    .flex-middle {
        display: -webkit-box !important;
        display: -ms-flexbox !important;
        display: flex !important;
        align-items: center;
    }
    .content-center {
        display: -webkit-box !important;
        display: -ms-flexbox !important;
        display: flex !important;
        justify-content: center;
        align-items: center;
    }
    .text-center{text-align: center}
    .jc-between{justify-content: space-between}.jc-start{justify-content: start}.ai-center{align-items: center}
    .mt-0{margin-top: 0}.mt-1{margin-top: 0.25rem}.mt-2{margin-top: 0.5rem}.mt-3{margin-top: 0.75rem}.mt-4{margin-top: 1rem}.mt-5{margin-top: 1.5rem}                                
    .mb-0{margin-bottom: 0}.mb-1{margin-bottom: 0.25rem}.mb-2{margin-bottom: 0.5rem}.mb-3{margin-bottom: 0.75rem}.mb-4{margin-bottom: 1rem}.mb-5{margin-bottom: 1.5rem}
    .ml-0{margin-left: 0}.ml-1{margin-left: 0.25rem}.ml-2{margin-left: 0.5rem}.ml-3{margin-left: 0.75rem}.ml-4{margin-left: 1rem}.ml-5{margin-left: 1.5rem}
    .mr-0{margin-right: 0}.mr-1{margin-right: 0.25rem}.mr-2{margin-right: 0.5rem}.mr-3{margin-right: 0.75rem}.mr-4{margin-right: 1rem}.mr-5{margin-right: 1.5rem}                                   
    .relative {
        position: relative;
    }
    .p_container {
        padding-top: 1.5rem;
    }
    .list-wrapper {
        background-color: #E9EDF2;
        border-radius: 31px;
        max-width: 400px;
        margin: auto;
        padding: 1.5rem 1rem;
    }
    .item-txt {
        color: #7f7f7f;
        font-size: 1.1rem;
    }
    .divider {
        height: 1px;
        width: 80%;
        background-color: #999999;
        margin: 1rem auto;
    }
    .discount {
        border-radius: 22px;
        background: white;
        display: flex;
        justify-content: space-between;
        padding: 8px;
    }
    .discount-input {
        border: none;
        background-color: transparent;
        height: 43px;
        padding-right: 10px;
    }
    .discount-input:focus {
        border: none;
        outline: none;
    }
    .apply-btn {
        background-color: #6190ff;
        color: #fff;
        border-radius: 11px;
        height: 43px;
        width: 98px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .p-banner {
        height: 68px;
        border-radius: 31px;
        padding: 0 28px;
        max-width: 400px;
        display: flex;
        justify-content: space-between;
        margin: 1rem auto;
    }
    .p-banner.paypal {
        background-color: #ffc439;
    }
    .paypal-button-row {
        background: blue!important;
    }
    .p-banner.paypal .title {
        color: #003087;
        font-weight: bold;
        font-size: 1.6rem;
        display: flex;
        align-items: center;
    }
    .p-banner.visa {
        background: #6190ff;
    }
    .p-banner.visa .title {
        color: white;
        font-weight: bold;
        font-size: 1.6rem;
        display: flex;
        align-items: center;
    }
</style>