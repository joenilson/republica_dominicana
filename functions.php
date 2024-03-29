<?php
/*
 * Copyright (C) 2018 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!function_exists('fs_tipos_id_fiscal')) {

    /**
    * Devuelve la lista de identificadores fiscales.
    * @return array
     */
    function fs_tipos_id_fiscal()
    {
        return array(FS_CIFNIF, 'Pasaporte', 'Cédula', 'RNC');
    }
}

/**
 * Se debe migrar aquí la funcionalidad de generar el ncf para insertarlo en
 * el campo numero2
 * @return array
 */
if (!function_exists('generar_numero2_old')) {
    function generar_numero2_old($cliente, $codalmacen, $codpago, $rectificativa = false, $terminal = false, $json = false)
    {
        $empresa = new empresa();
        $ncf_numero = array();
        $ncf_rango = new ncf_rango();
        /*
        * Verificación de disponibilidad del Número de NCF para República Dominicana
        */
        //Obtenemos el tipo de comprobante a generar para el cliente, si no existe le asignamos tipo 02 por defecto
        $tipo_comprobante = ($rectificativa) ? '04' : ncf_tipo_comprobante($empresa->id, $cliente);
        //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
        if ($terminal) {
            $numero_ncf = $ncf_rango->generate_terminal(
                $empresa->id,
                $codalmacen,
                $tipo_comprobante,
                $codpago,
                $terminal->area_impresion
            );
        } else {
            $numero_ncf = $ncf_rango->generate_old($empresa->id, $codalmacen, $tipo_comprobante, $codpago);
        }

        if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
            $ncf_numero = $numero_ncf['NCF'];
        }

        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'ncf_numero' => $ncf_numero,
                'tipo_comprobante' => $tipo_comprobante,
                'terminal' => $terminal,
                'cliente' => $cliente
            ), JSON_THROW_ON_ERROR);
        } else {
            return array($ncf_numero, null);
        }
    }
}

/**
 * Se debe migrar aquí la funcionalidad de generar el ncf para insertarlo en
 * el campo numero2
 * @param string  $cliente
 * @param string  $codalmacen
 * @param string  $codpago
 * @param boolean $rectificativa
 * @param boolean $terminal
 * @param boolean $json
 * @return string
 */
if (!function_exists('generar_numero2')) {
    function generar_numero2($cliente, $codalmacen, $codpago, $rectificativa = false, $terminal = false, $json = false)
    {
        $empresa = new empresa();
        $ncf_numero = array();
        $ncf_rango = new ncf_rango();
        // Verificación de disponibilidad del Número de NCF para República Dominicana
        //Obtenemos el tipo de comprobante a generar para el cliente, si no existe le asignamos tipo 02 por defecto
        $tipo_comprobante = ($rectificativa) ? '04' : ncf_tipo_comprobante($empresa->id, $cliente);
        //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
        $numero_ncf = $ncf_rango->generate($empresa->id, $codalmacen, $tipo_comprobante, $codpago);
        if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
            $ncf_numero = $numero_ncf['NCF'];
        }

        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'ncf_numero' => $ncf_numero,
                'tipo_comprobante' => $tipo_comprobante,
                'terminal' => $terminal,
                'cliente' => $cliente,
                'vencimiento' => $numero_ncf['VENCIMIENTO']
            ), JSON_THROW_ON_ERROR);
        } else {
            return array($ncf_numero, $numero_ncf['VENCIMIENTO']);
        }
    }
}

if (!function_exists('generar_numero2_proveedor')) {
    function generar_numero2_proveedor(
        $codproveedor,
        $codalmacen,
        $codpago,
        $fecha,
        $facturarect,
        $terminal = false,
        $json = false
    )
    {
        $empresa = new empresa();
        $prov = new proveedor();
        $proveedor = $prov->get($codproveedor);
        //Si el proveedor es una persona física le generamos el comprobante fiscal para personas físicas
        $tipo_comprobante = verificar_proveedor($proveedor, $facturarect);
        if ($tipo_comprobante !== '') {
            $ncf_numero = crear_ncf_compra($empresa->id, $codalmacen, $tipo_comprobante, $codpago, $fecha);
        } else {
            $ncf_numero = (\filter_input(INPUT_POST, 'numproveedor'))
                    ?: \filter_input(INPUT_GET, 'numproveedor');
        }

        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(array('ncf_numero' => $ncf_numero,
                'tipo_comprobante' => $tipo_comprobante,
                'terminal' => $terminal,
                'proveedor' => $codproveedor), JSON_THROW_ON_ERROR);
        } else {
            return $ncf_numero;
        }
    }
}

