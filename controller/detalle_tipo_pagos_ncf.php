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
require_model('ncf_detalle_tipo_pagos.php');

/**
 * Description of tipo_ncf
 * Clase para manejar los tipos de Compras para las facturas de proveedor
 * @author Joe Nilson <joenilson at gmail.com>
 */
class detalle_tipo_pagos_ncf extends fs_controller
{
    public $ncf_detalle_tipo_pagos;
    public $ncf_tipo_pagos;
    public $allow_delete;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Detalle de Tipo de Pago', 'contabilidad', false, false, false);
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
        $this->ncf_detalle_tipo_pagos = new ncf_detalle_tipo_pagos();
    }

    public function tratarTipos($accion)
    {
        if ($accion == 'asignar') {
            $this->asignar();
        } elseif ($accion == 'eliminar') {
            $this->eliminar();
        } else {
            $this->new_error_msg('Se recibió una solicitud incompleta.');
        }
    }
    
    protected function asignar()
    {
        $codigo = filter_input(INPUT_POST, 'codigo');
        $codpago = filter_input(INPUT_POST, 'codpago');
        $dtp0 = new ncf_detalle_tipo_pagos();
        $dtp0->codigo = $codigo;
        $dtp0->codpago = $codpago;
        if ($dtp0->save()) {
            $this->new_message('¡Asignación de Tipo de Pago realizado con exito!');
        } else {
            $this->new_error_msg('Ocurrio un error al intengar asignar la forma de pago, por favor revise los datos ingresados.');
        }
    }
    
    protected function eliminar()
    {
        /// desactivamos la plantilla HTML
        $this->template = false;
        header('Content-Type: application/json');
        
        if($this->allow_delete) {
            $codigo = filter_input(INPUT_POST, 'codigo');
            $codpago = filter_input(INPUT_POST, 'codpago');
            $tc1 = new ncf_detalle_tipo_pagos();
            $registro = $tc1->get($codigo,$codpago);
            if ($registro->delete()) {
                echo json_encode(array('message'=>'¡Asignación de Forma de pago a Tipo de Pago eliminada con exito!'));
            } else {
                echo json_encode(array('message'=>'Ocurrio un error al tratar de eliminar la asignación, por favor verifique los datos'));
            }
        } else {
            echo json_encode(array('message'=>'No tiene permiso para borrar información'));
        }
    }

    public function shared_extensions()
    {
        $extensiones = array(
            array(
                'name' => 'detalle_tipo_pago_ncf',
                'page_from' => __CLASS__,
                'page_to' => 'tipo_pagos_ncf',
                'type' => 'button',
                'text' => '<span class="fa fa-list-ol"></span>&nbsp;Detalle de los Tipos Pago',
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
