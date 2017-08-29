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
    $empresa = new empresa();
    /*
    * Verificación de disponibilidad del Número de NCF para República Dominicana
    */
    //Obtenemos el tipo de comprobante a generar para el cliente, si no existe le asignamos tipo 02 por defecto
    $tipo_comprobante = ncf_tipo_comprobante($empresa->id, $cliente->codcliente);
    //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
    require_model('ncf_rango.php');
    $ncf_numero = array();
    $ncf_rango = new ncf_rango();
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

function factura_post_save($factura)
{
    require_model('empresa.php');
    require_model('cliente.php');
    $empresa = new empresa();
    $cli0 = new cliente();
    $cliente = $cli0->get($factura->codcliente);
    $tipo_comprobante = ncf_tipo_comprobante($empresa->id, $factura->codcliente);
    /*
    * Grabación del Número de NCF para República Dominicana
    */
   //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
   $numero_ncf = generar_numero2($cliente, $factura->codalmacen, $tipo_comprobante, $factura->codpago);
   $this->guardar_ncf($this->empresa->id, $factura, $tipo_comprobante, $numero_ncf);
}

function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf, $motivo = false)
{
    if ($numero_ncf['NCF'] == 'NO_DISPONIBLE' OR empty($numero_ncf)) {
        return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', la factura ' . $factura->idfactura . ' se creo sin NCF.');
    } else {
        $ncf_factura = new ncf_ventas();
        $ncf_factura->idempresa = $idempresa;
        $ncf_factura->codalmacen = $factura->codalmacen;
        $ncf_factura->entidad = $factura->codcliente;
        $ncf_factura->cifnif = $factura->cifnif;
        $ncf_factura->documento = $factura->idfactura;
        $ncf_factura->fecha = $factura->fecha;
        $ncf_factura->tipo_comprobante = $tipo_comprobante;
        $ncf_factura->area_impresion = substr($numero_ncf['NCF'], 6, 3);
        $ncf_factura->ncf = $numero_ncf['NCF'];
        $ncf_factura->usuario_creacion = $this->user->nick;
        $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
        $ncf_factura->estado = true;
        if ($factura->idfacturarect) {
            $ncf_orig = new ncf_ventas();
            $val_ncf = $ncf_orig->get_ncf($this->empresa->id, $factura->idfacturarect, $factura->codcliente);
            $ncf_factura->documento_modifica = $factura->idfacturarect;
            $ncf_factura->ncf_modifica = $val_ncf->ncf;
            $ncf_factura->motivo = $motivo;
        }
        if (!$ncf_factura->save()) {
            $factura->numero2 = '';
            $factura->save();
            $this->new_error_msg('Ocurrió un error al grabar la factura ' . $factura->codigo . ' con el NCF: ' . $numero_ncf['NCF'] . ' Ingrese a la factura y dele al botón corregir NCF.');
        } else {
            $factura->numero2 = $numero_ncf['NCF'];
            $factura->save();
            $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
        }
    }
}

function ncf_tipo_comprobante($idempresa, $codigo_entidad, $tipo_entidad = 'CLI')
{
    require_model('ncf_entidad_tipo.php');
    $net0 = new ncf_entidad_tipo();
    $tipo_comprobante = '02';
    $tipo_comprobante_d = $net0->get($idempresa, $codigo_entidad, $tipo_entidad);
    if ($tipo_comprobante_d) {
        $tipo_comprobante = $tipo_comprobante_d->tipo_comprobante;
    } else {
        $net0 = new ncf_entidad_tipo();
        $net0->entidad = $codigo_entidad;
        $net0->estado = true;
        $net0->fecha_creacion = \date('Y-m-d H:i:s');
        $net0->usuario_creacion = $this->user->nick;
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
    $ncf_numero = array();
    $ncf_rango = new ncf_rango();
    if ($terminal) {
        $numero_ncf = $ncf_rango->generate_terminal($this->empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago, $terminal->area_impresion);
    } else {
        $numero_ncf = $ncf_rango->generate($this->empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago);
    }

    if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
        $ncf_numero = $numero_ncf['NCF'];
    }

    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(array('ncf_numero' => $this->ncf_numero, 'tipo_comprobante' => $tipo_comprobante, 'terminal' => $this->terminal, 'cliente' => $this->cliente_s));
    } else {
        return $numero_ncf;
    }
}