/**
 * Verify comprobant type
 * @return int|null
 */
if (!function_exists('verificar_proveedor')) {
    function verificar_proveedor($proveedor, $facturarect)
    {
        $proveedor_informal = \filter_input(INPUT_POST, 'proveedor_informal');
        $gastos_menores = \filter_input(INPUT_POST, 'gastos_menores');
        $tipo_comprobante = '';
        if ($proveedor_informal || $proveedor->personafisica === true) {
            $tipo_comprobante = 11;
        } elseif ($gastos_menores) {
            $tipo_comprobante = 13;
        } elseif ($facturarect) {
            $tipo_comprobante = '';
        }
        return $tipo_comprobante;
    }
}
/**
 * Función para Buscar NCF en la tabla de ventas
 * @param integer $idempresa
 * @param integer $documento
 * @return boolean
 */
if (!function_exists('buscar_ncf')) {
    function buscar_ncf($idempresa, $documento)
    {
        $ncf_factura = new ncf_ventas();
        $registroFactura = $ncf_factura->get_ncf($idempresa, $documento->idfactura, $documento->codcliente);
        $registroNCF = $ncf_factura->get($idempresa, $documento->numero2);
        if (!empty($registroNCF) && ($registroFactura->ncf === $documento->numero2)) {
            return true;
        }
        return false;
    }
}
/**
 * @param int $empresa_id
 * @param string $codalmacen
 * @param string $tipo_comprobante
 * @param string $codpago
 * @param date $fecha
 * @return string
 */
if (!function_exists('crear_ncf_compra')) {
    function crear_ncf_compra($empresa_id, $codalmacen, $tipo_comprobante, $codpago, $fecha)
    {
        $ncf_numero = '';
        $ncf_rango = new ncf_rango();
        $fs_log = new fs_core_log();
        $funcion_generate = (\strtotime($fecha) < (\strtotime('01-05-2018'))) ? 'generate_old' : 'generate';
        $ncf = $ncf_rango->$funcion_generate($empresa_id, $codalmacen, $tipo_comprobante, $codpago);
        if ($ncf['NCF'] !== 'NO_DISPONIBLE') {
            $ncf_numero = $ncf['NCF'];
        } else {
            $fs_log->new_error('Es una Persona Física pero no hay un <a href="' . FS_PATH .
                'index.php?page=ncf'.'" target="_blank">Rango de NCF</a> para Proveedores Informales (Tipo NCF 11), ' .
                'debe corregir esta información.');
        }
        return $ncf_numero;
    }
}

/**
 * Guardar información de NCF
 * @param integer          $idempresa
 * @param \factura_cliente $factura
 * @param string           $tipo_comprobante
 * @param string           $numero_ncf
 * @param string           $motivo
 * @return void
 */
