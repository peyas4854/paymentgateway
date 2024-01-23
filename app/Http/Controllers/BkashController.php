<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BkashController extends Controller
{
    private $base_url;
    private $amount = 100;

    public function __construct()
    {
        $this->base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
        //$this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function authHeaders()
    {
        return array(
            'Content-Type:application/json',
            'Authorization:' . Session::get('bkash_token'),
            'X-APP-Key:' . env('BKASH_CHECKOUT_URL_APP_KEY')
        );
    }

    public function curlWithBody($url, $header, $method, $body_data_json)
    {
        $curl = curl_init($this->base_url . $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_data_json);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function grant()
    {
        $header = array(
            'Content-Type:application/json',
            'username:' . env('BKASH_CHECKOUT_URL_USER_NAME'),
            'password:' . env('BKASH_CHECKOUT_URL_PASSWORD')
        );
        $header_data_json = json_encode($header);

        $body_data = array('app_key' => env('BKASH_CHECKOUT_URL_APP_KEY'), 'app_secret' => env('BKASH_CHECKOUT_URL_APP_SECRET'));
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/tokenized/checkout/token/grant', $header, 'POST', $body_data_json);

        $token = json_decode($response)->id_token;

        return $token;
    }

    public function create(Request $request)
    {

        Session::forget('bkash_token');
        $token = $this->grant();

        Session::put('bkash_token', $token);

        $header = $this->authHeaders();


        $body_data = array(
            'mode' => '0011',
            'payerReference' => ' ',
            'callbackURL' => 'http://127.0.0.1:8000/bkash/checkout-url/callback',
            'amount' => $this->amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => "Inv" . Str::random(10) // you can pass here you OrderID
        );
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/tokenized/checkout/create', $header, 'POST', $body_data_json);

        Session::forget('paymentID');
        Session::put('paymentID', json_decode($response)->paymentID);

        return redirect((json_decode($response)->bkashURL));
    }

    public function callback(Request $request)
    {
        dd('callback');

        $allRequest = $request->all();
        if (isset($allRequest['status']) && $allRequest['status'] == 'failure') {
            return view('fail')->with([
                'response' => 'Payment Failure'
            ]);

        } else if (isset($allRequest['status']) && $allRequest['status'] == 'cancel') {
            return view('fail')->with([
                'response' => 'Payment Cancell'
            ]);

        } else {

            $response = $this->execute($allRequest['paymentID']);

            $arr = json_decode($response, true);

            if (array_key_exists("statusCode", $arr) && $arr['statusCode'] != '0000') {
                return view('fail')->with([
                    'statusMessage' => $arr['statusMessage'],
                ]);
            } else if (array_key_exists("message", $arr)) {
                // if Execute Api Failed to response
                sleep(1);
                $queryResponse = $this->query($allRequest['paymentID']);
                return view('success')->with([
                    'response' => $queryResponse
                ]);
            }

            return view('success')->with([
                'response' => $response
            ]);

        }
    }

    public function execute($paymentID)
    {

        $header = $this->authHeaders();

        $body_data = array(
            'paymentID' => $paymentID
        );
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/tokenized/checkout/execute', $header, 'POST', $body_data_json);

        $res_array = json_decode($response, true);

        if (isset($res_array['trxID'])) {
            $trxID = $res_array['trxID'];
            $paymentID = $res_array['paymentID'];
            $amount = $res_array['amount'];
            $invoiceNo = $res_array['merchantInvoiceNumber'];
            $customerMsisdn = $res_array['customerMsisdn'];

            // your database insert operation

        }

        return $response;
    }

}
