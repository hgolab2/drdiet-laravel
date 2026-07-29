<?php
$price = $_POST['price'];
$mobile = $_POST['mobile'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$address = $_POST['address'];
$city = $_POST['city'];
$state = $_POST['state'];
$postcode = $_POST['postcode'];
$country = $_POST['country'];
$email = $_POST['email'];

$productName = $_POST['productname'];
$slug = date('YmdHis').substr(microtime(true), 0,9);




$data =  array(
    "api" => "70e2a409-1a56-457b-a5ef-236c64083d4c",
    "amount" => $price,
    "callback" => "https://drkermanidiet.com/pay/paypal.php",
    "mobile" => $mobile,
    "order" => array(
        "total" => $price,
        "billing" => array(
            "first_name" => $first_name,
            "last_name" => $last_name,
            "address_1" => $address,
            "city" => $city,
            "state" => $state,
            "postcode" => $postcode,
            "country" => $country,
            "email" => $email,
            "phone" => $mobile
        ),
        "products" => array(
            array(
                "id" => "57",
                "name" => $productName,
                "slug" => $slug,
                "price" => $price,
                "qty" => 1,
              	"image" => "https://drkermanidiet.com/pay/assets/img/cover_profile.jpg"
            )
        )
    )
);


//$url = 'https://sandbox.shepa.com/api/v1/token';
$url = 'https://merchant.shepa.com/api/v1/token';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));



curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Content-Type: application/json',
]);
$res = curl_exec($ch);
curl_close($ch);


$data = json_decode($res);


session_start(); 
$_SESSION['token'] = $data->result->token;
$_SESSION['dramount'] = $price;
$_SESSION['product_name'] = $productName;

$_SESSION['mobile'] = $mobile;
$_SESSION['first_name'] = $first_name;
$_SESSION['last_name'] = $last_name;




header('Content-type: application/json');
echo json_encode( $data );

//return $res;