function guardar_ncf_old($idempresa, $factura, $tipo_comprobante, $numero_ncf, $motivo = false)
{
    $ncf_rango = new ncf_rango();
    $usuario_login = trim(\filter_input(INPUT_COOKIE, 'user'));
    $usuario = ($usuario_login === '') ? 'cron' : $usuario_login;
    if (!empty($numero_ncf)) {
        $ncf_factura = new ncf_ventas();
        $ncf_factura->idempresa = $idempresa;
        $ncf_factura->codalmacen = $factura->codalmacen;
        $ncf_factura->entidad = $factura->codcliente;
        $ncf_factura->cifnif = $factura->cifnif;
        $ncf_factura->documento = $factura->idfactura;
        $ncf_factura->fecha = $factura->fecha;
        $ncf_factura->tipo_comprobante = $tipo_comprobante;
        $ncf_factura->area_impresion = substr($numero_ncf, 6, 3);
        $ncf_factura->ncf = $numero_ncf;
        $ncf_factura->usuario_creacion = $usuario;
        $ncf_factura->fecha_creacion = \date('d-m-Y H:i:s');
        $ncf_factura->usuario_modificacion = $usuario;
        $ncf_factura->fecha_modificacion = \date('d-m-Y H:i:s');
        $ncf_factura->estado = true;
        if ($factura->idfacturarect) {
            $ncf_orig = new ncf_ventas();
            $val_ncf = $ncf_orig->get_ncf($idempresa, $factura->idfacturarect, $factura->codcliente);
            $ncf_factura->documento_modifica = $factura->idfacturarect;
            $ncf_factura->ncf_modifica = $val_ncf->ncf;
            $ncf_factura->motivo = $motivo;
        }
        if ($ncf_factura->save()) {
            $solicitud = $ncf_rango->get_solicitud_old($idempresa, $factura->codalmacen, $numero_ncf);
            $ncf_rango->update_old(
                $ncf_factura->idempresa,
                $ncf_factura->codalmacen,
                $solicitud,
                $numero_ncf,
                $usuario
            );
        } else {
            $numero_ncf = '';
        }

        $factura->numero2 = $numero_ncf;
        $factura->save();
    }
}

/**
 * Guardar información de NCF
 * @param integer          $idempresa
 * @param \factura_cliente $factura
 * @param string           $tipo_comprobante
 * @param string           $numero_ncf
 * @param string           $motivo
 * @return void
 */
function guardar_ncf($idempresa, &$factura, $tipo_comprobante, $numero_ncf, $motivo = false)
{
    $ncf_rango = new ncf_rango();
    $ncf_detalle_tipo_pago = new ncf_detalle_tipo_pagos();
    $tipo_pago = $ncf_detalle_tipo_pago->get_codigo($factura->codpago);
    $usuario_login = trim(\filter_input(INPUT_COOKIE, 'user'));
    $usuario = ($usuario_login === '') ? 'cron' : $usuario_login;
    if (!empty($numero_ncf)) {
        $ncf_factura = new ncf_ventas();
        cabecera_ncf_ventas($idempresa, $factura, $tipo_comprobante, $tipo_pago, $numero_ncf, $usuario, $ncf_factura);
        if ($factura->idfacturarect) {
            $ncf_orig = new ncf_ventas();
            $val_ncf = $ncf_orig->get_ncf($idempresa, $factura->idfacturarect, $factura->codcliente);
            $ncf_factura->documento_modifica = $factura->idfacturarect;
            $ncf_factura->ncf_modifica = $val_ncf->ncf;
            $ncf_factura->motivo = $motivo;
        }

        if ($ncf_factura->save()) {
            $solicitud = $ncf_rango->get_solicitud($idempresa, $factura->codalmacen, $numero_ncf);
            $ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $solicitud, $numero_ncf, $usuario);
        } else {
            $numero_ncf = '';
        }

        $factura->numero2 = $numero_ncf;
        $factura->save();
    }
}

function cabecera_ncf_ventas($idempresa, $factura, $tipo_comprobante, $tipo_pago, $numero_ncf, $usuario, &$ncf_factura)
{
    $ncf_factura->idempresa = $idempresa;
    $ncf_factura->codalmacen = $factura->codalmacen;
    $ncf_factura->entidad = $factura->codcliente;
    $ncf_factura->cifnif = $factura->cifnif;
    $ncf_factura->documento = $factura->idfactura;
    $ncf_factura->fecha = $factura->fecha;
    $ncf_factura->fecha_vencimiento = $factura->fecha_vencimiento;
    $ncf_factura->tipo_comprobante = $tipo_comprobante;
    $ncf_factura->tipo_ingreso = $factura->tipo_ingreso;
    $ncf_factura->tipo_pago = (!empty($tipo_pago)) ? $tipo_pago : '17';
    $ncf_factura->area_impresion = '';
    $ncf_factura->ncf = $numero_ncf;
    $ncf_factura->usuario_creacion = $usuario;
    $ncf_factura->fecha_creacion = \date('d-m-Y H:i:s');
    $ncf_factura->usuario_modificacion = $usuario;
    $ncf_factura->fecha_modificacion = \date('d-m-Y H:i:s');
    $ncf_factura->estado = true;
}

