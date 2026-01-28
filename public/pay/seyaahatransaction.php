<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=yes" name="viewport">
    <link href="css/font-face.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <title>المركز الدولي للتغذية العلاجية - دايت کلوب</title>
    <style>
        .container{
            position: fixed;
            margin: auto;
        }
        .card{
            width: max-content;
            background-color: #F4F4F4;
            border-radius: 20px;
            padding: 60px 37px 20px 37px;
            min-width: 370px;
        }
        .card .icon{
            position: absolute;
            left: 0;
            right: 0;
            top: -38px;
            margin: auto;
        }
        .item{
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .btn.btn-blue{
            width: 300px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            gap: 15px;
        }
    </style>
</head>
<body class="flex flex-center" dir="rtl">
<?php
if( isset( $_POST['order_id'] ) and isset( $_POST['order_status'] ) ){
    if( $_POST['order_status'] == 'processing' or $_POST['order_status'] == 'completed' ) {
    ?>
<div class="container flex flex-center flex-col">
    <div class="card flex flex-col flex-center">
        <img class="icon" src="images/pay/success.png" alt="success" width="76" height="75">
        <b>الدفع ناجح</b>
        <div class="flex item w-full">
            <span class="text-light-gray">رمز التتبع</span>
            <b>
                <?php echo $_POST['order_id']; ?>
            </b>
        </div>
        <div class="flex item w-full">
            <span class="text-light-gray">التاريخ</span>
            <b>
                <?php echo date("Y-m-d") ?>
            </b>
        </div>
        <div class="flex item w-full">
            <span class="text-light-gray">طريقة الدفع</span>
            <img src="images/banks/muscat.png" alt="success" width="150" height="49">
        </div>
    </div>
    <?php
    } else{
?>
        <div class="container flex flex-center flex-col">
            <div class="card flex flex-col flex-center">
                <img alt="failed" class="icon" height="75" src="images/pay/failed.png" width="76">
                <b>عملية الدفع فشلت</b>
                <div class="flex item w-full">
                    <p class="text-center">
                        واجهت عملية الدفع مشكلة، يرجى التسجيل
                        <br/>
                        مرة أخرى أو الاتصال بالدعم لدينا
                    </p>
                </div>
            </div>
            <!--<div class="flex flex-center footer">-->
            <!--    <a class="btn btn-red" href="https://drdietapp.com/seyaaha">-->
            <!--        حاول ثانية-->
            <!--    </a>-->
            <!--</div>-->
        </div>


<?php
    }
}
?>
</div>
<script src="jquery-3.7.1.min.js"></script>
<script>
    $('body').css({
        height:window.innerHeight
    })
</script>
</body>
</html>