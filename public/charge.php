<?php
ini_set("error_reporting", ~E_NOTICE);
require("../stripe_keys.php");

if ($_POST) {
  $items = explode(" ", $_GET["items"]);
  $cost = 0;
  foreach ($items as $item) {
    if ($item == "AB") $cost +=200;
    if ($item == "BC") $cost +=200;
    if ($item == "M") $cost +=100;
    if ($item == "C") $cost +=150;
    if ($item[0] == "G") $cost +=250;
    if ($item[0] == "L") $cost +=250;
  }
  if ($cost > 0) {
    require("../vendor/stripe/stripe-php/init.php");
    \Stripe\Stripe::setApiKey($skey);
    $token = $_POST['stripeToken'];
    $charge = \Stripe\Charge::create(array(
      "amount" => 300,//(int)$cost * 100,
      "currency" => "sek",
      "description" => implode("+", $items),
      "statement_descriptor" => "Lotka-Volterra",
      "metadata" => array("uid" => (int)$_GET["uid"]),
      "source" => $token,
    ));
    if ($charge->outcome->network_status == "approved_by_network") {
      header("Location: thanks.php");
    } else {
      echo "Payment failed: " . $charge->outcome->seller_message;
    }
  } else {
    die("Something happened that shouldn't happen. You should go back and try again.");
  }
die();
}

if ((int)$_GET["uid"] <1) {
  die("You seem to have been logged out. Log in and try again.");
}

$cost = 0;
if ($_GET["bus_airport"]) $cost +=200;
if ($_GET["bus_central"]) $cost +=200;
if ($_GET["gents_tshirt"]) {
  $cost +=250;
  $reg_size = $_GET["gents_tshirt_size"];
}
if ($_GET["ladies_tshirt"]) {
  $cost +=250;
  $slim_size = $_GET["ladies_tshirt_size"];
}
if ($_GET["roll_mat"]) $cost +=100;

if ($_GET["cot"]) $cost +=150;

if ($cost < 1) {
  header("Location: shop.php?uid=".$_GET["uid"]);
  die();
}

$db = mysqli_connect("localhost","root","rou7oEvl","lvtickets");
$sql = "SELECT email FROM users WHERE id=".(int)$_GET["uid"];
$res = $db->query($sql);
$user = $res->fetch_assoc()["email"];

?><html>
<head>
    <link href="/assets/lib/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Volkhov:400i" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
    <link href="/assets/lib/animate.css/animate.css" rel="stylesheet">
    <link href="/assets/lib/components-font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="/assets/lib/et-line-font/et-line-font.css" rel="stylesheet">
    <link href="/assets/lib/flexslider/flexslider.css" rel="stylesheet">
    <link href="/assets/lib/owl.carousel/dist/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="/assets/lib/owl.carousel/dist/assets/owl.theme.default.min.css" rel="stylesheet">
    <link href="/assets/lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="/assets/lib/simple-text-rotator/simpletextrotator.css" rel="stylesheet">
    <link href="/assets/lib/jquery.growl/stylesheets/jquery.growl.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Pathway+Gothic+One" rel="stylesheet">
    <link href="/assets/css/theme/lotka-volterra.css" rel="stylesheet">
    <script src="/assets/lib/jquery/dist/jquery.js"></script>
    <script src="/assets/lib/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="/assets/js/main.js"></script>
<script src="/assets/js/theme/script.js"></script>
</head>
<body>
<style>
select,input {
  border:1px solid white;
  color: white;
  background-color:black;
}
table {
  border-spacing: 5px;
  border-collapse: separate;
}
td {
  padding:10px;
  border: 1px solid #777 !important;
}
body {
  padding:10px;
}

.stripe-button-el {
  background: none !important;
  background-color: #91e28f !important;
}
</style>
<h1>Lotka-Volterra Shop</h1>
<h2>Total cost: <?=(int)$cost;?> SEK</h2>
<ul>
<?php
if ($_GET["bus_airport"]==1) {
  echo "<li>Airport Bus (Airport) - 200 SEK</li>\n";
  $pstring[] = "AB";
}
if ($_GET["bus_central"]==1) {
  echo "<li>Stockholm Central Bus - 200 SEK</li>\n";
  $pstring[] = "BC";
}
if ($_GET["gents_tshirt"]==1) {
  echo "<li>Gents T-shirt (size ". $reg_size.") - 250 SEK</li>\n";
  $pstring[] = "G" . $reg_size;
}
if ($_GET["ladies_tshirt"]==1) {
  echo "<li>Ladies T-shirt (size ". $slim_size.") - 250 SEK</li>\n";
  $pstring[] = "L" . $reg_size;
}
if ($_GET["roll_mat"]==1) {
  echo "<li>Roll mat - 100 SEK</li>\n";
  $pstring[] = "M";
}
if ($_GET["cot"]==1) {
  echo "<li>Cot - 150 SEK</li>\n";
  $pstring[] = "C";
}
?>
</ul>

<i>Your email is already prefilled.</i>
<br><br>
<form action="https://lotka-volterra.se/charge.php?items=<?=implode("+", $pstring);?>&uid=<?=$_GET["uid"];?>" method="POST">
  <script
    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
    data-key="<?=$key;?>"
    data-amount="<?=$cost*100;?>"
    data-name="Lotka-Volterra"
    data-description="<?=implode("+", $pstring);?>"
    data-image="https://lotka-volterra.se/assets/media/f65cd39b513820d6.png"
    data-locale="en"
    data-label="Pay via Stripe"
    data-currency="SEK"
    data-email="<?=$user;?>"
    data-allow-remember-me="false"
    data-zip-code="false">
  </script>
</form>
