<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class ChatBotController extends Controller
{
    public function listenToReplies(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        //condition 
        if ($body == 'CASES TOTAL') {
            $this->getCasesTotal($from);
        }elseif ($body == 'DEATHS TOTAL') {
            $this->getDeathsTotal($from);
        }else{
            if (strpos($body, 'CASES') !== false) {   
                $code = substr($body, -2); 
                $this->getCasesByCountry($code, $from);
            }elseif (strpos($body, 'DEATHS') !== false) {
                $code = substr($body, -2); 
                $this->getDeathsByCountry($code, $from);
            }
        }
        return;
    }

    public function sendWhatsAppMessage(string $message, string $recipient)
    {
        $twilio_whatsapp_number = env('TWILIO_WHATSAPP_NUMBER','');
        $account_sid = env("TWILIO_SID",'');
        $auth_token = env("TWILIO_AUTH_TOKEN",'');

        $client = new Client($account_sid, $auth_token);
        return $client->messages->create($recipient, array('from' => "whatsapp:$twilio_whatsapp_number", 'body' => $message));
    }
    
    public function getDeathsTotal($from)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', "https://api.covid19api.com/summary");
            $getResponse = json_decode($response->getBody());
            if ($response->getStatusCode() == 200) {
                $temp = json_encode($getResponse->Global->TotalDeaths);
                $temp = $this->formatNumber($temp);
                $temp = "Total Deaths ". $temp;
                $this->sendWhatsAppMessage($temp, $from);
            }else{
                $this->sendWhatsAppMessage("error else", $from);
            }
        } catch (RequestException $th) {
            $this->sendWhatsAppMessage($th, $from);
        }
        return;
    }

    public function getCasesTotal($from)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', "https://api.covid19api.com/summary");
            $getResponse = json_decode($response->getBody());
            if ($response->getStatusCode() == 200) {
                $temp = json_encode($getResponse->Global->TotalConfirmed - $getResponse->Global->TotalRecovered);
                $temp = $this->formatNumber($temp);
                $temp = "Total Active Cases ". $temp;
                $this->sendWhatsAppMessage($temp, $from);
            }
        } catch (RequestException $th) {
            $this->sendWhatsAppMessage($th, $from);
        }
        return;
    }

    public function getCasesByCountry($code, $from)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', "https://api.covid19api.com/summary");
            $getResponse = json_decode($response->getBody());
            if ($response->getStatusCode() == 200) {
                $temp = ($getResponse->Countries);
                foreach($temp as $values){
                    if ($values->CountryCode == $code){
                        // Total Active Cases by Couuntry
                        $results = $values->TotalConfirmed - $values->TotalRecovered;
                        $results = $this->formatNumber($results);
                        $results = $code. " Active Cases ". $results;
                       $this->sendWhatsAppMessage($results, $from);
                    }
                }
            }
        } catch (RequestException $th) {
            $this->sendWhatsAppMessage($th, $from);
        }
        return;
    }

    public function getDeathsByCountry($code, $from)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', "https://api.covid19api.com/summary");
            $getResponse = json_decode($response->getBody());
            if ($response->getStatusCode() == 200) {
                $temp = ($getResponse->Countries);
                foreach($temp as $values){
                    if ($values->CountryCode == $code){
                        // Total Death by Couuntry
                        $results = $values->TotalDeaths; 
                        $results = $this->formatNumber($results);
                        $results = $code." Deaths ". $results;
                        $this->sendWhatsAppMessage($results, $from);
                    }
                }
            }
        } catch (RequestException $th) {
            $this->sendWhatsAppMessage($th, $from);
        }
        return;
    }

    public function formatNumber($number){
        $results = number_format($number,0,',','.');
        return $results;
    }
}
