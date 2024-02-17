<?php

namespace CohedaAfip\Services;

use Exception;
use SimpleXMLElement;
use SoapClient;

trait Certification
{
    protected $certificate;
    protected $private_key;
    protected $wsdl_wsaa;
    protected $wsdl_wsfex;

    protected function loadCertificates()
    {
        $certificate = $this->path($this->env('CERTIFICATE'));
        $private_key = $this->path($this->env('PRIVATE_KEY'));
        $wsdl_wsaa   = $this->path($this->env('WSDL_WSAA'));
        $wsdl_wsfex  = $this->path($this->env('WSDL_WSFEX'));

        $this->certificate = file_exists($certificate);
        $this->private_key = file_exists($private_key);
        $this->wsdl_wsaa   = file_exists($wsdl_wsaa);
        $this->wsdl_wsfex  = file_exists($wsdl_wsfex);
    }

    protected function checkCertificates(): bool
    {
        return
            $this->certificate &&
            $this->private_key &&
            $this->wsdl_wsaa   &&
            $this->wsdl_wsfex  &&
            true;
    }

    protected function createTRA()
    {
        $cn = $this->env('COMMON_NAME');
        $sn = $this->env('SERIAL_NUMBER');
        $o  = $this->env('ORGANIZATION');
        $c  = $this->env('COUNTRY');
        $destination = "cn=$cn,o=$o,c=$c,serialNumber=$sn";

        $TRA = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
                '<loginTicketRequest version="1.0">' .
                '</loginTicketRequest>'
        );
        $TRA->addChild('header');

        $TRA->header->addChild('destination', $destination);
        $TRA->header->addChild('uniqueId', date('U'));
        $TRA->header->addChild('generationTime', date('c', date('U') - 60));
        $TRA->header->addChild('expirationTime', date('c', date('U') + 60));
        $TRA->addChild('service', $this->env('SERVICE'));
        $TRA->asXML($this->path('tmp/TRA.xml'));

        if (!file_exists($this->path('tmp/TRA.xml')))
            throw new Exception(
                $this->_tr('Error creating', [
                    'name' => 'TRA.xml',
                ])
            );
    }

    protected function signTRA(): void
    {
        $xml = $this->path('tmp/TRA.xml');
        $tmp = $this->path('ws/TRA.cms');
        $cer = 'file://' . $this->path($this->env('CERTIFICATE'));
        $key = array('file://' . $this->path($this->env('PRIVATE_KEY')), $this->env('PASSPHRASE'));

        if (!openssl_pkcs7_sign($xml, $tmp, $cer, $key, array(), !PKCS7_DETACHED))
            throw new Exception($this->_tr('Error generating signature'));
    }

    protected function callWSAA(): void
    {
        $options = [
            'soap_version' => SOAP_1_2,
            'encoding'     => 'ISO-8859-1',
            'location'       => $this->env('URL_WSAA'),
            'trace'          => 1,
            'exceptions'     => 0
        ];

        if (!file_exists($this->path('ws/TRA.cms')))
            throw new Exception(
                $this->_tr('Missing file', [
                    'name' => 'TRA.cms',
                ])
            );

        $cms = file_get_contents($this->path('ws/TRA.cms'));
        $cms = preg_split("|\n\n|", $cms);
        $wsdl = $this->path($this->env('WSDL_WSAA'));
        $client = new SoapClient($wsdl, $options);
        $results = $client->loginCms(array('in0' => $cms[1]));

        if (is_soap_fault($results)) {
            throw new Exception(
                $this->_tr('SOAP Error', [
                    'code' => $results->faultcode,
                    'message' => $results->faultstring,
                ])
            );
        }

        if (!file_put_contents($this->path('tmp/TA.xml'), $results->loginCmsReturn))
            throw new Exception(
                $this->_tr('Error creating', [
                    'name' => 'TA.xml',
                ])
            );

        if (!file_exists($this->path('tmp/TA.xml')))
            throw new Exception(
                $this->_tr('Failed to open', [
                    'name' => 'TA.xml',
                ])
            );
    }
}
