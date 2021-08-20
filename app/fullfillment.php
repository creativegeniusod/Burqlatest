<?php
$webhook_content = '';
$webhook = fopen('php://input', 'rb');
while (!feof($webhook))
{ //loop through the input stream while the end of file is not reached
    $webhook_content .= fread($webhook, 4096); //append the content on the current iteration
    
}
fclose($webhook); //close the resource
$orders = json_decode($webhook_content, true); //convert the json to array
//Save to text file for checking
if ($orders['id'])
{
    include 'db_config.php';

    $query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_GET['shop'] . "'");

    $row = $query->fetch_array(MYSQLI_ASSOC);
    $fulfill = $orders['fulfillment_status'];
    $pay_status = $orders['financial_status'];
    $sql1 = "UPDATE orders SET fulfillment_status='$fulfill',payment_status='$pay_status' WHERE store_id='" . $row['id'] . "' AND order_id='" . $orders['id'] . "'";

    mysqli_query($conn, $sql1);

    $store = $row['store'];
    $acs_tkn = $row['access_token'];

    $query1 = mysqli_query($conn, "SELECT * FROM orders WHERE store_id='" . $row['id'] . "' AND order_id='" . $orders['id'] . "'");

    $row1 = $query1->fetch_array(MYSQLI_ASSOC);
    if ($row1['track_id'] != "")
    {
        $total_full = count($orders['fulfillments']);
        $update_full = $total_full - 1;
        
    //**************Prepare data for the api ******************//
        $fullfill = array(
            'fulfillment' => array(
                "notify_customer" => true,
                "tracking_info" => array(
                    'number' => $row1['track_id'],
                    'url' => 'https://burqup.com/track/' . $row1['track_id'],
                    'company' => "Burq Shipping"

                )
            )
        );
        $curl = curl_init();
    //**************Call api to add tracking url ******************//
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . $store . '/admin/api/' . $shopify_api_version . '/fulfillments/' . $orders['fulfillments'][$update_full]['id'] . '/update_tracking.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($fullfill) ,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Shopify-Access-Token:' . $acs_tkn

            ) ,
        ));
        $response1 = curl_exec($curl);
        curl_close($curl);

    }

}

