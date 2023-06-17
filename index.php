<?php
function GetDataBetweenTags($line, $tag1, $tag2) {
  $result = false;
  $p1 = strpos($line, $tag1);
  $p2 = strpos($line, $tag2);
  if ($p1 !== false && $p2 !== false) {      
    $p1 += strlen($tag1);
    $result = trim(substr($line, $p1, $p2-$p1));
  }
  return $result;
}

function GetNicInfo() {
  $interfaceCommand = "ifconfig";
  exec($interfaceCommand, $lines);
  $info = new stdClass();
  foreach ($lines as $line) {
    $p = strpos($line, ": flags");
    if ($p !== false) {
      $nic = substr($line, 0, $p);
      $info->$nic = new stdClass();
    }
    $ip = GetDataBetweenTags($line, "inet", "netmask");
    if ($ip !== false) {
      $info->$nic->IP = $ip;
    }
    $mac = GetDataBetweenTags($line, "ether", "txqueuelen");
    if ($mac !== false) {
      $info->$nic->MAC = $mac;
    }
  }
  
  return $info;
}

function GetBroadcastIP($ip) {
  $temp = explode(".", $ip);
  $temp[3] = 255;
  $bip = join(".",$temp);
  return $bip;
}

function WakeuopOnLan($broadcast, $mac, $port = 7) {
  $hwaddr = pack('H*', preg_replace('/[^0-9a-fA-F]/', '', $mac));

  //
  // Create Magic Packet
  //
  $packet = sprintf (
    '%s%s',
    str_repeat(chr(255), 6),
    str_repeat($hwaddr, 16)
  );

  $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

  if ($sock !== false) {
    $options = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);

    if ($options !== false) {
      socket_sendto($sock, $packet, strlen($packet), 0, $broadcast, $port);
      socket_close($sock);
    }
  }
}

//-----------------------------------------------------------------------------
// Main
//-----------------------------------------------------------------------------
  $broadcast_list = array();
  $broadcast_ip = false;
  $mac = false;

  if (php_sapi_name() == "cli") {
    $cli_flag = true;
    $CRLF = "\n";
    $longopts  = array(
      "MAC:",             // Required value
      "B:",               // Required value
      // "optional::",    // Optional value
      // "option",        // No value
      // "opt",           // No value
    );
    $options = getopt("f:hp:", $longopts);
    // var_dump($options);
    if (isset($options["MAC"])) {
      $mac = $options["MAC"];
    }
    if (isset($options["B"])) {
      $broadcast_ip = $options["B"];
    }
  } else {
    $cli_flag = false;
    $CRLF = "<br>";
    if (isset($_GET["MAC"])) {
      $mac = $_GET["MAC"];
    }
    if (isset($_GET["B"])) {
      $broadcast_ip = $_GET["B"];
    }
  }

  if ($broadcast_ip !== false) {
    array_push($broadcast_list, $broadcast_ip);
  } else {
    $nic_list = GetNicInfo();
    foreach ($nic_list as $nic_name => $nic_info) {
      if (strpos($nic_name, "eth") !== false && isset($nic_info->IP)) {
        $ip = $nic_info->IP;
        $bip = GetBroadcastIP($ip);
        array_push($broadcast_list, $bip);
      }
    }
  }
  
  if ($mac !== FALSE) {
    foreach ($broadcast_list as $bip) {
      WakeuopOnLan($bip, $mac);
      echo "Info: Send Magic Packet to $mac [$bip]$CRLF";
    }
  } else {
    echo "Error: MAC Address not found$CRLF";
  }
?>