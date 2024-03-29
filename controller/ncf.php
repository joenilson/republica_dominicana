<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Lesser General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
/**
 * Description of ncf
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf extends fs_controller
{
    public $ncf_rango;
    public $ncf_tipo;
    public $ncf_ventas;
    public $allow_delete;
    public $almacen;
    public $pais;
    public $array_series;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Maestro de NCF', 'contabilidad');
    }

    protected function private_core()
    {
        $this->almacen = new almacen();
        $this->pais = new pais();
        $this->ncf_rango = new ncf_rango();
        $this->ncf_tipo = new ncf_tipo();
        $this->array_series = \range('A', 'Z');
        $this->default_items->codalmacen();

        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $delete = \filter_input(INPUT_GET, 'delete');
        $accion = \filter_input(INPUT_POST, 'accion');
        $restore = \filter_input(INPUT_GET, 'restore_names');
        
        if($delete){
            $this->eliminarSolicitudNCF($delete);
        }
        
        if($restore) {
            $this->restaurarNombresNCF();
        }
        
        if($accion == 'guardar' OR $accion == 'modificar') {
            $this->guardarSolicitudNCF();
        }
                
    }
    
    protected function eliminarSolicitudNCF($delete)
    {
        $id = $delete;
        if (!is_null($id)) {
            $data_borrar = $this->ncf_rango->get_by_id($this->empresa->id, $id);
            $ncf0 = new ncf_rango();
            $ncf0->idempresa = $this->empresa->id;
            $ncf0->id = $id;
            if ($ncf0->delete()) {
                $this->new_message("Datos de la Solicitud " . $data_borrar->solicitud . " con tipo de comprobante ".$data_borrar->tipo_comprobante." eliminados correctamente.");
            } else {
                $this->new_error_msg("¡Ocurrió un error, por favor revise la información enviada!");
            }
        } else {
            $this->new_error_msg("¡Existen " . ($data_borrar->correlativo-$data_borrar->secuencia_inicio) . " NCF generados, no se puede eliminar esta secuencia!");
        }
    }
    
    protected function guardarSolicitudNCF()
    {
        $id = \filter_input(INPUT_POST, 'id');
        $solicitud = \filter_input(INPUT_POST, 'solicitud');
        $autorizacion = \filter_input(INPUT_POST, 'autorizacion');
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $serie = \filter_input(INPUT_POST, 'serie');
        $tipo_comprobante = \str_pad(\filter_input(INPUT_POST, 'tipo_comprobante'), 2, "0", STR_PAD_LEFT);
        $secuencia_inicio = \filter_input(INPUT_POST, 'secuencia_inicio');
        $secuencia_fin = \filter_input(INPUT_POST, 'secuencia_fin');
        $correlativo = \filter_input(INPUT_POST, 'correlativo');
        $fecha_vencimiento = \filter_input(INPUT_POST, 'fecha_vencimiento');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        
        $ncf0 = $this->ncf_rango->get_information($this->empresa->id, $solicitud, $autorizacion, $serie, $tipo_comprobante, $estado);
        if ($id) {
            $ncf0 = $this->ncf_rango->get_by_id($this->empresa->id, $id);
            $estado = (isset($id))?$estado:true;
        }
        
        if (!$ncf0) {
            $ncf0 = new ncf_rango();
        }
        
        $ncf0->idempresa = $this->empresa->id;
        $ncf0->id = $id;
        $ncf0->solicitud = $solicitud;
        $ncf0->autorizacion = $autorizacion;
        $ncf0->codalmacen = $codalmacen;
        $ncf0->serie = $serie;
        $ncf0->tipo_comprobante = $tipo_comprobante;
        $ncf0->secuencia_inicio = $secuencia_inicio;
        $ncf0->secuencia_fin = $secuencia_fin;
        $ncf0->correlativo = (null !== \filter_input(INPUT_POST, 'correlativo')) ? $correlativo : $secuencia_inicio;
        $ncf0->usuario_creacion = $this->user->nick;
        $ncf0->fecha_vencimiento = (!empty($fecha_vencimiento))?\date('d-m-Y', strtotime($fecha_vencimiento)):null;
        $ncf0->fecha_creacion = \date('d-m-Y H:i:s');
        $ncf0->usuario_modificacion = $this->user->nick;
        $ncf0->fecha_modificacion = \date('d-m-Y H:i:s');
        $ncf0->estado = $estado;
        if ($ncf0->save()) {
            $this->new_message("Datos de la Solicitud " . $ncf0->solicitud . " guardados correctamente.");
        } else {
            $this->new_error_msg("¡Imposible guardar los datos de la solicitud!");
        }

    }
}
