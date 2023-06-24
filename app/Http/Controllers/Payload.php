<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;

class Payload extends Controller
{
    const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    const ID_MERCHANT_CATEGORY_CODE = '52';
    const ID_TRANSACTION_CURRENCY = '53';
    const ID_TRANSACTION_AMOUNT = '54';
    const ID_COUNTRY_CODE = '58';
    const ID_MERCHANT_NAME = '59';
    const ID_MERCHANT_CITY = '60';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    const ID_CRC16 = '63';

    function PixGenerate(HttpRequest $request)
    {
        try {
            $payload = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01') .
                $this->getMerchantAccountInformation($request['key'], $request['description']) .
                $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000') .
                $this->getValue(self::ID_TRANSACTION_CURRENCY, '986') .
                $this->getValue(self::ID_TRANSACTION_AMOUNT, $request['amount']) .
                $this->getValue(self::ID_COUNTRY_CODE, 'BR') .
                $this->getValue(self::ID_MERCHANT_NAME, $request['merchantName']) .
                $this->getValue(self::ID_MERCHANT_CITY, $request['merchantCity']) .
                $this->getAdditionalDataFieldTemplate($request['txID']);

            $data = [
                "pix" => $payload . $this->getCRC16($payload)
            ];

            return response()->json($data, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th], 500);
        }
    }

    private function getValue($id, $value)
    {
        $size = str_pad(strlen($value), 2, 0, STR_PAD_LEFT);
        return $id . $size . $value;
    }

    private function getMerchantAccountInformation($key, $description)
    {
        // Bank Dominion
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');

        // PIX KEY
        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $key);

        // Payment description
        $description = strlen($description) ? $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $description) : '';

        // Complete value of account
        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }

    private function getAdditionalDataFieldTemplate($txID)
    {
        $txid = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $txID);

        // Return complete value
        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txid);
    }

    private function getCRC16($payload)
    {
        //ADICIONA DADOS GERAIS NO PAYLOAD
        $payload .= self::ID_CRC16 . '04';

        //DADOS DEFINIDOS PELO BACEN
        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        //CHECKSUM
        if (($length = strlen($payload)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $resultado ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }

        //RETORNA CÓDIGO CRC16 DE 4 CARACTERES
        return self::ID_CRC16 . '04' . strtoupper(dechex($resultado));
    }
}
