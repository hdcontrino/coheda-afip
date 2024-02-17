<?php

require_once 'src/autoload.php';

use CohedaAfip\FacturandoAfip;

function emitirFactura($CbteFch, $CbteTipo, $DocNro, $DocTipo, $ImpIVA, $ImpNeto, $ImpTotal)
{

    $reflection = new ReflectionFunction(__FUNCTION__);
    $facturando = new FacturandoAfip;
    $args       = func_get_args();
    $names      = array_column($reflection->getParameters(), 'name');
    $args       = array_pad($args, count($names), null);
    $params     = (object) array_combine($names, $args);

    return $facturando->emitirFactura($params);
}
