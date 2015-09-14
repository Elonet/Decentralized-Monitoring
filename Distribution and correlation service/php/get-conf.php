<?php
/*
 * Decentralized Monitoring source code
 * https://github.com/Elonet/Decentralized-Monitoring
 *
 * Copyright 2015, Leo Leroy
 * https://elonet.fr/
 *
 * Licensed under the GPLv3 license:
 * http://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
 
include("/etc/decentralized_monitoring/config.conf");
 header("Access-Control-Allow-Origin: *");
$array = array("give" => $conf['get_item_url'], "result" => $conf['result_url'], "update" => $conf['check_time_app']);
$json = json_encode($array);
echo $json;
?>

