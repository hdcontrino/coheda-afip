<?php

namespace CohedaAfip\Base;

use CohedaAfip\Services\Certification;
use CohedaAfip\Services\Support;
use CohedaAfip\Services\Translations;
use Exception;

class FacturandoAfipBase
{
    use Certification;
    use Support;
    use Translations;

    protected const ROOT = '/../../';

    private $invoiceDataRequirements = [
        'CbteFch',
        'CbteTipo',
        'DocNro',
        'DocTipo',
        'ImpIVA',
        'ImpNeto',
        'ImpTotal',
    ];

    public function __construct()
    {
        ini_set("soap.wsdl_cache_enabled", "0");
        $this->base_path = __DIR__ . self::ROOT;

        $this->loadEnv();
        $this->loadCertificates();

        if (!$this->checkCertificates())
            throw new Exception($this->_tr('Certificate error'));
    }

    protected function checkParams($data): object
    {
        if (!is_object($data))
            $data = (object) $data;

        $properties = get_object_vars($data);

        foreach ($this->invoiceDataRequirements as $each) {
            if (!array_key_exists($each, $properties)) {
                throw new Exception(
                    $this->_tr('Missing property of given parameter.', [
                        'name' => $each,
                    ])
                );
            }
        }

        return $data;
    }
}
