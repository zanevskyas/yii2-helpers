<?php
namespace Kakadu\Yii2Helpers\Queue;

use yii\base\Component;

/**
 * Class SmsComponent for sms assistant
 */
class SmsComponent extends Component
{
    /**
     * @var bool
     */
    public $test = false;
    /**
     * @var string
     */
    public $oneUrl;
    /**
     * @var string
     */
    public $packetUrl;
    /**
     * @var string
     */
    public $balanceUrl;
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $sender;

    public function send($phone, $text)
    {
        if ($this->test) {
            return true;
        }

        $text     = urlencode($text);
        $phone    = str_replace([' ', '(', ')', '-'], '', $phone);
        $url      = $this->oneUrl . '?user=' . $this->user . '&password=' . $this->password . '&recipient=' . $phone . '&message=' . $text . '&sender=' . $this->sender;
        $curl     = curl_init();
        $header[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $header[] = 'Cache-Control: max-age=0';
        $header[] = 'Connection: keep-alive';
        $header[] = 'Keep-Alive: 300';
        $header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
        $header[] = 'Accept-Language: en-us,en;q=0.5';
        $header[] = 'Pragma: '; // browsers keep this blank.
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($curl); // execute the curl command
        curl_close($curl); // close the connection

        return ((int) $html) > 0;
    }

    public function sendPacket($phones, $text)
    {
        if (empty($phones)) {
            return false;
        }
        $postdata = $this->get_data($phones, $text);

        $ch = curl_init($this->packetUrl);
        curl_setopt($ch, CURLOPT_URL, $this->packetUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
        $header[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $header[] = 'Content-Type: text/xml';
        $header[] = 'Cache-Control: max-age=0';
        $header[] = 'Connection: keep-alive';
        $header[] = 'Keep-Alive: 300';
        $header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
        $header[] = 'Accept-Language: en-us,en;q=0.5';
        $header[] = 'Pragma: '; // browsers keep this blank.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $content = curl_exec($ch);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        return $header;
    }

    public function get_data($phones, $text): string
    {
        $postdata = '<?xml version="1.0" encoding="utf-8" ?><package login="' . $this->user . '" password="' . $this->password . '"><message>';
        foreach ($phones as $phone) {
            $postdata .= '<msg recipient="' . $phone . '" sender="' . $this->sender . '" validity_period="86400">' . $text . '</msg>';
        }
        $postdata .= '</message></package>';

        return $postdata;
    }

    public function checkBalance()
    {
        $url       = $this->balanceUrl . '?user=' . $this->user . '&password=' . $this->password;
        $curl      = curl_init();
        $header[0] = 'Accept: text/xml,application/xml,application/xhtml+xml,';
        $header[0] .= 'text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $header[]  = 'Cache-Control: max-age=0';
        $header[]  = 'Connection: keep-alive';
        $header[]  = 'Keep-Alive: 300';
        $header[]  = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
        $header[]  = 'Accept-Language: en-us,en;q=0.5';
        $header[]  = 'Pragma: '; // browsers keep this blank.
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($curl); // execute the curl command
        curl_close($curl); // close the connection

        return $html;
    }
}