<?php
ini_set("error_reporting", ~E_NOTICE);
if ((int)$_GET["uid"] <1) {
  die("You seem to have been logged out. Log in and try again.");
}
if ($_POST) {
  $_POST["uid"] = (int) $_GET["uid"];
  header("Location: charge.php?".http_build_query($_POST));
  die();
}

$db = mysqli_connect("localhost","root","rou7oEvl","lvtickets");
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
td img {
  max-width:500px;
}
body {
  padding:10px;
}
</style>
<script>
$(document).ready(function() {
  var totalcost = 0;
  function addToCart(thing) {
    alert(thing);
  }

  $(".addToCart").click(function() {
    if ($(this).find("span").hasClass("fa-check")) {
      $(this).find("span").removeClass("fa-check");
      $($(this).next("input")).val(0);
      totalcost = totalcost - $(this).data("cost");
    } else {
      $(this).find("span").addClass("fa-check");
      totalcost = totalcost + $(this).data("cost");
      $($(this).next("input")).val(1);
    }
  });

});

</script>

<h1>Lotka-Volterra Shop</h1>

<form action="" method="post">
<table>
  <tr>
    <td>
<img src="https://lotka-volterra.se/assets/media/f50f3d92f5923c50.jpg"><br>
<h1>Bus ticket (Airport)</h1>
<p>Return ticket between Arlanda Airport and the larp venue. Leaves from Arlanda Airport at 11:30 on thursday and leaves for the airport on sunday at 11:00.</p>
<p><b>NOTE!</b> Deadline for ordering this item is March 25th</p>
<button type="button" class="addToCart btn btn-green" data-id="bus_airport"><span class="fa"></span>SEK 200</button>
<input type="hidden" name="bus_airport" value="0">
    </td>
    <td>
<img src="https://lotka-volterra.se/assets/media/3283433f2194fd9f.jpg"><br>
<h1>Bus ticket (Stockholm Central)</h1>
<p>Return ticket between Stockholm Central and the larp venue. Leaves from Stockholm Central at 10:50 on thursday and leaves for the Central on sunday at 11:00.</p>
<p><b>NOTE!</b> Deadline for ordering this item is March 25th</p>
<button type="button" class="addToCart btn btn-green" data-id="bus_central"><span class="fa"></span>SEK 200</button>
<input type="hidden" name="bus_central" value="0">
    </td>
  </tr>
  <tr>
    <td>
<img src="https://lotka-volterra.se/assets/media/a52442aeff968024.jpg"><br>
<h1>T-shirt ("Gents" model)</h1>
<p>Swag to show you were there! Dark tshirt with color print. <i>"Gents" is what the tshirt maker call this model, not our term</i>.</p>
<p><b>NOTE!</b> Deadline for ordering this item is March 25th</p>
<select name="gents_tshirt_size">
  <option value="S">Small</option>
  <option value="M">Medium</option>
  <option value="L">Large</option>
  <option value="XL">X-Large</option>
  <option value="XXL">XX-Large</option>
  <option value="XXXL">XXX-Large</option>
</select><br>
<button type="button" class="addToCart btn btn-green" data-id="gents_tshirt"><span class="fa"></span>SEK 250</button>
<input type="hidden" name="gents_tshirt" value="0">
    </td>
    <td>
<img src="https://lotka-volterra.se/assets/media/a52442aeff968024.jpg"><br>
<h1>T-shirt ("Ladies" model)</h1>
<p>Swag to show you were there! Dark tshirt with color print. <i>"Ladies" is what the tshirt maker call this model, not our term</i>.</p>
<p><b>NOTE!</b> Deadline for ordering this item is March 25th</p>
<select name="ladies_tshirt_size">
  <option value="S">Small</option>
  <option value="M">Medium</option>
  <option value="L">Large</option>
  <option value="XL">X-Large</option>
  <option value="XXL">XX-Large</option>
</select><br>
<button type="button" class="addToCart btn btn-green" data-id="ladies_tshirt"><span class="fa"></span>SEK 250</button>
<input type="hidden" name="ladies_tshirt" value="0">
    </td>
  </tr>
  <tr>
    <td>
<img src="https://lotka-volterra.se/assets/media/639067378126c6d6.jpg"><br>
<h1>Roll mat</h1>
<p>In case you don't have the opportunity to bring a roll matt to insulate your cot, we can get one for you.</p>
<button type="button" class="addToCart btn btn-green" data-id="roll_mat"><span class="fa"></span>SEK 100</button>
<input type="hidden" name="roll_mat" value="0">
    </td>
    <td>
<img src="https://lotka-volterra.se/assets/media/d80abd3587a65eaf.jpg"><br>
<h1>Camping cot</h1>
<p>Want to keep your cot after the larp? Get it at a killer price.</p>
<button type="button" class="addToCart btn btn-green" data-id="cot"><span class="fa"></span>SEK 150</button>
<input type="hidden" name="cot" value="0">
    </td>
  </tr>
</table>
<br>
<input type="submit" value=" PAY " class="btn btn-green">
</form>
