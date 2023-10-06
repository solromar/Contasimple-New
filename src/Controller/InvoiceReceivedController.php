<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceReceivedController extends AbstractController
{
    #[Route('/invoice/received', name: 'app_invoice_received')]
    public function authAndGetInvoicesReceived()
    {
        $authorizationKey = 'f9670a835eb947ffa7efd9e2df8b3348';
        // -------------------------------------------Obtener el token de Autorizacion -----------------------------------------------------//
        $client = new Client();
        $response = $client->request('POST', 'https://api.contasimple.com/api/v2/oauth/token', [
            'form_params' => [
                'key' => $authorizationKey,
                'grant_type' => 'authentication_key',
            ],
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $accessToken = $data['access_token'];
        // -----------------------------------------------  Obtener las Facturas Emitidas -------------------------------------------------------//
        $response = $client->request('GET', 'https://api.contasimple.com/api/v2/accounting/2023-4T/invoices/received', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
        $invoices = json_decode($response->getBody()->getContents(), true);
        // --------------------------------------- Recorrer las facturas y asignar variables ---------------------------------------------------------//        
        $cantidadTotalFacturas = count($invoices['data']);
        // Crear un array que contendrá tanto la cantidad total de facturas como las facturas individuales
        $respuesta = [
            'CANTIDAD TOTAL DE FACTURAS' => $cantidadTotalFacturas,
            'FACTURAS RECIBIDAS EN EL PERIODO' => [],
        ];

        foreach ($invoices['data'] as $invoice) {
            // Variables para emisor de la factura //
            $issuer = $invoice['issuer'];
            $issuerAdress = $issuer['address'] . ', ' . $issuer['city'] . ', ' . $issuer['province'] . ', ' . $issuer['country'] . ', ' . $issuer['postalCode'];
            $issuerContact = $issuer['phone'] . ', ' . $issuer['email'] . ', ' . $issuer['url'];
            $issuerDetails = [
                'Razón social' => $issuer['organization'],
                'NIF' => $issuer['nif'],
                'Dirección' => $issuerAdress,
                'Datos de contacto' => $issuerContact,
            ];
            // Variables para receptor de la factura //
            $target = $invoice['target'];
            $targetAdress = $target['address'] . ', ' . $target['city'] . ', ' . $target['province'] . ', ' . $target['country'] . ', ' . $target['postalCode'];
            $targetContact = $target['phone'] . ', ' . $target['email'] . ', ' . $target['url'];
            $targetDetails = [
                'Razón social' => $target['organization'],
                'NIF' => $target['nif'],
                'Dirección' => $targetAdress,
                'Datos de contacto' => $targetContact,
            ];

            $factura = [
                'ID de Factura' => $invoice['id'],
                'Periodo' => $invoice['period'],
                'Razon Social del Cliente' => $invoice['entityString'],
                'Tipo de Factura' => $invoice['type'],
                'Descripcion' => $invoice['invoiceClassDescription'],
                'Fecha de Emision' => $invoice['invoiceDate'],
                'Fecha de Vencimiento' => $invoice['expirationDate'],
                'Número de Factura' => $invoice['number'],
                'Estado de la Factura' => $invoice['status'],
                'Importe de la retención' => number_format($invoice['retentionAmount'], 2, '.', ','),
                'Porcentaje de la retención' => number_format($invoice['retentionPercentage'], 2, '.', ','),
                'Base total imponible' => number_format($invoice['totalTaxableAmount'], 2, '.', ','),
                'Importe total de impuestos' => number_format($invoice['totalVatAmount'], 2, '.', ','),
                'Importe total de la factura ' => number_format($invoice['totalAmount'], 2, '.', ','),
                'Importe total retenido' => number_format($invoice['totalReAmount'], 2, '.', ','),
                'Importe total Pagado' => number_format($invoice['totalPayedAmount'], 2, '.', ','),
                'Importe Total Pendiente de Pago' => number_format($invoice['totalAmountPerPay'], 2, '.', ','),
                'Importe total computable' => number_format($invoice['totalComputableAmount'], 2, '.', ','),
                'Importe total computable de impuestos' => number_format($invoice['totalComputableAmountForVAT'], 2, '.', ','),
                'Porcentaje computable' => number_format($invoice['computablePercentage'], 2, '.', ','),
                'Porcentaje computable de impuestos' => number_format($invoice['computablePercentageVAT'], 2, '.', ','),
                'Factura rectificada ' => number_format($invoice['isRectificationInvoice'], 2, '.', ','),
                'ID de la factura rectificada' => number_format($invoice['rectifiesInvoiceId'], 2, '.', ','),
                'DETALLES DEL EMISOR' => $issuerDetails,
                'DETALLES DEL RECEPTOR' => $targetDetails,
            ];
            //-------------------------------------------- DETALLE DE PAGOS -----------------------------------------------------
            foreach ($invoice['payments'] as $payment) {
                $payments = [
                    'Monto Pagado' => number_format($payment['amount'], 2, '.', ','),
                    'Fecha de Pago' => $payment['date'],
                    'Forma de pago' => $payment['paymentMethodType'],
                    'Metodo de pago' => $payment['paymentMethodName'],
                    'Importe conciliado' => $payment['reconciledAmount'],
                    'Importe pendiente a conciliar' => $payment['pendingAmountToReconcile'],
                ];
                // Agregar el pago al array de pagos de la factura
                $factura['DETALLE DE PAGOS'][] = $payments;
            }
            //------------------------------------------------- PRODUCTOS ---------------------------------------------------------- 
            foreach ($invoice['lines'] as $product) {
                $producto = [
                    'Nombre' => $product['concept'],
                    'Descripción' => $product['detailedDescription'],
                    'Unidades' => number_format($product['quantity']),
                    'Precio Unitario' => number_format($product['unitTaxableAmount'], 2, '.', ','),
                    'Descuento' => number_format($product['discountPercentage'], 2, '.', ','),
                    'Base imponible' => $product['totalTaxableAmount'],
                    'porcentaje de impuesto' => number_format($product['vatPercentage'], 2, '.', ','),
                    'Importe del impuesto' => $product['vatAmount'],
                    'Porcentaje de retención ' => $product['rePercentage'],
                    'Importe de retención' => number_format($product['reAmount'], 2, '.', ','),
                ];
                // Agregar el producto al array de productos de la factura
                $factura['DETALLE DE PRODUCTOS'][] = $producto;
            }
            // Agregar la factura procesada al array
            $respuesta['FACTURAS RECIBIDAS EN EL PERIODO'][] = $factura;
        }
        return new JsonResponse($respuesta);
    }
}
