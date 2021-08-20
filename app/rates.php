<?php
$input = file_get_contents('php://input');

// // parse the request
$rates = json_decode($input, true);

// // log the array format for easier interpreting
// file_put_contents('debug', print_r($rates, true));

//*********************Get picup and dropoff address to calculate the shipping*************************//
if ($rates['rate']['origin']['address2'] != "")
{
    $p_address_2 = $rates['rate']['origin']['address2'] . ', ';
}
else
{
    $p_address_2 = "";
}
$pickup_address = $rates['rate']['origin']['address1'] . ', ' . $p_address_2 . $rates['rate']['origin']['city'] . ' ' . $rates['rate']['origin']['province'] . ', ' . $rates['rate']['origin']['postal_code'] . ', ' . $rates['rate']['origin']['country'];

if ($rates['rate']['destination']['address2'] != "")
{
    $d_address_2 = $rates['rate']['destination']['address2'] . ', ';
}
else
{
    $d_address_2 = "";
}
$dropoff_address = $rates['rate']['destination']['address1'] . ', ' . $d_address_2 . $rates['rate']['destination']['city'] . ' ' . $rates['rate']['destination']['province'] . ', ' . $rates['rate']['destination']['postal_code'] . ', ' . $rates['rate']['destination']['country'];

$ship_data = array(
    'pickup_address' => $pickup_address,
    'dropoff_address' => $dropoff_address

);
// // total up the cart quantities for simple rate calculations
// $quantity = 0;
// foreach($rates['rate']['items'] as $item) {
//     $quantity =+ $item['quantity'];
// }
// // use number_format because shopify api expects the price to be "25.00" instead of just "25"
// // overnight shipping is 5.50 per item
// $overnight_cost = number_format($quantity * 5.50, 2, '', '');
// // regular shipping is 2.75 per item
// $regular_cost = number_format($quantity * 2.75, 2, '', '');
// overnight shipping is 1 to 2 days after today
// $on_min_date = date('Y-m-d H:i:s O', strtotime('+1 day'));
// $on_max_date = date('Y-m-d H:i:s O', strtotime('+2 days'));
// // regular shipping is 3 to 7 days after today
// $reg_min_date = date('Y-m-d H:i:s O', strtotime('+3 days'));
// $reg_max_date = date('Y-m-d H:i:s O', strtotime('+7 days'));
include 'db_config.php';

$query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_GET['shop'] . "' AND status='installed'");
$data = $query->fetch_assoc();
$price = "000";
$currency = "USD";
$curl = curl_init();

curl_setopt_array($curl, [CURLOPT_URL => "https://api.burqup.com/v1/quote", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 30, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => json_encode($ship_data) , CURLOPT_HTTPHEADER => ["Content-Type: application/json", "x-api-key: " . $data['api_key']], ]);

$response = curl_exec($curl);
$err = curl_error($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
file_put_contents('output.txt', $response);
if ($httpcode == 200)
{

    $re = json_decode($response);

    $price = $re->fee;
    $currency = $re->currency;

    $quote_id = $re->id;
    // build the array of line items using the prior values
    $output = array(
        'rates' => array(
            array(
                'service_name' => 'Burq Shipping',
                'service_code' => $quote_id,
                'total_price' => $price,
                'currency' => $currency,
                'min_delivery_date' => '',
                'max_delivery_date' => ''
            )
        )
    );
 
 

}
else
{
    $output = array(
        'rates' => array(
            array(
                'service_name' => 'Burq Shipping',
                'service_code' => 'Burq Shipping',
                'description'=>'you ordered is not delivered by our courier partners delivering to your area.',
                'total_price' => 0.00,
                'currency' => $currency,
                'min_delivery_date' => '',
                'max_delivery_date' => ''
            )
        )
    );
}

    // encode into a json response
    $json_output = json_encode($output);

    // send it back to shopify
    print $json_output;