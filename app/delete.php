<?php
include 'db_config.php';

define('SHOPIFY_APP_SECRET', $app_api_secret); // Replace with your SECRET KEY
function verify_webhook($data, $hmac_header)
{
    $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
    return hash_equals($hmac_header, $calculated_hmac);
}

$res = '';
$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
$topic_header = $_SERVER['HTTP_X_SHOPIFY_TOPIC'];
$shop_header = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
$data = file_get_contents('php://input');
$decoded_data = json_decode($data, true);

$verified = verify_webhook($data, $hmac_header);

if ($verified == true)
{
    if ($topic_header == 'app/uninstalled' || $topic_header == 'shop/update')
    {
        if ($topic_header == 'app/uninstalled')
        {
            //***************Update data in DB when the store removed the app from the store*************************//
            $query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $shop_header . "'");
            $row = $query->fetch_array(MYSQLI_ASSOC);

            $store = "DELETE FROM smsa_details WHERE store_id='" . $row['id'] . "'";
            $result = mysqli_query($conn, $store);

            $sql = "UPDATE stores SET status='uninstalled',api_key=NULL,service_id=NULL,service_status='off' WHERE store='" . $shop_header . "'";
            $result = mysqli_query($conn, $sql);

            $response->shop_domain = $decoded_data['shop_domain'];

            $res = $decoded_data['shop_domain'] . ' is successfully deleted from the database';
        }
        else
        {
            $res = $data;
        }
    }
}
else
{
    $res = 'The request is not from Shopify';
}

error_log('Response: ' . $res); //check error.log to see the result



?>