function guardar_ncf_compras($empresa_id, $documento, $documento_modifica, $tipo_compra, $tipo_pago, $usuario)
{
    $ncf_compras = new ncf_compras();
    cabecera_ncf_compras(
        $empresa_id,
        $documento,
        $documento_modifica,
        $tipo_compra,
        $tipo_pago,
        $usuario,
        $ncf_compras
    );
    $tipo_proveedor = tipo_proveedor_ncf($documento->codproveedor);
    sumar_lineas_compras($documento, $ncf_compras, $tipo_proveedor);
    $ncf_compras->save();
}

function cabecera_ncf_compras(
    $empresa_id,
    $documento,
    $documento_modifica,
    $tipo_compra,
    $tipo_pago,
    $usuario,
    &$ncf_compras
)
{
    $ncf_compras->idempresa = $empresa_id;
    $ncf_compras->entidad = $documento->codproveedor;
    $ncf_compras->codalmacen = $documento->codalmacen;
    $ncf_compras->fecha = $documento->fecha;
    $ncf_compras->documento = $documento->idfactura;
    $ncf_compras->documento_modifica = $documento->idfacturarect;
    $ncf_compras->cifnif = $documento->cifnif;
    $ncf_compras->ncf = $documento->numproveedor;
    $ncf_compras->ncf_modifica = $documento_modifica->numproveedor ?? null;
    $ncf_compras->tipo_comprobante = \substr($documento->numproveedor, -10, 2);
    $ncf_compras->tipo_compra = $tipo_compra->codigo;
    $ncf_compras->tipo_pago = $tipo_pago;
    $ncf_compras->estado = true;
    $ncf_compras->usuario_creacion = $usuario;
    $ncf_compras->fecha_creacion = \date('Y-m-d H:i:s');
    $ncf_compras->usuario_modificacion = $usuario;
    $ncf_compras->fecha_modificacion = \date('Y-m-d H:i:s');
}

function sumar_lineas_compras($documento, &$ncf_compras, $tipo_proveedor)
{
    $ncf_compras->total_bienes = 0;
    $ncf_compras->total_servicios = 0;
    if ($tipo_proveedor !== 'ACREEDOR') {
        $art0 = new rd_articulo_clasificacion();
        foreach ($documento->get_lineas() as $linea) {
            if ($linea->referencia) {
                $art = $art0->get_referencia($linea->referencia);
                sumar_tipos_compras($art, $linea, $ncf_compras->total_bienes, $ncf_compras->total_servicios);
            } else {
                $ncf_compras->total_servicios += $linea->pvptotal;
            }
        }
    } else {
        $ncf_compras->total_servicios = $documento->neto;
    }
}

function sumar_tipos_compras($art, $linea, &$total_bienes, &$total_servicios)
{
    if ($art->tipo_articulo === '01') {
        $total_bienes += $linea->pvptotal;
    } elseif ($art->tipo_articulo === '02') {
        $total_servicios += $linea->pvptotal;
    } else {
        $total_servicios += $linea->pvptotal;
    }
}

function ncf_tipo_comprobante($idempresa, $codigo_entidad, $tipo_entidad = 'CLI')
{
    $net0 = new ncf_entidad_tipo();
    $usuario_login = trim(\filter_input(INPUT_COOKIE, 'user'));
    $usuario = ($usuario_login === '') ? 'cron' : $usuario_login;
    $tipo_comprobante = '02';
    $tipo_comprobante_d = $net0->get($idempresa, $codigo_entidad, $tipo_entidad);
    if ($tipo_comprobante_d) {
        $tipo_comprobante = $tipo_comprobante_d->tipo_comprobante;
    } else {
        $net0 = new ncf_entidad_tipo();
        $net0->entidad = $codigo_entidad;
        $net0->estado = true;
        $net0->fecha_creacion = \date('Y-m-d H:i:s');
        $net0->usuario_creacion = $usuario;
        $net0->idempresa = $idempresa;
        $net0->tipo_comprobante = $tipo_comprobante;
        $net0->tipo_entidad = $tipo_entidad;
        $net0->save();
    }
    return $tipo_comprobante;
}

