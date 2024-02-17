<?php

namespace CohedaAfip;

use CohedaAfip\Base\FacturandoAfipBase;

class FacturandoAfip extends FacturandoAfipBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function emitirFactura($datosFactura)
    {
        $data = $this->checkParams($datosFactura);

        $this->createTRA();
        $this->signTRA();
        $this->callWSAA();
        $this->getSoap();

        return $this->FECAESolicitar($data);
    }
}
