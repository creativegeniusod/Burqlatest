<html>
<head>
	<link rel="stylesheet" href="assets/css/burq.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
</head>
<body>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$message = "";
include 'db_config.php';

//**********check app installaed or not****************************//
$query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_GET['shop'] . "' AND status='installed'");

if (mysqli_num_rows($query) < 1)
{
    $shop = $_GET['shop'];

    $api_key = $app_api_key;
    $scopes = "read_orders,write_orders,write_shipping,read_fulfillments, write_fulfillments, read_assigned_fulfillment_orders, write_assigned_fulfillment_orders,write_merchant_managed_fulfillment_orders";
    $redirect_uri = $base_url . "/generate_token.php";

    $install_url = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . $redirect_uri;

    header("Location: " . $install_url);

}
else
{
    if (isset($_GET['hmac']) && !isset($_GET['session']))
    {

        $app_page = "https://" . $_GET['shop'] . "/admin/apps/burq-1";
        header("Location: " . $app_page);
    }
    $data = $query->fetch_assoc();

//**************Validate post api key  ******************//
    if ($_POST && $_POST['api-key'] != "")
    {

        $curl = curl_init();

        curl_setopt_array($curl, [CURLOPT_URL => "https://api.burqup.com/v1/deliveries", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 30, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["x-api-key: " . $_POST['api-key']], ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($httpcode == 200)
        {
            $api_key = $_POST["api-key"];
            $store = $_GET["shop"];
 //**************Update key in DB  ******************//
            $sql = "UPDATE stores SET api_key='$api_key' WHERE store='$store'";
            mysqli_query($conn, $sql);
            $message = '<p class="success">Api Key saved successfully.</p>';
        }
        else
        {
            $message = '<p class="error">Incorrect Api Key.</p>';
        }

        $query = mysqli_query($conn, "SELECT * FROM stores WHERE store='" . $_GET['shop'] . "' AND status='installed'");
        $data = $query->fetch_assoc();
    }

?>
	 <div class="container">
	 <a class="burq-link" href="https://dashboard.burqup.com/" target="_blank">Burq Dashboard</a>
	 <!-- <a class="burq-link" id="list-all-order" href="list_order.php">View Orders</a> -->


	 <form action="#" method="post"  id="apikey-form" >
	 	<?php echo $message; ?>
	 	<?php if ($data['api_key'] != "" && $data['api_key'] != NULL)
    { ?>
	 	 <div class="form-group">
	 	 	 <label >Enable/Disable Shipping option</label>
	 	
	 	<input id="toggle-shipping" type="checkbox" <?php if ($data['service_status'] == "on")
        {
            echo 'checked';
        } ?> data-toggle="toggle">
	 </div>
	<?php
    } ?>
  <div class="form-group">
    <label for="api-key">Api key</label>
    <input type="api_key" class="form-control" id="api-key" name="api-key" aria-describedby="apikey" placeholder="Api key" value="<?php echo $data['api_key']; ?>">
    <small id="apikey" class="form-text text-muted">You can get the api key from Burq Dashboard.</small>
  </div>
  
  <button type="submit" class="btn btn-primary" id="sub-btn">Submit</button>
</form>
	</div>
	 <?php
}
?>
</body>
</html>
<script>
	$(document).ready(function(){

$('#sub-btn').click(function(){
	$(this).text('Processing...');
})
      $('#toggle-shipping').change(function(){
        var mode= $(this).prop('checked');
        var store="<?php echo $_GET['shop']; ?>";
         $.ajax({
          type:'POST',
          dataType:'JSON',
          url:'enable_disable.php',
          data:'mode='+mode+'&store='+store,
          success:function(data)
          {

            var data=eval(data);
            message=data.message;
            alert(message);
          }
        });
	});
  });
	</script>
