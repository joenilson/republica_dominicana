<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('ncf_tipo.php');

/**
 * Description of tipo_ncf
 * Clase para manejar los tipos de NCF configurados
 * @author Joe Nilson <joenilson at gmail.com>
 */
class tipo_ncf extends fs_controller
{

    public $ncf_tipo;
    public $allow_delete;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Tipo de NCF', 'contabilidad', FALSE, FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->shared_extensions();
        $this->allow_delete = ($this->user->admin) ? TRUE : $this->user->allow_delete_on(__CLASS__);

        $accion = filter_input(INPUT_POST, 'accion');
        if ($accion) {
            $this->tratar_tipos($accion);
        }
        $this->ncf_tipo = new ncf_tipo();
    }

    public function tratar_tipos($accion)
    {
        if ($accion == 'agregar') {
            $tipo_comprobante = filter_input(INPUT_POST, 'tipo_comprobante');
            $descripcion = filter_input(INPUT_POST, 'descripcion');
            $clase_movimiento = filter_input(INPUT_POST, 'clase_movimiento');
            $ventas = filter_input(INPUT_POST, 'ventas');
            $compras = filter_input(INPUT_POST, 'compras');
            $contribuyente = filter_input(INPUT_POST, 'contribuyente');
            $estado = filter_input(INPUT_POST, 'estado');
            $tc0 = new ncf_tipo();
            $tc0->tipo_comprobante = strtoupper(strip_tags(trim($tipo_comprobante)));
            $tc0->descripcion = strtoupper(strip_tags(trim($descripcion)));
            $tc0->clase_movimiento = $clase_movimiento;
            $tc0->ventas = $ventas;
            $tc0->compras = $compras;
            $tc0->contribuyente = $contribuyente;
            $tc0->estado = ($estado) ? "TRUE" : "FALSE";
            if ($tc0->save()) {
                $this->new_message('¡Tipo de comprobante agregado con exito!');
            } else {
                $this->new_error_msg('Ocurrio un error al intengar agregar el Tipo de comprobante, por favor revise los datos ingresados.');
            }
        } elseif ($accion == 'eliminar') {
            $tipo_comprobante = filter_input(INPUT_POST, 'tipo_comprobante');
            $tc1 = new ncf_tipo();
            $registro = $tc1->get($tipo_comprobante);
            if ($registro->delete()) {
                $this->new_message('¡Tipo de comprobante eliminado con exito!');
            } else {
                $this->new_error_msg('Ocurrio un error al tratar de eliminar el Tipo de comprobante, por favor verifique los datos');
            }
        } else {
            $this->new_error_msg('Se recibió una solicitud incompleta.');
        }
    }

    public function shared_extensions()
    {
        $extensiones = array(
            array(
                'name' => 'tipo_ncfs',
                'page_from' => __CLASS__,
                'page_to' => 'ncf',
                'type' => 'button',
                'text' => '<span class="fa fa-list-ol"></span>&nbsp;Configurar Tipos NCF',
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
