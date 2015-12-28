<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
require_model('almacen.php');
require_model('pais.php');
require_model('ncf_rango.php');
require_model('ncf_tipo.php');
require_model('ncf_ventas.php');

/**
 * Description of ncf
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf extends fs_controller {

    public $ncf_rango;
    public $ncf_tipo;
    public $ncf_ventas;
    public $allow_delete;
    public $almacen;
    public $pais;
    public $array_series;

    public function __construct() {
        parent::__construct(__CLASS__, 'Maestro de NCF', 'contabilidad');
    }

    protected function private_core() {
        $this->almacen = new almacen();
        $this->pais = new pais();
        $this->ncf_rango = new ncf_rango();
        $this->ncf_tipo = new ncf_tipo();
        $this->array_series = \range('A', 'U');

        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        if (isset($_POST['solicitud']) AND isset($_POST['codalmacen']) AND isset($_POST['serie']) AND isset($_POST['division']) AND isset($_POST['punto_emision']) AND isset($_POST['area_impresion']) AND isset($_POST['tipo_comprobante']) AND isset($_POST['secuencia_inicio']) AND isset($_POST['secuencia_fin'])) {
            $id = \filter_input(INPUT_POST, 'id');
            $solicitud = \filter_input(INPUT_POST, 'solicitud');
            $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
            $serie = \filter_input(INPUT_POST, 'serie');
            $division = \str_pad(\filter_input(INPUT_POST, 'division'), 2, "0", STR_PAD_LEFT);
            $punto_emision = \str_pad(\filter_input(INPUT_POST, 'punto_emision'), 3, "0", STR_PAD_LEFT);
            $area_impresion = \str_pad(\filter_input(INPUT_POST, 'area_impresion'), 3, "0", STR_PAD_LEFT);
            $tipo_comprobante = \str_pad(\filter_input(INPUT_POST, 'tipo_comprobante'), 2, "0", STR_PAD_LEFT);
            $secuencia_inicio = \filter_input(INPUT_POST, 'secuencia_inicio');
            $secuencia_fin = \filter_input(INPUT_POST, 'secuencia_fin');
            $correlativo = \filter_input(INPUT_POST, 'correlativo');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $estado = (isset($estado_val))?"true":"false";
            $contado_val = \filter_input(INPUT_POST, 'contado');
            $contado = (isset($contado_val))?"true":"false";
            $ncf0 = $this->ncf_rango->get($this->empresa->id, $solicitud, $codalmacen, $serie, $division, $punto_emision, $area_impresion, $tipo_comprobante);

            if (!$ncf0) {
                $ncf0 = new ncf_rango();
            }
            
            $verificacion = $this->verifica_correlativo($ncf0, $correlativo);
            if ($verificacion) {
                return $this->new_error_msg("¡Existen " . $verificacion . " NCF generados, no se puede retroceder el correlativo!");
            }

            $ncf0->idempresa = $this->empresa->id;
            $ncf0->id = $id;
            $ncf0->solicitud = $solicitud;
            $ncf0->codalmacen = $codalmacen;
            $ncf0->serie = $serie;
            $ncf0->division = $division;
            $ncf0->punto_emision = $punto_emision;
            $ncf0->area_impresion = $area_impresion;
            $ncf0->tipo_comprobante = $tipo_comprobante;
            $ncf0->secuencia_inicio = $secuencia_inicio;
            $ncf0->secuencia_fin = $secuencia_fin;
            $ncf0->correlativo = (null !== \filter_input(INPUT_POST, 'correlativo')) ? $correlativo : $secuencia_inicio;
            $ncf0->usuario_creacion = $this->user->nick;
            $ncf0->fecha_creacion = \date('d-m-Y H:i');
            $ncf0->usuario_modificacion = $this->user->nick;
            $ncf0->fecha_modificacion = \date('d-m-Y H:i');
            $ncf0->estado = $estado;
            $ncf0->contado = $contado;
            if ($ncf0->save()) {
                $this->new_message("Datos de la Solicitud {$ncf0->contado} " . $ncf0->solicitud . " guardados correctamente.");
            } else
                $this->new_error_msg("¡Imposible guardar los datos de la solicitud!");
        }
        elseif ($_GET['delete']) {
            $id = \filter_input(INPUT_GET, 'delete');
            if(!is_null($id)){
                $data_borrar = $this->ncf_rango->get_by_id($this->empresa->id, $id);
                $ncf0 = new ncf_rango();
                $ncf0->idempresa = $this->empresa->id;
                $ncf0->id = $id;
                if($ncf0->delete()){
                    $this->new_message("Datos de la Solicitud " . $data_borrar->solicitud . " con tipo de comprobante ".$data_borrar->tipo_comprobante." eliminados correctamente.");
                }else{
                    $this->new_error_msg("¡Ocurrió un error, por favor revise la información enviada!");
                }
            }else{
                $this->new_error_msg("¡Existen " . ($data_borrar->correlativo-$data_borrar->secuencia_inicio) . " NCF generados, no se puede eliminar esta secuencia!");
            }
        }
    }

    protected function verifica_correlativo($ncf, $correlativo) {
        if (($ncf->correlativo != $correlativo) AND ($ncf->correlativo > $ncf->secuencia_inicio)) {
            $this->ncf_ventas = new ncf_ventas();
            $facturas = $this->ncf_ventas->get_tipo($ncf->idempresa, $ncf->tipo_comprobante, $ncf->codalmacen);
            if ($facturas) {
                return count($facturas);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf) {
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
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
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            $ncf_factura->estado = TRUE;
            if($factura->idfacturarect){
                $ncf_orig = new ncf_ventas();
                $val_ncf = $ncf_orig->get_ncf($this->empresa->id, $factura->idfacturarect, $factura->codcliente);
                $ncf_factura->documento_modifica = $factura->idfacturarect;
                $ncf_factura->ncf_modifica = $val_ncf->ncf;
            }
            if (!$ncf_factura->save()) {
                return $this->new_error_msg('Ocurrió un error al grabar la factura ' . $factura->idfactura . ' con el NCF: ' . $numero_ncf['NCF'] . ' Anule la factura e intentelo nuevamente.');
            } else {
                $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
    }

}
