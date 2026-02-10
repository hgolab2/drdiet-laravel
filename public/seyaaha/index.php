<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>المركز الدولي للتغذية العلاجية - دايت کلوب</title>
    <link href="/seyaaha/css/font-face.css" rel="stylesheet" type="text/css">
    <link href="/seyaaha/css/style.css" rel="stylesheet" type="text/css">
    <meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=yes" name="viewport">
    <script src="https://www.paypal.com/sdk/js?client-id=AW5ThXjTyCwNX6DbOg8JLJTaoRN4n-SniYxXgRbu-TN-a1HHqI29czcna8OmsoCc-e2BJ_TeeY0IlxPS"></script>
</head>


<?php
$basePrcide = (int)@$_GET['price'];
if($basePrcide < 10 ){
    $basePrcide = 10;
}
// $price =  $basePrcide*2.6;
$price =  $basePrcide*0.38;

$product_name = 'اشتراك حمية دكتور دايت';
$dest_site = 'https://sub1.seyaaha.com/payment/';

$product_details = array();
$p_detail				= array();
$p_detail['name'] 		= $product_name; //needs filling
$p_detail['image'] 		= ''; //needs filling
$p_detail['price'] 		= $price; //needs filling
$p_detail['quantity'] 	= 1; //needs filling
$product_details[] 		= $p_detail;

$orderId = date('YmdHis').rand(0,999);
$order_received_url = 'https://api.di3t-club.com/seyaaha/seyaahatransaction.php'; //needs filling

?>
<body class="flex flex-center flex-col orange siyaha-theme">
<div class="payment-form" style="padding-bottom: 0px;">
    <div class="header flex flex-center">
        <img alt="logo" class="logo" src="https://di3t-club.com/logo.svg">
        <div class="product-card flex">
            <div class="icon flex flex-center"></div>
            <div class="content flex">
                <span class="title text-gray">المنتج: نظام غذائي</span>
                <div class="flex flex-center">
                    <b class="price"><?php echo $basePrcide; ?> USD</b>
                    <!--span class="date text-gray">/  <?php echo $price ?> OMR</span-->
                </div>
            </div>
        </div>
    </div>

    <form class="pay-section form"  method="POST" action="<?php echo $dest_site; ?>">

        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>" >
        <input type="hidden" name="product_details" value='<?php echo json_encode( $product_details ); ?>'>
        <input type="hidden" name="order_received_url" value="<?php echo $order_received_url; ?>">
        <input type="hidden" name="price"  value="<?php echo $price; ?>" />


        <div class="form-field flex">
            <div class="select-field">
                <select class="field" hidden id="countries" name="country"></select>
                <div class="lang-select">
                    <button class="btn-select" type="button" value=""></button>
                    <div class="select-box">
                        <ul id="countries__list"></ul>
                    </div>
                </div>
            </div>
            <input required type="number" dir="ltr" placeholder="رقم الهاتف" name="phoneNumber" />
            <input required type="hidden" dir="ltr" placeholder="رقم الهاتف" name="billing_phone" />
        </div>
        <div class="not-valid" id="phoneNumber"></div>
        <div class="form-field">
            <input required type="text" placeholder="الاسم" name="order_billing_first_name" />
        </div>
        <div class="not-valid" id="Name"></div>
        <div class="form-field">
            <input required type="email" placeholder="البريد الإلكتروني" name="billing_email" />
        </div>
        <div class="not-valid" id="Email"></div>
        <button id="submitBtn" type="submit" class="flex flex-center btn btn-red w-full">
            <img src="/seyaaha/images/bank-muscat.png" alt="bank muscat" width="102">
        </button>
    </form>
    <img alt="" src="/seyaaha/images/pay.png" style="width: 90%;">
