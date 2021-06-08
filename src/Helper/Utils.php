<?php
namespace App\Helper;

class Utils
{
    public static function ipInRange(string $ip, string $range): bool
    {
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }

    public static function sendMusCommand(string $command, string $data = null): bool
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

    public static function securise(string $str): string
    {
        return htmlspecialchars(stripslashes(nl2br(trim($str))), ENT_QUOTES, 'ISO-8859-1');
    }

    public static function hashMdp(string $str, bool $old = true): string
    {
        if ($old) {
            return md5(self::securise(self::securise($str)));
        }
        
        return password_hash($str, PASSWORD_BCRYPT);
    }

    public static function ticketRefresh(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return "ticket-" . md5($randomString);
    }

    public static function getSslPage(string $url): string
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

    public static function getUserIP(): string
    {
        if ($_SERVER['REMOTE_ADDR'] == '178.33.7.19' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

            $ip = explode(",", $forward)[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return !empty($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
    }

    public static function generateHash(int $qtd): string
    {
        $characters = 'abcdefghijklmopqrstuvxwyzABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
        $hash = '';

        for ($x = 1; $x <= $qtd; $x++) {
            $postChar = rand(0, strlen($characters) - 1);
            $hash .= substr($characters, $postChar, 1);
        }

        return $hash;
    }

    public static function junkMail(string $mail): bool
    {
        $domains = array('@wibbo.org', 'pjjkp.com', 'ephemail.com', 'ephemail.org', 'ephemail.net', 'jetable.org', 'jetable.net', 'jetable.com', 'haltospam.com', 'tempinbox.com', 'brefemail.com', '0-mail.com', 'link2mail.net', 'mailexpire.com', 'spambox.info', 'mytrashmail.com', 'mailinator.com', 'dontreg.com', 'maileater.com', 'brefemail.com', '0-mail.com', 'brefemail.com', 'ephemail.net', 'guerrillamail.com', 'guerrillamail.info', 'haltospam.com', 'iximail.com', 'jetable.net', 'jetable.org', 'kasmail.com', 'klassmaster.com', 'kleemail.com', 'link2mail.net', 'mailin8r.com', 'mailinator.com', 'mailinator.net', 'mailinator2.com', 'myamail.com', 'nyms.net', 'shortmail.net', 'sogetthis.com', 'spambox.us', 'spamday.com', 'Spamfr.com', 'spamgourmet.com', 'spammotel.com', 'tempinbox.com', 'yopmail.fr', 'guerrillamail.org', 'temporaryinbox.com', 'spamcorptastic.com', 'filzmail.com', 'lifebyfood.com', 'tempemail.net', 'spamfree24.org', 'spamfree24.com', 'spamfree24.net', 'spamfree24.de', 'spamfree24.eu', 'spamfree24.info', 'spamherelots.com', 'thisisnotmyrealemail.com', 'slopsbox.com', 'trashmail.net', 'myamail.com', 'tyldd.com', 'safetymail.info', 'brefmail.com', 'bofthew.com', 'trash-mail.com', 'wimsg.com', 'emailo.pro', 'boximail.com');

        list($user, $domain) = explode('@', $mail);

        return in_array($domain, $domains);
    }
}