<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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

/**
 * Description of functions
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
if (!function_exists('fs_tipos_id_fiscal')) {
    /**
    * Devuelve la lista de identificadores fiscales.
    * @return type
    */
   function fs_tipos_id_fiscal()
   {
       return array(FS_CIFNIF,'Pasaporte','Cédula','RNC');
   }
}

/**
 * Se debe migrar aquí la funcionalidad de generar el ncf para insertarlo en
 * el campo numero2
 * @return string
 */

function generar_numero2($cliente, $codalmacen, $codpago, $terminal=false, $json = false)
{
    require_model('empresa.php');
    require_model('ncf_rango.php');
    $empresa = new empresa();
    $ncf_numero = array();
    $ncf_rango = new ncf_rango();
    /*
    * Verificación de disponibilidad del Número de NCF para República Dominicana
    */
    //Obtenemos el tipo de comprobante a generar para el cliente, si no existe le asignamos tipo 02 por defecto
    $tipo_comprobante = ncf_tipo_comprobante($empresa->id, $cliente->codcliente);
    //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
    if ($terminal) {
        $numero_ncf = $ncf_rango->generate_terminal($empresa->id, $codalmacen, $tipo_comprobante, $codpago, $terminal->area_impresion);
    } else {
        $numero_ncf = $ncf_rango->generate($empresa->id, $codalmacen, $tipo_comprobante, $codpago);
    }

    if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
        $ncf_numero = $numero_ncf['NCF'];
    }

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(array('ncf_numero' => $ncf_numero, 'tipo_comprobante' => $tipo_comprobante, 'terminal' => $terminal, 'cliente' => $cliente));
    } else {
        return $ncf_numero;
    }
}

function generar_numero2_proveedor($codproveedor, $codalmacen, $codpago, $terminal=false, $json = false)
{
    require_model('empresa.php');
    require_model('ncf_rango.php');
    $ncf_numero = '';
    $ncf_rango = new ncf_rango();
    $empresa = new empresa();
    $prov = new proveedor();
    $proveedor = $prov->get($codproveedor);
    $tipo_comprobante = 11;
    //Si el proveedor es una persona física le generamos el comprobante fiscal para personas físicas
    if($proveedor->personafisica == true){
        $ncf = $ncf_rango->generate($empresa->id, $codalmacen, $tipo_comprobante, $codpago);
        if ($ncf['NCF'] != 'NO_DISPONIBLE') {
            $ncf_numero = $ncf['NCF'];
        }
    }else{
        $ncf_numero = \filter_input(INPUT_POST, 'numproveedor');
        if(\filter_input(INPUT_GET, 'numproveedor')){
            $ncf_numero = \filter_input(INPUT_GET, 'numproveedor');
        }
    }

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(array('ncf_numero' => $ncf_numero, 'tipo_comprobante' => $tipo_comprobante, 'terminal' => $terminal, 'proveedor' => $codproveedor));
    } else {
        return $ncf_numero;
    }
}

function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf, $motivo = false)
{
    require_model('ncf_rango.php');
    $ncf_rango = new ncf_rango();
    $usuario = \filter_input(INPUT_COOKIE, 'user');
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
        $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
        $ncf_factura->usuario_modificacion = $usuario;
        $ncf_factura->fecha_modificacion = Date('d-m-Y H:i:s');
        $ncf_factura->estado = true;
        if ($factura->idfacturarect) {
            $ncf_orig = new ncf_ventas();
            $val_ncf = $ncf_orig->get_ncf($idempresa, $factura->idfacturarect, $factura->codcliente);
            $ncf_factura->documento_modifica = $factura->idfacturarect;
            $ncf_factura->ncf_modifica = $val_ncf->ncf;
            $ncf_factura->motivo = $motivo;
        }
        $factura->numero2 = $numero_ncf;
        if (!$ncf_factura->save()) {
            $factura->numero2 = '';
        } else {
            $solicitud = $ncf_rango->get_solicitud($idempresa, $factura->codalmacen, $numero_ncf);
            $ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $solicitud, $numero_ncf, $usuario);
        }
        $factura->save();
    }
}