function tipo_proveedor_ncf($codproveedor)
{
    $tipo = '';
    $prov = new proveedor();
    $proveedor = $prov->get($codproveedor);
    if ($proveedor->acreedor) {
        $tipo = 'ACREEDOR';
    } elseif ($proveedor->personafisica) {
        $tipo = 'INFORMAL';
    }
    return $tipo;
}

/**
 * @param string|object $cliente
 * @param string $tipo_comprobante
 * @param boolean|object $terminal
 * @param boolean $json
 * @return array|type|object
 */
function generar_comprobante_fiscal($cliente, $tipo_comprobante, $codalmacen, $terminal = false, $json = false)
{
    $ncf_numero = array();
    $empresa = new empresa();
    $ncf_rango = new ncf_rango();
    if ($terminal) {
        $numero_ncf = $ncf_rango->generate_terminal(
            $empresa->id,
            $codalmacen,
            $tipo_comprobante,
            $cliente->codpago,
            $terminal->area_impresion
        );
    } else {
        $numero_ncf = $ncf_rango->generate($empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago);
    }

    if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
        $ncf_numero = $numero_ncf['NCF'];
    }

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(array(
            'ncf_numero' => $ncf_numero,
            'tipo_comprobante' => $tipo_comprobante,
            'terminal' => $terminal,
            'cliente' => $cliente
        ), JSON_THROW_ON_ERROR);
    } else {
        return $numero_ncf;
    }
}

if (!function_exists('fs_generar_numero2')) {

    /**
     * Asigna el número de NCF al documento si es una factura
     * en caso ser un pedido u otro documento solo escribe el valor enviado por
     * numero2 o numproveedor
     * @param object $documento
     */
    function fs_generar_numero2(&$documento)
    {
        $tipo_documento = \get_class($documento);
        $numero2 = \filter_input(INPUT_POST, 'numero2');
        if (\filter_input(INPUT_GET, 'numero2')) {
            $numero2 = \filter_input(INPUT_GET, 'numero2');
        }
        $ncf_length = (\strtotime($documento->fecha) < (\strtotime('01-05-2018'))) ? 19 : 11;
        $funcion_generar_numero2 = (\strtotime($documento->fecha) < (\strtotime('01-05-2018')))
            ? 'generar_numero2_old'
            : 'generar_numero2';
        if (\strpos($tipo_documento, 'factura_cliente') !== false) {
            $rectificativa = (bool)$documento->idfacturarect;
            $numero2 = $funcion_generar_numero2(
                $documento->codcliente,
                $documento->codalmacen,
                $documento->codpago,
                $rectificativa
            );
            if (strlen($documento->numero2) !== $ncf_length) {
                $documento->numero2 = '';
            } elseif (strlen($documento->numero2) === $ncf_length) {
                $numero2[0] = $documento->numero2;
            }
        }
        $documento->numero2 = $numero2[0];
        return true;
    }
}

if (!function_exists('fs_generar_numproveedor')) {
    /**
     * Genera y asigna el valor de numproveedor. Devuelve true si lo asgina.
     * A completar en los plugins interesados.
     * @param object $documento
     * @return boolean
     */
    function fs_generar_numproveedor(&$documento)
    {
        $tipo_documento = \get_class($documento);
        $numproveedor = \filter_input(INPUT_POST, 'numproveedor');
        if (\filter_input(INPUT_GET, 'numproveedor')) {
            $numproveedor = \filter_input(INPUT_GET, 'numproveedor');
        }
        if (\strpos($tipo_documento, 'factura_proveedor') !== false) {
            $numproveedor = generar_numero2_proveedor(
                $documento->codproveedor,
                $documento->codalmacen,
                $documento->codpago,
                $documento->fecha,
                $documento->idfacturarect
            );
        }
        if (!empty($documento->numproveedor) && empty($numproveedor)) {
            $numproveedor = $documento->numproveedor;
        }
        $documento->numproveedor = $numproveedor;
        return true;
    }
}


