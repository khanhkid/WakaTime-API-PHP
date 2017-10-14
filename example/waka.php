<?php

require '../src/Wakatime.php';

use Khanhkid\Wakatime\WakatimeAPI;

$waka = new WakatimeAPI("e18cf41f-9cbf-40d4-a844-a767c82b32af");

$result = $waka->getSummaries("2017-10-12","2017-10-12");

echo '<pre>',var_dump($result),'</pre>';die();
?>
