<?php

namespace CohedaAfip\Services;

use Exception;
use SoapClient;

trait Support
{
    protected $base_path;
    protected $variables;

    protected $client;
    protected $token;
    protected $sign;

    protected function loadEnv(): void
    {
        $this->variables = parse_ini_file($this->path('.env'));
    }

    protected function env(string $env): string
    {
        return $this->variables[$env];
    }

    protected function path(string $path = '/'): string
    {
        return realpath($this->base_path . $path);
    }

    protected function getSoap(): void
    {
        $this->client = new SoapClient(
            $this->path($this->env('WSDL_WSFEX')),
            array(
                'soap_version' => SOAP_1_2,
                'location'     => $this->env('URL_WSFEX'),
                'exceptions'   => 0,
                'encoding'     => 'ISO-8859-1',
                'features'     => SOAP_USE_XSI_ARRAY_TYPE + SOAP_SINGLE_ELEMENT_ARRAYS,
                'trace'        => 1,
                'stream_context' => stream_context_create(array('ssl' => array('ciphers' => 'AES256-SHA')))
            )
        );

        $TA = simplexml_load_file($this->path('tmp/TA.xml'));
        $this->token = $TA->credentials->token;
        $this->sign = $TA->credentials->sign;
    }

    protected function checkErrors($results, $method)
    {
        $Y = $method . 'Result';
        $X = $results->$Y;

        if ($this->env('LOG_XMLS')) {
            $req = $this->path("tmp/request-$method.xml");
            $resp = $this->path("tmp/response-$method.xml");
            file_put_contents($req, $this->client->__getLastRequest());
            file_put_contents($resp, $this->client->__getLastResponse());
        }

        if (is_soap_fault($results)) {
            $error = "Check Error $results->faultcode: $results->faultstring";
            throw new Exception($error);
        }

        if (isset($X->Errors)) {
            $error = '';
            foreach ($X->Errors->Err as $E) {
                $error = "$method Error $E->Code: $E->Msg";
            }
            throw new Exception($error);
        }
    }

    protected function FECAESolicitar($data)
    {
        $CbteNum = $this->FECompUltimoAutorizado($data->CbteTipo);
        $body    = $this->getFECAEBody($data, $CbteNum + 1);
        $results = $this->client->FECAESolicitar($body);

        $this->checkErrors($results, 'FECAESolicitar');

        $C = $results->FECAESolicitarResult->FeCabResp;
        $D = $results->FECAESolicitarResult->FeDetResp;

        return $D->FECAEDetResponse;
    }

    protected function FECompUltimoAutorizado($CbteTipo)
    {
        $results = $this->client->FECompUltimoAutorizado([
            'Auth' => [
                'Token' => $this->token,
                'Sign'  => $this->sign,
                'Cuit'  => $this->env('CUIT'),
            ],
            'PtoVta'   => $this->env('PV'),
            'CbteTipo' => $CbteTipo,
        ]);

        $this->checkErrors($results, 'FECompUltimoAutorizado');
        $X = $results->FECompUltimoAutorizadoResult;

        return $X->CbteNro;
    }

    protected function getFECAEBody(object $data, int $CbteNum): array
    {
        $CbteFch  = date('Ymd', strtotime($data->CbteFch));
        $ImpIVA   = round($data->ImpIVA, 2);
        $ImpNeto  = round($data->ImpNeto, 2);
        $ImpTotal = round($data->ImpTotal, 2);

        $FeDetReq = [
            'CbteDesde'  => $CbteNum,
            'CbteFch'    => $CbteFch,
            'CbteHasta'  => $CbteNum,
            'Concepto'   => 1,
            'DocNro'     => $data->DocNro,
            'DocTipo'    => $data->DocTipo,
            'ImpIVA'     => $ImpIVA,
            'ImpNeto'    => $ImpNeto,
            'ImpOpEx'    => 0,
            'ImpTotal'   => $ImpTotal,
            'ImpTotConc' => 0,
            'ImpTrib'    => 0,
            'Iva'        => [
                'AlicIva' => [
                    [
                        'Id'      => $ImpIVA > 0 ? 5 : 3,
                        'BaseImp' => $ImpNeto,
                        'Importe' => $ImpIVA,
                    ]
                ],
            ],
            'MonCotiz'  => 1,
            'MonId'     => 'PES',
        ];

        if ($data->CbteTipo == 2 || $data->CbteTipo == 3) {
            $FeDetReq['CbtesAsoc'] = [
                'CbteAsoc' => [
                    [
                        'CbteFch' => $CbteFch,
                        'Cuit'    => $data->DocNro,
                        'Nro'     => $data->CbteFch,
                        'PtoVta'  => $this->env('PV'),
                        'Tipo'    => 1,
                    ]
                ]
            ];
        }

        return [
            'Auth' => [
                'Token' => $this->token,
                'Sign'  => $this->sign,
                'Cuit'  => $this->env('CUIT'),
            ],
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg'  => 1,
                    'PtoVta'   => $this->env('PV'),
                    'CbteTipo' => $data->CbteTipo,
                ],
                'FeDetReq' => [$FeDetReq],
            ],
        ];
    }
}
