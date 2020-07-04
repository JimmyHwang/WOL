<?php
$bip = "192.168.0.255";			// Default Broadcast IP

$mac = FALSE;
if (isset($_GET["MAC"])) {
  $mac = $_GET["MAC"];
}
if (isset($_GET["BIP"])) {
  $bip = $_GET["BIP"];			// Broadcast IP
}

if ($mac !== FALSE) {
  WakeuopOnLan($bip, $mac);
  echo "Info: Send Magic Packet to $mac via $bip";
} else {
  echo "Error: MAC Address not found";
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

?>