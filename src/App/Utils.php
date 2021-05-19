<?php
function ipInRange($ip, $range)
{
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~$wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function sendMusCommand($command, $data = null)
{
    $data = $command . chr(1) . $data;
    $connection = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
    socket_connect($connection, getenv('MUS_IP'), getenv('MUS_PORT'));
    if (!is_resource($connection)) {
        socket_close($connection);
        
        return false;
    } else {
        socket_send($connection, $data, strlen($data), MSG_DONTROUTE);
        socket_close($connection);

        return true;
    }
}

function securise($str)
{
    return htmlspecialchars(stripslashes(nl2br(trim($str))), ENT_QUOTES, 'ISO-8859-1');
}

function hashMdp($str, $old = true)
{
    if($old)
        return md5(securise(securise($str)));
    else
        return password_hash($str, PASSWORD_BCRYPT);
}

function ticketRefresh()
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 10; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return "ticket-" . md5($randomString);
}

function getSslPage($url)
{
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1';
    $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
    $headers[] = 'Accept-Language: ar,en;q=0.5';
    $headers[] = 'Connection: keep-alive';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/535.36 (KHTML, like Gecko) Chrome/36.0.1985.49 Safari/537.36");
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getUserIP()
{
    if ($_SERVER['REMOTE_ADDR'] == '178.33.7.19' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') 
    {
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $ip = explode(",", $forward)[0];
    } 
    else 
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return !empty($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
}