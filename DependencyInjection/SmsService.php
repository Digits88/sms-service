<?php

namespace Nethesis\Service\SmsBundle\DependencyInjection;

class SmsService
{
    public function sendAction(
        $operator,
        $login,
        $pass,
        $caller,
        $custom_url,
        $cell,
        $msg,
        $portech_ip = null)
    {
        switch($operator) {
            case 'smshosting':
                $fields = array('user' => $login,
                                'password' => $pass,
                                'mittente' => $caller,
                                'numero' => (strlen($cell) <= 10 ? '39'. $cell : $cell),
                                'testo' => $msg);

                $ret = SmsService::__send_rest($fields,
                    'www.smshosting.it', '/smsMaster/invioSmsHttp.do');

                $resxml = new \SimpleXMLElement($ret);

                if ($resxml->CODICE != 'HTTP_00')
                    throw new \Exception($resxml->DESCRIZIONE);

                break;
            case 'tol':
                $fields = array('user' => $login,
                                'pass' => $pass,
                                'sender' => $caller,
                                'rcpt' => $cell,
                                'data' => $msg,
                                'qty' => 'h');

                $ret = SmsService::__send_rest($fields, 'sms.tol.it');

                break;
            case 'gatewaysms':
                $fields = array('login' => $login,
                                'pwd' => $pass,
                                'numero' => $cell,
                                'testo' => $msg);

                $ret = SmsService::__send_rest($fields,
                    'www.gatewaysms.it', '/gateway.php');

                break;
            case 'smsmarket':
                $fields = array('user' => $login,
                                'pass' => $pass,
                                'sender' => $caller,
                                'rcpt' => $cell,
                                'data' => $msg);

                $ret = SmsService::__send_rest($fields, 'sms.smsmarket.it');

                break;
            case 'mobyt':
                $fields = array('user' => $login,
                                'pass' => $pass,
                                'sender' => $caller,
                                'rcpt' => $cell,
                                'data' => $msg,
                                'qty' => 'h');

                $ret = SmsService::__send_rest($fields, 'client.mobyt.it');

                break;
            case '9net':
                $fields = array('user' => $login,
                                'pass' => $pass,
                                'sender' => $caller,
                                'rcpt' => $cell,
                                'data' => $msg);

                $ret = SmsService::__send_rest($fields, 'sms.host.tld');

                break;
            case 'custom':
                $url = str_replace('$user', urlencode($login), $custom_url);
                $url = str_replace('$pass', urlencode($pass), $url);
                $url = str_replace('$caller', urlencode($caller), $url);
                $url = str_replace('$dest', urlencode($cell), $url);
                $url = str_replace('$msg', urlencode($msg), $url);

                $url_list = parse_url($url);

                $ret = SmsService::__send_raw($url_list['host'],
                                              $url_list['path'],
                                              $url_list['query']);

                break;
            case 'portech':
                $ret = SmsService::__send_portech($login,
                                                         $pass,
                                                         $cell,
                                                         $msg,
                                                         $caller,
                                                         $portech_ip);

                break;
            default:
                throw new \Exception('operator not supported');
        }
    }

    private function __send_rest($fields, $host, $url = '/sms/send.php') {
        $qs = array();

        foreach ($fields as $k => $v)
            $qs[] = $k. '='. urlencode($v);

        $qs = join('&', $qs);

        return preg_replace("/^.*?\r\n\r\n/s", '', SmsService::__send_raw($host, $url, $qs));
    }

    private function __send_raw($host, $url, $qs) {
        $errno = $errstr = '';

        if ($fp = @fsockopen($host, 80, $errno, $errstr, 30)) {
            fputs($fp, "POST $url HTTP/1.0\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "User-Agent: PHP/". phpversion(). "\r\n");
            fputs($fp, "Content-Type:application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-Length: ". strlen($qs)."\r\n");
            fputs($fp, "Connection: close\r\n");
            fputs($fp, "\r\n". $qs);

            $content = '';

            while (!feof($fp))
                $content .= fgets($fp, 1024);

            fclose($fp);

            return $content;
        }

        return null;
    }

    private function __send_portech($user, $pass, $cell, $msg, $caller, $ip) {
        $out = "";

        $fp = fsockopen($ip, 23, $errno, $errstr, 30);
        if (!$fp) {
            return -1;
        }
        sleep(2);
        $cmd = "$user\r";
        fputs($fp, $cmd, strlen($cmd));
        sleep(1);
        
        $cmd = "$pass\r";
        fputs($fp, $cmd, strlen($cmd));
        sleep(1);
        
        $cmd = "module\r"; //inserire il numero sim
        fputs($fp, $cmd, strlen($cmd));
        sleep(2);
        
        $cmd = "ate1\r";
        fputs($fp, $cmd, strlen($cmd));
        sleep(1);
        
        $cmd = "AT+CSCS=\"GSM\"\r";
        fputs($fp, $cmd, strlen($cmd));
        sleep(2);
        
        //Select SMS Message Format... (0=PDU Mode, 1=Text Mode)
        $cmd = "at+cmgf=1\r";
        fputs($fp, $cmd, strlen($cmd));
        $out .= fread($fp, 256);
        sleep(2);
        
        //Send SMS Message...
        $cmd = "at+cmgs=\"$cell\"\r";
        fputs($fp, $cmd, strlen($cmd));
        sleep(2);
        $out .= fread($fp, 256);
        
        //Body...
        $cmd = "$msg\r\x1a"; //Ctrl-Z
        fputs($fp, $cmd, strlen($cmd));

        $res = " ";
        $out = "";
        stream_set_timeout($fp, 5); //5 seconds read timeout
        while ($res != "") {
            $res = fread($fp, 256);
            $out .= $res;
        }
        $tmpsms_number = explode('+CMGS: ', $out);
        $sms_number = explode(' ', $tmpsms_number[1]);
        $actnum = $sms_number[0];
        $actnum = str_replace(" ","",$actnum);
        $actnum = str_replace("\r","",$actnum);
        $actnum = str_replace("\n","",$actnum);
        $actnum = str_replace("\t","",$actnum);
        $actlen= strlen($actnum)-1;
        $actnum = substr($actnum, 0, $actlen);
        fclose($fp);
        
        return $actnum;
    }
}
