<?php
/*
 * This file is part of republica_dominicana
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

require_once 'plugins/republica_dominicana/extras/rd_controller.php';

/**
 * Description of ventas_factura_fiscal
 * Datos necesarios para la presentación de información a la DGII
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ventas_factura_fiscal extends rd_controller
{

    public $factura;
    public $ncf_venta;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Información Fiscal de factura de ventas', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->share_extension();
        $this->template = 'tab/' . __CLASS__;

        $fact0 = new factura_cliente();
        $ncf_ventas = new ncf_ventas();
        $this->factura = FALSE;
        if (isset($_REQUEST['id'])) {
            $this->factura = $fact0->get($_REQUEST['id']);
            $this->ncf_venta = $ncf_ventas->get($this->empresa->id, $this->factura->numero2)[0];
        }

        if (!$this->factura) {
            $this->new_error_msg('Factura no encontrada.', 'error', FALSE, FALSE);
        }
    }

    private function share_extension()
    {
        $fsxet = new fs_extension();
        $fsxet->name = 'tab_ventas_fiscal';
        $fsxet->from = __CLASS__;
        $fsxet->to = 'ventas_factura';
        $fsxet->type = 'tab';
        $fsxet->text = '<span class="fa fa-balance-scale fa-fw" aria-hidden="true"></span>'
                . '<span class="hidden-xs">&nbsp; Información Fiscal</span>';
        $fsxet->save();

        $fsxet2 = new fs_extension();
        $fsxet2->name = 'tab_ventas_fiscal_editar';
        $fsxet2->from = __CLASS__;
        $fsxet2->to = 'editar_factura';
        $fsxet2->type = 'tab';
        $fsxet2->text = '<span class="fa fa-balance-scale fa-fw" aria-hidden="true"></span>'
                . '<span class="hidden-xs">&nbsp; Información Fiscal</span>';
        $fsxet2->save();
    }
}
