<?php
if (php_sapi_name() !== "cli") {
die("Only from CLI");
}

$db = mysqli_connect("localhost","root","rou7oEvl","lvtickets");
$player_id = readline("Player ID: ");
$sshift = null;

if((int)$player_id > 0) {
  $sql = "SELECT users.displayname,attributes.value,user_attribute.attribute_id FROM users, user_attribute, attributes WHERE users.id = user_attribute.user_id AND attributes.id = user_attribute.attribute_id AND user_id=".$player_id." AND attribute_id IN(SELECT id FROM attributes WHERE name=\"group\")";
  $res = $db->query($sql);
  if ($res->num_rows <1) die("No such user\n");
  $group = null;
  while($row = $res->fetch_assoc()) {
    echo $row["displayname"] . " is in group ".$row["attribute_id"]." (".$row["value"].")";
    $sql = "SELECT users.displayname, attributes.value, user_attribute.attribute_id FROM users, user_attribute, attributes WHERE users.id = user_attribute.user_id AND attributes.id = user_attribute.attribute_id AND user_id=".$player_id." AND attribute_id IN(SELECT id FROM attributes WHERE name=\"shift\")";
    $res2 = $db->query($sql);
    $group = $row["attribute_id"];
    while($row2 = $res2->fetch_assoc()) {
      echo", shift ".$row2["attribute_id"]." (".$row2["value"].")\n";
      $sshift = $row2["attribute_id"];
    }
  }
} else {
  exit;
}


$sql = "SELECT * FROM attributes WHERE name=\"group\"";
$res = $db->query($sql);

$x=1;
echo "Choose new group: \n";
$groups = array();
while($row = $res->fetch_assoc()) {
  $groups[$x]["value"] = $row["value"];
  $groups[$x]["id"] = $row["id"];
  echo $x . ". " . $row["value"]."\n";
  $x++;
}

$newgroup = readline("New group: ");

echo "Setting group #".$groups[$newgroup]["id"]. " (".$groups[$newgroup]["value"].")\n";

$sql = "SELECT * FROM attributes WHERE name=\"shift\"";
$res = $db->query($sql);

$x=1;
echo "Choose new shoft: \n";
$shift = array();
while($row = $res->fetch_assoc()) {
  $shift[$x]["value"] = $row["value"];
  $shift[$x]["id"] = $row["id"];
  echo $x . ". " . $row["value"]."\n";
  $x++;
}

$newshift = readline("New group: ");

echo "Setting shift #".$shift[$newshift]["id"]. " (".$shift[$newshift]["value"].")\n";
echo "----\n";
$sql = "UPDATE user_attribute SET attribute_id = ".$groups[$newgroup]["id"] ." WHERE user_id=".$player_id." AND attribute_id=".$group . " LIMIT 1";
$db->query($sql);
$sql = "UPDATE user_attribute SET attribute_id = ".$shift[$newshift]["id"] ." WHERE user_id=".$player_id." AND attribute_id=".$sshift . " LIMIT 1";
$db->query($sql);
