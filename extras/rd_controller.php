<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('agente.php');
require_model('almacen.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('pais.php');
require_model('serie.php');
require_model('ncf_rango.php');
require_model('ncf_entidad_tipo.php');
require_model('ncf_tipo.php');
require_model('ncf_ventas.php');
require_once 'plugins/facturacion_base/extras/fbase_controller.php';
/**
 * Description of rd_controller
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class rd_controller extends fbase_controller
{
/**
     * TRUE si el usuario tiene permisos para eliminar en la página.
     * @var type
     */
    public $allow_delete;

    /**
     * TRUE si hay más de un almacén.
     * @var type
     */
    public $multi_almacen;
    public $divisa;
    public $forma_pago;
    public $periodos;
    public $meses = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Setiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
    public $ncf_rango;
    public $ncf_tipo;
    public $ncf_entidad_tipo;
    public $ncf_ventas;
    public $agente;
    public $almacen;
    public $pais;
    public $serie;
    public $array_series;
    
    protected function private_core() 
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);

        //Datos de NCF
        $this->agente = new agente();
        $this->almacen = new almacen();
        $this->divisa = new divisa();
        $this->forma_pago = new forma_pago();
        $this->pais = new pais();
        $this->serie = new serie();
        $this->ncf_rango = new ncf_rango();
        $this->ncf_tipo = new ncf_tipo();
        $this->ncf_entidad_tipo = new ncf_entidad_tipo();
        $this->array_series = \range('A', 'U');
        
        $fsvar = new fs_var();
        $this->multi_almacen = $fsvar->simple_get('multi_almacen');
        $this->periodos = range(2016,\date('Y'));
        
    }
    
    public function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf, $motivo = false) {
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
            $ncf_factura->area_impresion = substr($numero_ncf['NCF'],6,3);
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            $ncf_factura->estado = TRUE;
            if($factura->idfacturarect){
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
                $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
    }
}
