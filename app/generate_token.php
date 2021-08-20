<?php
include 'db_config.php';
$params = $_GET; // Retrieve all request parameters
$hmac = $_GET['hmac']; // Retrieve HMAC request parameter
$params = array_diff_key($params, array(
    'hmac' => ''
)); // Remove hmac from params
ksort($params); // Sort params lexographically
// Compute SHA256 digest
$computed_hmac = hash_hmac('sha256', http_build_query($params) , $app_api_secret);

// Use hmac data to check that the response is from Shopify or not
if (hash_equals($hmac, $computed_hmac))
{

    $query = array(
        "client_id" => $app_api_key, // Your API key
        "client_secret" => $app_api_secret, // Your app credentials (secret key)
        "code" => $_GET['code'] // Grab the access key from the URL
        
    );

    // Generate access token URL
    $access_token_url = "https://" . $_GET['shop'] . "/admin/oauth/access_token";

    // Configure curl client and execute request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $access_token_url);
    curl_setopt($ch, CURLOPT_POST, count($query));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
    $result = curl_exec($ch);
    curl_close($ch);

    // Store the access token
    $result = json_decode($result, true);

    $access_token = $result['access_token'];

    //********Register webhook for uninstall app***************//
    

    $uninstall = array(
        'webhook' => array(
            'topic' => 'app/uninstalled',
            'address' => $base_url . '/delete.php?shop=' . $_GET['shop'],
            'format' => 'json'
        )
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_GET['shop'] . "/admin/api/" . $shopify_api_version . "/webhooks.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($uninstall) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $access_token
        )
    ));

    $uninstall = curl_exec($curl);

    curl_close($curl);

    //********Register webhook for get order data in app***************//
    $order = array(
        'webhook' => array(
            'topic' => 'orders/create',
            'address' => $base_url . '/order.php?shop=' . $_GET['shop'],
            'format' => 'json'
        )
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_GET['shop'] . "/admin/api/" . $shopify_api_version . "/webhooks.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($order) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $access_token
        ) ,
    ));

    $order_resp = curl_exec($curl);

    curl_close($curl);

    //********Register webhook for get partial fullfillment data in app***************//
    $par_fullfill = array(
        'webhook' => array(
            'topic' => 'orders/partially_fulfilled',
            'address' => $base_url . '/fullfillment.php?shop=' . $_GET['shop'],
            'format' => 'json'
        )
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_GET['shop'] . "/admin/api/" . $shopify_api_version . "/webhooks.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($par_fullfill) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $access_token
        ) ,
    ));

    $par_fullfill_resp = curl_exec($curl);

    //********Register webhook for get complete fullfillment data in app***************//
    curl_close($curl);
    $fullfill = array(
        'webhook' => array(
            'topic' => 'orders/fulfilled',
            'address' => $base_url . '/fullfillment.php?shop=' . $_GET['shop'],
            'format' => 'json'
        )
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_GET['shop'] . "/admin/api/" . $shopify_api_version . "/webhooks.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fullfill) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $access_token
        ) ,
    ));

    $fullfill_resp = curl_exec($curl);

    curl_close($curl);

    //********Add carrier service to display shipping option with shipping charges on app end***************//
    $carrier_array = array(
        "carrier_service" => array(
            "name" => "Burq Shipping",
            "callback_url" => $base_url . "/rates.php?shop=" . $_GET['shop'],
            "format" => "json",
            "active" => false,
            "service_discovery" => true,
            "carrier_service_type" => "legacy",
            "settings" => array(
                "use_on_checkout" => true
            )
        )
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_GET['shop'] . "/admin/api/" . $shopify_api_version . "/carrier_services",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($carrier_array) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $access_token
        ) ,
    ));

    $response = curl_exec($curl);
    $re = json_decode($response);

    curl_close($curl);
    if (isset($re->errors))
    {
        $service = NULL;
        $service_status = NULL;
    }
    else
    {

        $service = $re
            ->carrier_service->id;
        $service_status = "off";
    }
    $shop = $_GET['shop'];
    $created = date("Y-m-d H:i:s");
    $modified = date("Y-m-d H:i:s");

    //***********Check app status*************//
    $query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_GET['shop'] . "' AND status='uninstalled'");

    if (mysqli_num_rows($query) > 0)
    {
        //***********Update data if app already installed*************//
        $sql = "UPDATE stores SET status='installed',access_token='$access_token',service_id='$service',service_status='$service_status' WHERE store='" . $_GET['shop'] . "'";
        $result = mysqli_query($conn, $sql);

    }

    else
    {
        //***********create new entry in app db if app not installed*************//
        $sql = "INSERT INTO stores(store,access_token,status,service_id,service_status,created_at,modified_on) VALUES('$shop','$access_token','installed','$service','$service_status','$created','$modified')";

        if (mysqli_query($conn, $sql))
        {
            //echo "Records inserted successfully.";
            
        }
        else
        {
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
    }
    // Close connection
    mysqli_close($conn);

    $app_page = "https://" . $shop . "/admin/apps/burq-1";
    header("Location: " . $app_page);
}
else
{
    echo 'not valid';
}
?>