</div>
<ul class="options flex flex-center" dir="rtl">
    <li class="flex flex-col">
        <img alt="shield" src="images/icons/shield.svg">
        <div class="content flex flex-col flex-center">
            <span class="title text-brown">دفع آمن</span>
            <p class="description text-half-dark">
                يتم عبر بوابة آمنة،
                <br/>
                ومعلوماتك لا تُحفظ.
            </p>
        </div>
    </li>
    <li class="flex flex-col">
        <img alt="headphone" src="images/icons/headphone.svg">
        <div class="content flex flex-col flex-center">
            <span class="title text-brown">دعم 24/7</span>
            <p class="description text-half-dark">
                فريقنا متاح دائمًا
                <br/>
                لخدمتك.
            </p>
        </div>
    </li>
    <li class="flex flex-col">
        <img alt="lock" src="images/icons/lock.svg">
        <div class="content flex flex-col flex-center">
            <span class="title text-brown">
                خصوصية محمية
            </span>
            <p class="description text-half-dark">
                بياناتك مؤمنة و
                <br/>
                مشفرة بالكامل.
            </p>
        </div>
    </li>
    <li class="flex flex-col">
        <img alt="repeat" src="images/icons/repeat.svg">
        <div class="content flex flex-col flex-center">
            <span class="title text-brown">
                بدون اشتراكات
                <br/>
                تلقائية
            </span>
            <p class="description text-half-dark">
                دفع لمرة واحدة فقط.
            </p>
        </div>
    </li>
</ul>
<div class="flex flex-center w-full" dir="rtl">
    <div class="protection-link">
        <input id="protection" type="checkbox"/>
        <label class="text-half-dark flex flex-center" for="protection">
            كيف تضمن أمان عملية الدفع الخاصة بك؟
            <img class="chevron" src="/seyaaha/images/icons/chevron-down.svg" alt="chevron down" width="20">
        </label>
        <div class="description flex-col">
            <p class="text-half-dark">- شهادة SSL (اتصال آمن): يجب استخدام بروتوكول HTTPS لتأمين الاتصالات بينك وبين
                الخادم، ويُعد إلزاميًا
                لصفحات الدفع.</p>
            <p class="text-half-dark">- شارات الثقة: تُضفي الشارات الأمنية على صفحة الدفع طمأنينة بأن عملية الدفع
                آمنة.</p>
            <p class="text-half-dark">- معلومات الشراء: تأكد من عرض تفاصيل الطلب كالمنتج والسعر قبل التوجه إلى بوابة
                الدفع الإلكتروني.</p>
            <p class="text-half-dark">- زر CTA مرئي: يجب أن يكون الزر المؤدي إلى بوابة الدفع الإلكتروني واضحًا ومختصرًا
                مثل "الدفع عبر بوابة
                الدفع الإلكتروني.</p>
        </div>
    </div>
</div>

