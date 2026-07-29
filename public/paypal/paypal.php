<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Payment</title>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"
    integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous">
    </script>




  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>



<?php
  
  session_start(); 
if ((!empty($_GET["status"]) && $_GET["status"] == "success") || (!empty($_GET["status"]) && $_GET["status"] == "OK")) {
    $api = "70e2a409-1a56-457b-a5ef-236c64083d4c";
    $token = @$_SESSION['token'];
    $amount = @$_SESSION['dramount'];
    $product_name = @$_SESSION['product_name'];
  	
  	$mobile = @$_SESSION['mobile'];
  	$first_name = @$_SESSION['first_name'];
  	$last_name = @$_SESSION['last_name'];
    $getToken = $_GET["token"];
  	
  
  	
  
  

    $params =  array(
        "api" => "70e2a409-1a56-457b-a5ef-236c64083d4c",
        "token" => $token,        
        "amount" => $amount,
    );
    $url = 'https://merchant.shepa.com/api/v1/verify';
  	//$url = 'https://sandbox.shepa.com/api/v1/verify';
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $verifyResult = json_decode($res);
    if (!empty($verifyResult->success)) {
        ?>
        <body>
            <div class="container p_container">
                <div class="d-flex flex-center mb-4">
                    <img src="./assets/img/full-logo.svg" alt="Logo" />
                </div>

                <div class="list-wrapper">
                    


				<div class="d-flex flex-center mb-4">                	
                   <img src="./assets/img/success_tick2.png">
            	</div>
                  
                 <div class="d-flex flex-center mb-4">
                	<span class="item-txt" style="color:green;font-weight: bold;">تم الدفع بنجاح</span>                    
            	</div> 					

					<div class="flex jc-between mb-3">		
                        <span class="item-txt">
                            رقم الهاتف :
                        </span>
                        <span id="today_date" class="item-txt">
                            <?php echo $mobile ?>
                        </span>
                    </div>                  
                  
                  
                  
 					
                 
                  <div class="flex jc-between mb-3">		
                        <span class="item-txt">
                            الاسم :
                        </span>
                        <span id="today_date" class="item-txt">
                            <?php echo $first_name.'  '.$last_name ?>
                        </span>
                    </div>   
                 
                  
                  

                    <div class="flex jc-between mb-3">
                        <span class="item-txt">
                            رقم العملية:
                        </span>
                        <span id="today_date" class="item-txt">
                            <?php echo substr($token, 0,13) ?>
                        </span>
                    </div>


                    <div class="flex jc-between mb-3">
                        <span class="item-txt">
                            تاریخ:
                        </span>
                        <span id="today_date" class="item-txt">
                            <?php echo date('Y-m-d') ?>
                        </span>
                    </div>

                    <div class="flex jc-between mb-3">
                        <span class="item-txt">
                            المنتج:
                        </span>
                        <span class="item-txt">
                        <?php echo $product_name; ?>
                        </span>
                    </div>
                  
                    <div class="flex jc-between mb-3">
                        <span class="item-txt">
                            المبلغ المدفوع:
                        </span>
                        <span class="item-txt">
                        <?php echo $amount; ?>
                        </span>
                    </div>
                  
                  



                </div>


            </div>
        </body>    
    
        <?php

    } else {
            ?>
        <body>
        <div class="container p_container">
            <div class="d-flex flex-center mb-4">
                <img src="./assets/img/full-logo.svg" alt="Logo" />
            </div>

            <div class="list-wrapper">
                


                <div class="flex jc-between mb-3">
                    <span class="item-txt">
                        خطا فی الدفع
                    </span>          
                </div>

                <div class="flex jc-between mb-3">
                    <span class="item-txt">
                        تاریخ:
                    </span>
                    <span id="today_date" class="item-txt">
                        <?php echo date('Y-m-d') ?>
                    </span>
                </div>


            </div>


        </div>
    </body>    
<?php
    }

} else if(!empty($_GET["status"]) && $_GET["status"] == "failed"){
    ?>
<body>
        <div class="container p_container">
            <div class="d-flex flex-center mb-4">
                <img src="./assets/img/full-logo.svg" alt="Logo" />
            </div>

            <div class="list-wrapper">
                

   
                  <div class="d-flex flex-center mb-4">
                	<span class="item-txt" style="color:red">
                        خطا فی الدفع
                    </span> 
            	</div>
                  
                  
                  
               

                <div class="flex jc-between mb-3">
                    <span class="item-txt">
                        تاریخ:
                    </span>
                    <span id="today_date" class="item-txt">
                        <?php echo date('Y-m-d') ?>
                    </span>
                </div>


            </div>


        </div>
    </body>    
    <?php
}



?>





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
    font-weight: normal;
    font-style: normal;
    font-size: 13px;
    line-height: 27px;
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

  .text-center {
    text-align: center
  }

  .jc-between {
    justify-content: space-between
  }

  .jc-start {
    justify-content: start
  }

  .ai-center {
    align-items: center
  }

  .mt-0 {
    margin-top: 0
  }

  .mt-1 {
    margin-top: 0.25rem
  }

  .mt-2 {
    margin-top: 0.5rem
  }

  .mt-3 {
    margin-top: 0.75rem
  }

  .mt-4 {
    margin-top: 1rem
  }

  .mt-5 {
    margin-top: 1.5rem
  }

  .mb-0 {
    margin-bottom: 0
  }

  .mb-1 {
    margin-bottom: 0.25rem
  }

  .mb-2 {
    margin-bottom: 0.5rem
  }

  .mb-3 {
    margin-bottom: 0.75rem
  }

  .mb-4 {
    margin-bottom: 1rem
  }

  .mb-5 {
    margin-bottom: 1.5rem
  }

  .ml-0 {
    margin-left: 0
  }

  .ml-1 {
    margin-left: 0.25rem
  }

  .ml-2 {
    margin-left: 0.5rem
  }

  .ml-3 {
    margin-left: 0.75rem
  }

  .ml-4 {
    margin-left: 1rem
  }

  .ml-5 {
    margin-left: 1.5rem
  }

  .mr-0 {
    margin-right: 0
  }

  .mr-1 {
    margin-right: 0.25rem
  }

  .mr-2 {
    margin-right: 0.5rem
  }

  .mr-3 {
    margin-right: 0.75rem
  }

  .mr-4 {
    margin-right: 1rem
  }

  .mr-5 {
    margin-right: 1.5rem
  }

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
    background: blue !important;
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


  input[type="text"].bmi-input,
  input[type="number"].bmi-input {
    width: 100%;
    height: 3rem;
    background: #FFFFFF;
    border-radius: 15px;
    padding: 5px 20px;
    text-align: right;
    margin-bottom: 12px;

    -moz-appearance: textfield
  }

  input[type="text"].bmi-input::-webkit-outer-spin-button,
  input[type="number"].bmi-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  input[type="text"].bmi-input::-webkit-inner-spin-button,
  input[type="number"].bmi-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  input[type="text"].bmi-input.error,
  input[type="number"].bmi-input.error {
    color: #FF3B3B;
    box-shadow: inset 0 0 12px rgba(255, 59, 59, 0.3);
  }

  .submit-btn {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    width: 90%;
    height: 45px;
    margin: auto;
    margin-top: 16px;
    padding: 0.5rem;
    text-align: center;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    background-color: #5CB85C;
    border-radius: 14px;
    cursor: pointer;
    z-index: 10;
  }

  #countrySelector {
    width: 100%;
  }

  .select2-container {
    margin: 8px auto;
  }
</style>