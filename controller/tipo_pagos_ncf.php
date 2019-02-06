<?php

/*
 * Copyright (C) 2018 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
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
require_model('ncf_tipo_pagos.php');

/**
 * Description of tipo_ncf
 * Clase para manejar los tipos de Compras para las facturas de proveedor
 * @author Joe Nilson <joenilson at gmail.com>
 */
class tipo_pagos_ncf extends fs_controller
{
    public $ncf_tipo_pagos;
    public $ncf_tipo_pagos_compras;
    public $allow_delete;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Tipo de Pago', 'contabilidad', false, false, false);
    }

    protected function private_core()
    {
        $this->shared_extensions();
        $this->allow_delete = ($this->user->admin) ? true : $this->user->allow_delete_on(__CLASS__);

        $accion = filter_input(INPUT_POST, 'accion');
        if ($accion) {
            $this->tratarTipos($accion);
        }
        
        $this->ncf_tipo_pagos = new ncf_tipo_pagos();
        $this->ncf_tipo_pagos_compras = new ncf_tipo_pagos_compras();
    }

    public function tratarTipos($accion)
    {
        if ($accion == 'agregar') {
            $this->agregar();
        } elseif ($accion == 'eliminar') {
            $this->eliminar();
        } elseif ($accion == 'restore_names_ventas') {
            $this->restaurarNombres();
        } elseif ($accion == 'restore_names_compras') {
            $this->restaurarNombresCompras();
        } else {
            $this->new_error_msg('Se recibió una solicitud incompleta.');
        }
    }
    
    protected function agregar()
    {
        $codigo = filter_input(INPUT_POST, 'codigo');
        $descripcion = filter_input(INPUT_POST, 'descripcion');
        $estado = filter_input(INPUT_POST, 'estado');
        $tipo_pago = filter_input(INPUT_POST, 'tipo_pago');
        $tc0 = ($tipo_pago == 'ventas')?new ncf_tipo_pagos():new ncf_tipo_pagos_compras();
        $tc0->codigo = $codigo;
        $tc0->descripcion = strtoupper(strip_tags(trim($descripcion)));
        $tc0->estado = ($estado) ? true : false;
        if ($tc0->save()) {
            $this->new_message('¡Tipo de Pago agregado con exito!');
        } else {
            $this->new_error_msg('Ocurrio un error al intengar agregar el Tipo de pago, por favor revise los datos ingresados.');
        }
    }
    
    protected function eliminar()
    {
        if($this->allow_delete) {
            $codigo = filter_input(INPUT_POST, 'codigo');
            $tipo_pago = filter_input(INPUT_POST, 'tipo_pago');
            $tc1 = ($tipo_pago == 'ventas')?new ncf_tipo_pagos():new ncf_tipo_pagos_compras();
            $registro = $tc1->get($codigo);
            if ($registro->delete()) {
                $this->new_message('¡Tipo de pago ELIMINADO con exito!');
            } else {
                $this->new_error_msg('Ocurrio un error al tratar de ELIMINAR el Tipo de pago, por favor verifique los datos');
            }
        } else {
            $this->new_error_msg('No tiene permiso para borrar información');
        }
    }
    
    protected function restaurarNombres()
    {
        $ncf_tipo_pagos = new ncf_tipo_pagos();
        $nombresRestaurados = $ncf_tipo_pagos->restore_names();
        $this->template = false;
        $data = array();
        $data['success']=true;
        $data['cantidad']=$nombresRestaurados;
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    protected function restaurarNombresCompras()
    {
        $ncf_tipo_pagos = new ncf_tipo_pagos_compras();
        $nombresRestaurados = $ncf_tipo_pagos->restore_names();
        $this->template = false;
        $data = array();
        $data['success']=true;
        $data['cantidad']=$nombresRestaurados;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function shared_extensions()
    {
        $extensiones = array(
            array(
                'name' => 'tipo_pago_ncf',
                'page_from' => __CLASS__,
                'page_to' => 'ncf',
                'type' => 'button',
                'text' => '<span class="fa fa-list-ol"></span>&nbsp;Configurar Tipos de Pago',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