if (!function_exists('fs_documento_post_save')) {

    /**
     * Genera tareas despues que se guarda un documento de venta o de compra
     * En facturacion_base solo devuelve un ok en los plugins por pais
     * se puede agregar procesos adicionales
     * @param object $documento
     * @return boolean
     */
    function fs_documento_post_save(&$documento)
    {
        $tipo_documento = \get_class($documento);
        //echo \strpos($tipo_documento, 'factura_cliente')."\n";
        if (\strpos($tipo_documento, 'factura_cliente') !== false) {
            fs_documento_venta_post_save($documento);
        } elseif (\strpos($tipo_documento, 'factura_proveedor') !== false) {
            fs_documento_compra_post_save($documento);
        }
        if (\substr($tipo_documento, -(\strlen('cliente'))) === 'cliente') {
            compatibilidad_distribucion($documento);
        }
    }
}

function fs_documento_venta_post_save(&$documento)
{
    $empresa = new empresa();
    $ncf_tipo_anulacion = new ncf_tipo_anulacion();
    $tipo_ingreso = \filter_input(INPUT_POST, 'tipo_ingreso');
    $rectificativa = (bool)$documento->idfacturarect;
    $funcion_generar = (\strtotime($documento->fecha) < (\strtotime('01-05-2018')))
        ? 'generar_numero2_old'
        : 'generar_numero2';
    $funcion_tipo_comprobante = (\strtotime($documento->fecha) < (\strtotime('01-05-2018')))
        ? 'get_tipo_comprobante_old'
        : 'get_tipo_comprobante';
    $funcion_guardar_ncf = (\strtotime($documento->fecha) < (\strtotime('01-05-2018')))
        ? 'guardar_ncf_old'
        : 'guardar_ncf';
    if (buscar_ncf($empresa->id, $documento) !== true) {
        [$numero_ncf, $vencimiento] = $funcion_generar(
            $documento->codcliente,
            $documento->codalmacen,
            $documento->codpago,
            $rectificativa
        );
        $tipo_comprobante = $funcion_tipo_comprobante($numero_ncf);
        $motivo = \filter_input(INPUT_POST, 'motivo');
        $motivo_doc = '';
        $documento->fecha_vencimiento = $vencimiento;
        $documento->tipo_ingreso = '1';
        if (isset($tipo_ingreso)) {
            $documento->tipo_ingreso = ($tipo_ingreso !== '') ? $tipo_ingreso : '1';
        }
        if ($motivo) {
            $motivo_anulacion = $ncf_tipo_anulacion->get($motivo);
            $documento->observaciones = ucfirst(FS_FACTURA_RECTIFICATIVA) . " por " . $motivo_anulacion->descripcion;
            $motivo_doc = $motivo_anulacion->codigo . " " . $motivo_anulacion->descripcion;
        }
        $funcion_guardar_ncf($empresa->id, $documento, $tipo_comprobante, $numero_ncf, $motivo_doc);
    }
}

function fs_documento_compra_post_save(&$documento)
{
    $usuario_login = trim(\filter_input(INPUT_COOKIE, 'user'));
    $usuario = ($usuario_login === '') ? 'cron' : $usuario_login;
    $empresa = new empresa();
    $ncf_tipo_compras = new ncf_tipo_compras();
    $prov = new proveedor();
    $proveedor = $prov->get($documento->codproveedor);
    $tipo_comprobante = verificar_proveedor($proveedor, $documento->idfacturarect);
    verificar_numproveedor_ncf($documento, $empresa->id, $tipo_comprobante, $usuario);
    // Si modifica a otro documento lo buscamos
    $fact_compras = new factura_proveedor();
    $documento_modifica = $fact_compras->get($documento->idfacturarect);
    $tipo_compra = verificar_tipo_compra_ncf($documento, $empresa->id, $ncf_tipo_compras);
    $tipo_pago = \filter_input(INPUT_POST, 'tipo_pago');
    // Guardamos la información de la compra en la tabla NCF Compra
    guardar_ncf_compras($empresa->id, $documento, $documento_modifica, $tipo_compra, $tipo_pago, $usuario);
}