<script src="jquery-3.7.1.min.js"></script>
<script>

    async function setCountry() {
        try {
            const res = await fetch("https://api.ipregistry.co/?key=tryout", {
                method: 'GET'
            }).then((data)=>data.json())
            const userCode = res.location.country.calling_code;
            for (const countryCode of countryCodes) {
                if (countryCode.code === userCode) {
                    const codeS = countryCode.code
                    const selectedCountry = countryCodes.find(({code})=>code.includes(codeS))
                    console.log(selectedCountry);
                    const value = selectedCountry.en_name;
                    const item = '<li><img src="' + countryFlag(selectedCountry.iso_code) + '" alt="" /><span>' + selectedCountry.name + '</span></li>';
                    $('.btn-select').html(item);
                    $('.btn-select').attr('value', value);
                    $('#countries').val(value)
                }
            }
        } catch (e) {
            console.log("ip error:", e);
        }
    }
    const countryCodes = [
        {id: '3', name: 'الجزائر', en_name: 'Algeria', code: '213', iso_code: 'DZ', iso3: 'DZA'},
        {id: '13', name: 'أستراليا', en_name: 'Australia', code: '61', iso_code: 'AU', iso3: 'AUS'},
        {id: '17', name: 'بحرین', en_name: 'Bahrain', code: '973', iso_code: 'BH', iso3: 'BHR'},
        {id: '38', name: 'كندا', en_name: 'Canada', code: '1', iso_code: 'CA', iso3: 'CAN'},
        {id: '58', name: 'الدنمارك', en_name: 'Denmark', code: '45', iso_code: 'DK', iso3: 'DNK'},
        {id: '63', name: 'مصر', en_name: 'Egypt', code: '20', iso_code: 'EG', iso3: 'EGY'},
        {id: '73', name: 'فرنسا', en_name: 'France', code: '33', iso_code: 'FR', iso3: 'FRA'},
        {id: '80', name: 'ألمانيا', en_name: 'Germany', code: '49', iso_code: 'DE', iso3: 'DEU'},
        {id: '101', name: 'إيران', en_name: 'Iran', code: '98', iso_code: 'IR', iso3: 'IRN'},
        {id: '102', name: 'العراق', en_name: 'Iraq', code: '964', iso_code: 'IQ', iso3: 'IRQ'},
        {id: '104', name: 'فلسطين المحتله', en_name: 'Occupied Palestine', code: '972', iso_code: 'PS', iso3: null},
        {id: '108', name: 'الأردن', en_name: 'Jordan', code: '962', iso_code: 'JO', iso3: 'JOR'},
        {id: '114', name: 'کویت', en_name: 'Kuwait', code: '965', iso_code: 'KW', iso3: 'KWT'},
        {id: '118', name: 'لبنان', en_name: 'Lebanon', code: '961', iso_code: 'LB', iso3: 'LBN'},
        {id: '121', name: 'لیبی', en_name: 'Libya', code: '218', iso_code: 'LY', iso3: 'LBY'},
        {id: '144', name: 'المغرب', en_name: 'Morocco', code: '212', iso_code: 'MA', iso3: 'MAR'},
        {id: '161', name: 'عمان', en_name: 'Oman', code: '968', iso_code: 'OM', iso3: 'OMN'},
        {id: '164', name: 'فلسطین', en_name: 'Palestine', code: '970', iso_code: 'PS', iso3: null},
        {id: '174', name: 'قطر', en_name: 'Qatar', code: '974', iso_code: 'QA', iso3: 'QAT'},
        {id: '187', name: 'السعودیة', en_name: 'Saudi Arabia', code: '966', iso_code: 'SA', iso3: 'SAU'},
        {id: '201', name: 'سودان', en_name: 'Sudan', code: '249', iso_code: 'SD', iso3: 'SDN'},
        {id: '205', name: 'السويد', en_name: 'Sweden', code: '46', iso_code: 'SE', iso3: 'SWE'},
        {id: '207', name: 'سوریه', en_name: 'Syria', code: '963', iso_code: 'SY', iso3: 'SYR'},
        {id: '217', name: 'تونس', en_name: 'Tunisia', code: '216', iso_code: 'TN', iso3: 'TUN'},
        {id: '218', name: 'ترکیا', en_name: 'Turkey', code: '90', iso_code: 'TR', iso3: 'TUR'},
        {id: '224', name: 'الامارات', en_name: 'Emirates', code: '971', iso_code: 'AE', iso3: 'ARE'},
        {id: '225', name: 'بریطانیا', en_name: 'Britain', code: '44', iso_code: 'GB', iso3: 'GBR'},
        {
            id: '226',
            name: 'الولايات المتحدة الأمريكية',
            en_name: 'United States of America',
            code: '1',
            iso_code: 'US',
            iso3: 'USA'
        },
        {id: '237', name: 'الیمن', en_name: 'Yemen', code: '967', iso_code: 'YE', iso3: 'YEM'}
    ]

    const countryFlag = (code) =>
        code ? `https://flagcdn.com/h40/${code.toLowerCase()}.png` : "";
    $(document).ready(async () => {
        countryCodes.forEach((item) => {
            $('#countries').append(`
            <option data-thumbnail="${countryFlag(item.iso_code)}" value="${item.en_name}">
            ${item.name}
            </option>
            `)

        })
        let langArray = [];
        $('#countries option').each(function () {
            const img = $(this).attr("data-thumbnail");
            const text = this.innerText;
            const value = $(this).val();
            const item = '<li><img src="' + img + '" alt="" value="' + value + '"/><span>' + text + '</span></li>';
            langArray.push(item);
        })

        $('#countries__list').html(langArray);
        $('.btn-select').html(langArray[0]);
        $('#countries__list li').click(function (e) {
            const img = $(this).find('img').attr("src");
            const value = $(this).find('img').attr('value');
            const text = this.innerText;
            const item = '<li><img src="' + img + '" alt="" /><span>' + text + '</span></li>';
            $('.btn-select').html(item);
            $('.btn-select').attr('value', value);
            $('#countries').val(value)
            $(".select-box").toggle();
            e.stopPropagation()
        });
        $(".btn-select").click(function (e) {
            $(".select-box").toggle();
            e.stopPropagation()
        });

        $(window).click(function (){
            $(".select-box").hide();

        })
        await setCountry()


        $('[name="phoneNumber"]').on('input',(event)=>{
            const phoneNumber = event.target.value
            const countryName = $('[name="country"]').val()
            const currentCountry = countryCodes.find(({en_name})=>en_name === countryName)
            $("[name='billing_phone']").val(currentCountry.code + phoneNumber)
        })


    })
