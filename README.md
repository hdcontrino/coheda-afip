# Biblioteca para Facturación Electrónica con AFIP

Esta biblioteca proporciona una interfaz simple y fácil de usar para generar facturas electrónicas a través de la API de AFIP (Administración Federal de Ingresos Públicos) en Argentina. 
En esta versión, por el momento, solo se pueden generar facturas. En futuras actualizaciones se añadirán más características, como la capacidad de listar facturas, devolver alguna en particular o anular.

## Servicio

Actualmente sólo se conecta al servicio wsfev1 - R.G. N° 4.291

## Características

- Interfaz sencilla para la generación de facturas electrónicas.
- Integración directa con la API de AFIP para cumplir con los requisitos legales y fiscales.
- Flexibilidad para personalizar y adaptar la facturación electrónica según las necesidades específicas del negocio.
- Compatible con wsfev1 - R.G. N° 4.291

## Requisitos del Sistema

- PHP 7.4 o superior

## Instalación

Con Composer:
```bash
composer require coheda/coheda-afip
```
Sin Composer:
Puedes incorporar esta librería a tus proyectos sin la necesidad de utilizar Composer. Por ejemplo, para proyectos de Scriptcase, simplemente incluye `run.php` en tus archivos.

## Uso

Para comenzar a utilizar la biblioteca, copie sus credenciales de acceso en la carpeta `/cert`.
A continuación, ajuste las variables en `.env` con los valores correspondientes a su conexión.

Luego, simplemente importa la clase `FacturandoAfip` y crea una instancia de ella. A continuación, puedes utilizar los métodos proporcionados para emitir facturas electrónicas a través de la API de AFIP.

```php
use CohedaAfip;

$facturador = new FacturandoAfip();

// Ejemplo: Emitir una factura
$datosFactura = [
    'CbteFch'  => string,
    'CbteTipo' => int,
    'DocNro'   => int,
    'DocTipo'  => int,
    'ImpIVA'   => double,
    'ImpNeto'  => double,
    'ImpTotal' => double,
];

$facturador->emitirFactura($datosFactura);
```

Con run.php:
`run.php` proporciona un helper para cada método. En esta versión únicamente está disponible `emitirFactura()`.

```php
// Ejemplo: Emitir una factura
$facturador = emitirFactura(
    string $CbteFch, 
    int $CbteTipo, 
    int $DocNro, 
    int $DocTipo, 
    double $ImpIVA, 
    double $ImpNeto, 
    double $ImpTotal
);
```

Respuesta: `FECAEDetResponse`

## Contribuciones

¡Las contribuciones son bienvenidas! Si tienes sugerencias, ideas o encuentras algún problema, no dudes en abrir un issue o enviar un pull request.

## Licencia

Este proyecto está bajo la [Licencia MIT](LICENSE).