function verificar_numproveedor_ncf(&$documento, &$empresa_id, &$tipo_comprobante, &$usuario)
{
    $ncf_rango = new ncf_rango();
    $ncf_compras = new ncf_compras();
    $function_get_solicitud = (\strtotime($documento->fecha) < (\strtotime('01-05-2018')))
        ? 'get_solicitud_old'
        : 'get_solicitud';
    $function_update = (\strtotime($documento->fecha) < (\strtotime('01-05-2018'))) ? 'update_old' : 'update';
    // Si el proveedor es persona física actualizamos el NCF de Proveedores informales (NCF 11)
    // o si es un ingreso por gastos menores (NCF 13)
    $documento_registrado = $ncf_compras->get_ncf($empresa_id, $documento->idfactura, $documento->codproveedor);
    if ($tipo_comprobante !== '' && $documento_registrado === false) {
        $solicitud = $ncf_rango->$function_get_solicitud($empresa_id, $documento->codalmacen, $documento->numproveedor);
        $ncf_rango->$function_update(
            $empresa_id,
            $documento->codalmacen,
            $solicitud,
            $documento->numproveedor,
            $usuario
        );
    }
}

function verificar_tipo_compra_ncf($documento, $empresa_id, $ncf_tipo_compras)
{
    $tipo_compra_code = '09';
    $ncf_compras0 = new ncf_compras();
    $ncf_compra = $ncf_compras0->get_ncf($empresa_id, $documento->idfactura, $documento->codproveedor);
    if (\filter_input(INPUT_POST, 'tipo_compra')) {
        $tipo_compra_code = \filter_input(INPUT_POST, 'tipo_compra');
    } elseif ($ncf_compra) {
        $tipo_compra_code = ($ncf_compra->tipo_compra !== '') ? $ncf_compra->tipo_compra : '09';
    }
    return $ncf_tipo_compras->get($tipo_compra_code);
}

/**
* Agregamos el campo ruta y el codvendedor si está activo distribucion_clientes
* El campo codvendedor se agrega porque el que ingresa el pedido no necesariamente
* puede ser el que atiende la ruta, esto cuando se atienden pedidos via telefónica u otro
*/
function compatibilidad_distribucion(&$documento)
{
    $empresa = new empresa();
    if (\class_exists('distribucion_clientes')) {
        $distribucion_clientes = new distribucion_clientes();
        $codvendedor = '';
        $ruta = '';
        if (\filter_input(INPUT_POST, 'codruta')) {
            $ruta = \filter_input(INPUT_POST, 'codruta');
            $ruta_data = $distribucion_clientes->getOne($empresa->id, $documento->codcliente, $ruta);
            $codvendedor = $ruta_data->codagente ?? '';
        }
        if (empty($documento->codruta)) {
            $documento->codruta = $ruta;
        }
        if (empty($documento->codvendedor)) {
            $documento->codvendedor = $codvendedor;
        }
        $documento->save();
    }
}

function get_tipo_comprobante_old($numero_ncf)
{
    return substr($numero_ncf, 9, 2);
}

function get_tipo_comprobante($numero_ncf)
{
    return substr($numero_ncf, 1, 2);
}

/**
 * Convierte un precio de la divisa_desde a la divisa especificada
 * @param float  $precio
 * @param string $coddivisa_desde
 * @param string $coddivisa
 * @return float
 */
function rd_divisa_convert($precio, $coddivisa_desde, $coddivisa)
{
    $divisa_model = new divisa();
    $divisas = $divisa_model->all();
    $euro = $divisa_model->get('EUR');
    if ($coddivisa_desde !== $coddivisa) {
        $divisa = $divisa_desde = false;
        /// buscamos las divisas en la lista
        foreach ($divisas as $div) {
            if ($div->coddivisa === $coddivisa) {
                $divisa = $div;
            } elseif ($div->coddivisa === $coddivisa_desde) {
                $divisa_desde = $div;
            }
        }
        if ($divisa && $divisa_desde) {
            //Primer Paso convertir la moneda a Euro
            $precio_euro = $precio / $divisa_desde->tasaconv * $euro->tasaconv;
            //Segundo Paso convertir el valor de Euro a la moneda destino
            $precio = $precio_euro / $euro->tasaconv * $divisa->tasaconv;
        }
    }
    return $precio;
}