</script>
<script>

    function isEmail(email) {
        const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
    function stopSubmit(event) {
        event.stopPropagation()
        event.preventDefault()
    }

    $('#submitBtn').click((btnEvent) => {
        $('.form input,.form select').each((_, e) => {
            const errorElement = $('#' + $(e).attr('name'))
            if ($(e).attr('name') === 'email' && !isEmail($(e).val())) {
                $(errorElement).attr('data-error', 'البريد الإلكتروني مطلوب')
                stopSubmit(btnEvent)
            } else if ($(e).val() === '' || Number($(e).val()) < 1) {
                $(errorElement).attr('data-error', 'يجب عليك ملء هذا الحقل')
                stopSubmit(btnEvent)
            } else {
                $(errorElement).attr('data-error', '')
            }
        })
    })
</script>

<script>
    const bodyElement = document.getElementsByTagName('body')[0]
    const windowHeight = window.innerHeight

    bodyElement.style.height = windowHeight + 'px'
</script>

<script>
    // Render the PayPal button
    paypal.Buttons({
        createOrder: function (data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?php echo $price; ?>' // Set the payment amount here
                    }
                }]
            });
        },
        onApprove: function (data, actions) {
            return actions.order.capture().then(function (details) {

                // Send the payment details to the server for processing
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'process-payment-11.php');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {

                        var response = JSON.parse(xhr.responseText);

                        alert('تم عملية دفع بنجاح\r\n' + 'Amount paid : ' + response.amount + '\r\n' + 'Transaction Number: ' + response.transactionId + '\r\n\r\n' + response.createTime);
                        window.location.href = 'https://drdietapp.com/paypal/successpay.php?transactionid=' + response.transactionId + '&createtime=' + response.createTime + '&amount=' + response.amount;
                    } else {
                        //console.log(xhr.responseText);
                        alert('Payment failed, please try again!');
                    }
                };
                xhr.send('paymentID=' + data.orderID + '&payerID=' + data.payerID + '&paymentAmount=' + details.purchase_units[0].amount.value);
            });
        }
    }).render('#paypal-button-container');

    function setDate() {
        const date = new Date();
        let currentDay = String(date.getDate()).padStart(2, '0');
        let currentMonth = String(date.getMonth() + 1).padStart(2, "0");
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
