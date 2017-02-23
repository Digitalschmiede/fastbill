<?php
/* ******************************************** */
/*   Copyright: ZWEISCHNEIDER DIGITALSCHMIEDE   */
/*         http://www.zweischneider.de          */
/* ******************************************** */

namespace FastBill;

define('FASTBILL_PLUS',         'https://my.fastbill.com/api/1.0/api.php');
define('FASTBILL_AUTOMATIC',    'https://automatic.fastbill.com/api/1.0/api.php');


class FastBill
{
    private $email = '';
    private $apiKey = '';
    private $apiUrl = '';
    private $debug = false;
    private $convert_to_utf8 = false;

    public function __construct($_email, $_apiKey, $_apiUrl = FASTBILL_PLUS)
    {
        if($_email != '' && $_apiKey != '')
        {
            $this->email = $_email;
            $this->apiKey = $_apiKey;
            $this->apiUrl = $_apiUrl;
        }
        else
        {
            return false;
        }
    }

    public function setDebug($_bool = false)
    {
        if($_bool != '')
        {
            $this->debug = $_bool;
        }
        else
        {
            if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Übergabeparameter 1 ist leer!"))); }
            else { return false; }
        }
    }

    public function checkAPICredentials()
    {
        $ret = $this->request(array('SERVICE' => 'customer.get'));

        if(isset($ret['RESPONSE']['ERRORS'])) { return false; }else{ return true; }
    }

    public function setConvertToUTF8($_convert_to_utf8 = false)
    {
        if($_convert_to_utf8 != '')
        {
            $this->convert_to_utf8 = $_convert_to_utf8;
        }
        else
        {
            if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Übergabeparameter 1 ist leer!"))); }
            else { return false; }
        }
    }

    private function convertToUTF8($_array)
    {
        foreach($_array AS $key => $val)
        {
            if(is_array($val))
            {
                $val = $this->convertToUTF8($val);
            }
            else
            {
                $val = utf8_encode($val);
            }
            $_array[$key] = $val;
        }

        return $_array;
    }

    public function request($_data, $_file = NULL)
    {
        if($_data)
        {
            if($this->email != '' && $this->apiKey != '' && $this->apiUrl != '')
            {
                if($this->convert_to_utf8) { $_data = $this->convertToUTF8($_data); }

                $ch = curl_init();

                $data_string = json_encode($_data);

                if($_file != NULL) {
                    if (class_exists('CURLFile')) {
                        $_finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $_curl_file = new CURLFile($_file, finfo_file($_finfo, $_file), substr(strrchr($_file, '/'), 1));
                    }
                    else {$_curl_file = "@".$_file;}
                    $bodyStr = array("document" => $_curl_file, "httpbody" => $data_string);
                }
                else { $bodyStr = array("httpbody" => $data_string); }

                curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('header' => 'Authorization: Basic ' . base64_encode($this->email.':'.$this->apiKey)));
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug ? 1 : 0));

                $exec = curl_exec($ch);

                $result = json_decode($exec,true);

                curl_close($ch);

                return $result;
            }
            else
            {
                if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Email und/oder APIKey und/oder APIURL Fehlen!"))); }
                else { return false; }
            }
        }
        else
        {
            if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Übergabeparameter 1 ist leer!"))); }
            else { return false; }
        }
    }
    
    public function GetAllInvoices() {
        $this->checkAPICredentials();
        $invoices_array = array();
        $yearlist = array(date("Y")-1,date("Y"));
        $monthlist = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12');

        foreach ($yearlist as $year) {
            foreach ($monthlist as $month) {
                $invoices = $this->request(array("SERVICE" => "invoice.get", "FILTER" => array("TYPE" => "outgoing", "YEAR" => $year, "MONTH" => $month), "LIMIT" => 100 ));
                if (!isset($invoices['RESPONSE']['INVOICES'])) {
                    throw new Exception("Cannot get Invoices from Fastbill!");
                }
                foreach ($invoices['RESPONSE']['INVOICES'] as $invoice) {
                    if(!count($invoice) == 0) {
                        $invoices_array[] = $invoice;
                    }
                    if(count($invoice) >= 100) {
                        // READ ME: the limit of the fastbill api at this time
                        // 100 per month per year!
                        throw new Exception("Fastbill return 100 invoices!");
                    } 
                }                
            }
        }
        return $invoices_array;
    }
}
