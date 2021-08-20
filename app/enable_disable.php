<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_config.php';
if ($_POST)
{
 //***********Check app status*************//
    $query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_POST['store'] . "' AND status='installed'");

    $data = $query->fetch_assoc();

    if ($_POST['mode'] == "true")
    {
        $active = true;
        $status = "on";
    }
    else
    {
        $active = false;
        $status = "off";
    }
    $ser_id = $data['service_id'];
    $carrier_stop_array = array(
        "carrier_service" => array(
            "id" => $ser_id,
            "name" => "Burq Shipping",
            "active" => $active,

        )
    );
     //***********Enable/Disable shipping option on store end through api*************//
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://" . $_POST['store'] . "/admin/api/" . $shopify_api_version . "/carrier_services/" . $ser_id . 'json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($carrier_stop_array) ,

        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . $data['access_token']
        ) ,
    ));

    $response = curl_exec($curl);
    $re = json_decode($response);

    curl_close($curl);
    // echo '<pre>';
    // print_r($re);
    if (isset($re->errors))
    {
        return json_encode(['status' => "ERROR", "message" => "Something went wrong.Please try again."], 401);
    }
    else
    {
    	//************Update shipping option on/off status in DB*********************//
        $store = $_POST["store"];
        $ser_sql = "UPDATE stores SET service_status='$status' WHERE store='$store'";
        mysqli_query($conn, $ser_sql);
        echo json_encode(['status' => "OK", "message" => "Data Updated successfully."], 200);
    }
}
?>