function ncf_tipo_comprobante($idempresa, $codigo_entidad, $tipo_entidad = 'CLI')
{
    require_model('ncf_entidad_tipo.php');
    $net0 = new ncf_entidad_tipo();
    $usuario = \filter_input(INPUT_COOKIE, 'user');
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

/**
 *
 * @param type $cliente
 * @param type $tipo_comprobante
 * @param type $terminal
 * @param type $json
 * @return type array
 */
function generar_comprobante_fiscal($cliente, $tipo_comprobante, $codalmacen, $terminal=false, $json = false)
{
    require_model('ncf_rango.php');
    require_model('empresa.php');
    $ncf_numero = array();
    $empresa = new empresa();
    $ncf_rango = new ncf_rango();
    if ($terminal) {
        $numero_ncf = $ncf_rango->generate_terminal($empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago, $terminal->area_impresion);
    } else {
        $numero_ncf = $ncf_rango->generate($empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago);
    }

    if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
        $ncf_numero = $numero_ncf['NCF'];
    }

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(array('ncf_numero' => $ncf_numero, 'tipo_comprobante' => $tipo_comprobante, 'terminal' => $terminal, 'cliente' => $cliente));
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
        $campo = 'numero2';
        if(substr($tipo_documento,-(strlen('proveedor'))) === 'proveedor'){
            $campo = 'numproveedor';
        }

        $numero2 = \filter_input(INPUT_POST,$campo);
        if(\filter_input(INPUT_GET,$campo)){
            $numero2 = \filter_input(INPUT_GET,$campo);
        }

        if($tipo_documento == 'factura_cliente') {
            $numero2 = generar_numero2($documento->codcliente, $documento->codalmacen, $documento->codpago);
            if(strlen($documento->$campo) != 19){
                $documento->$campo = '';
            }
        }

        if($tipo_documento == 'factura_proveedor') {
            $numero2 = generar_numero2_proveedor($documento->codproveedor, $documento->codalmacen, $documento->codpago);
        }

        if(empty($documento->$campo) && !empty($numero2)){
            $documento->$campo = $numero2;
        }
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
        require_model('empresa.php');
        $empresa = new empresa();
        $tipo_documento = \get_class($documento);
        if($tipo_documento == 'factura_cliente'){
            $numero_ncf = generar_numero2($documento->codcliente, $documento->codalmacen, $documento->codpago);
            $tipo_comprobante = get_tipo_comprobante($numero_ncf);
            guardar_ncf($empresa->id, $documento, $tipo_comprobante, $numero_ncf);
        }

        if(substr($tipo_documento,-(strlen('cliente'))) === 'cliente'){
            compatibilidad_distribucion($documento, $empresa->id);
        }

    }
}

/**
* Agregamos el campo ruta y el codvendedor si está activo distribucion_clientes
* El campo codvendedor se agrega porque el que ingresa el pedido no necesariamente
* puede ser el que atiende la ruta, esto cuando se atienden pedidos via telefónica u otro
*/
function compatibilidad_distribucion(&$documento, $idempresa)
{

    require_model('distribucion_clientes.php');
    $distribucion_clientes = new distribucion_clientes();
    if (\class_exists('distribucion_clientes')) {
        $codvendedor = '';
        $ruta = '';
        if (\filter_input(INPUT_POST, 'codruta')) {
            $ruta = \filter_input(INPUT_POST, 'codruta');
            $ruta_data = $distribucion_clientes->getOne($idempresa, $documento->codcliente, $ruta);
            $codvendedor = ($ruta_data) ? $ruta_data->codagente : '';
        }
        if(empty($documento->codruta)) {
            $documento->codruta = $ruta;
        }
        if(empty($documento->codvendedor)) {
            $documento->codvendedor = $codvendedor;
        }
        $documento->save();
    }
}

function get_tipo_comprobante($numero_ncf)
{
    return substr($numero_ncf, 9,2);
}
