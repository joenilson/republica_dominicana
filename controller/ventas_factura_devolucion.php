<?php
/*
 * This file is part of facturacion_base
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of ventas_factura_devolucion
 *
 * @author Carlos Garcia Gomez
 */
class ventas_factura_devolucion extends rd_controller
{

    public $factura;
    public $serie;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Devoluciones de factura de venta', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->share_extension();
        $this->template = 'tab/' . __CLASS__;

        $this->serie = new serie();

        $fact0 = new factura_cliente();
        $this->factura = FALSE;
        if (isset($_REQUEST['id'])) {
            $this->factura = $fact0->get($_REQUEST['id']);
        }

        if ($this->factura) {
            if (isset($_POST['id'])) {
                $this->nueva_rectificativa();
            }
        } else {
            $this->new_error_msg('Factura no encontrada.', 'error', FALSE, FALSE);
        }
    }

    private function nueva_rectificativa()
    {
        $continuar = TRUE;

        $eje0 = new ejercicio();
        $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
        if (!$ejercicio) {
            $this->new_error_msg('Ejercicio no encontrado o está cerrado.');
            $continuar = FALSE;
        }

        if ($continuar) {
            $tipo_comprobante = '04';
            $numero_ncf = $this->generar_numero_ncf($this->empresa->id, $this->factura->codalmacen, $tipo_comprobante, $this->factura->codpago);
            $motivo = \filter_input(INPUT_POST, 'motivo');
            $motivo_anulacion = $this->ncf_tipo_anulacion->get($motivo);
            $frec = clone $this->factura;
            /**
             * Compatibilidad con plugin distribucion
             */
            if (property_exists('factura_cliente', 'codruta')) {
                $frec->codruta = $this->factura->codruta;
                $frec->codvendedor = $this->factura->codvendedor;
            }
            $frec->idfactura = NULL;
            $frec->numero = NULL;
            $frec->numero2 = $numero_ncf['NCF'];
            $frec->codigo = NULL;
            $frec->idasiento = NULL;
            $frec->idfacturarect = $this->factura->idfactura;
            $frec->codigorect = $this->factura->codigo;
            $frec->codejercicio = $ejercicio->codejercicio;
            $frec->codserie = $_POST['codserie'];
            $frec->fecha = \date('Y-m-d', strtotime($_POST['fecha']));
            $frec->hora = $this->hour();
            $frec->observaciones = ucfirst(FS_FACTURA_RECTIFICATIVA) . " por " . $motivo_anulacion->descripcion;
            $frec->femail = NULL;
            $frec->numdocs = NULL;

            $frec->irpf = 0;
            $frec->neto = 0;
            $frec->total = 0;
            $frec->totalirpf = 0;
            $frec->totaliva = 0;
            $frec->totalrecargo = 0;

            if($frec->save()){
                $guardar = $this->guardar_lineas_devolucion($frec);
                if ($guardar) {
                    /// redondeamos
                    $frec->neto = round($frec->neto, FS_NF0);
                    $frec->totaliva = round($frec->totaliva, FS_NF0);
                    $frec->totalirpf = round($frec->totalirpf, FS_NF0);
                    $frec->totalrecargo = round($frec->totalrecargo, FS_NF0);
                    $frec->total = $frec->neto + $frec->totaliva - $frec->totalirpf + $frec->totalrecargo;
                    $frec->pagada = true;
                    if ($frec->save()) {
                        $this->guardar_ncf($this->empresa->id, $frec, $tipo_comprobante, $numero_ncf, $motivo_anulacion->codigo . " " . $motivo_anulacion->descripcion);
                        $this->generar_asiento($frec);
                        $this->new_message(FS_FACTURA_RECTIFICATIVA . ' creada correctamente.');
                    }
                } else {
                    $frec->delete();
                    $this->new_advice('Todas las cantidades a devolver están a 0 no se genera ningún documento.');
                }
            }else{
                $this->new_error_msg('Error al guardar la ' . FS_FACTURA_RECTIFICATIVA);
            }

        }
    }

    public function guardar_lineas_devolucion($frec)
    {
        $total_devolucion = 0;
        $art0 = new articulo();

        foreach ($this->factura->get_lineas() as $value) {
            if (isset($_POST['devolver_' . $value->idlinea]) and (floatval($_POST['devolver_' . $value->idlinea]) > 0)) {
                $devolucion = floatval($_POST['devolver_' . $value->idlinea]);
                $linea = clone $value;
                $linea->idlinea = null;
                $linea->idfactura = $frec->idfactura;
                $linea->idalbaran = null;
                $linea->cantidad = 0 - $devolucion;
                $linea->pvpsindto = $linea->cantidad * $linea->pvpunitario;
                $linea->pvptotal = $linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100;
                if ($linea->save()) {
                    $articulo = $art0->get($linea->referencia);
                    if ($articulo) {
                        $articulo->sum_stock($frec->codalmacen, 0 - $linea->cantidad, false, $linea->codcombinacion);
                    }
                    $frec->neto += $linea->pvptotal;
                    $frec->totaliva += ($linea->pvptotal * $linea->iva / 100);
                    $frec->totalirpf += ($linea->pvptotal * $linea->irpf / 100);
                    $frec->totalrecargo += ($linea->pvptotal * $linea->recargo / 100);

                    if ($linea->irpf > $frec->irpf) {
                        $frec->irpf = $linea->irpf;
                    }
                }
                $total_devolucion += $devolucion;
            }

        }
        return $total_devolucion;
    }

    private function generar_asiento(&$factura)
    {
        if ($this->empresa->contintegrada) {
            $asiento_factura = new asiento_factura();
            $asiento_factura->generar_asiento_venta($factura);

            foreach ($asiento_factura->errors as $err) {
                $this->new_error_msg($err);
            }

            foreach ($asiento_factura->messages as $msg) {
                $this->new_message($msg);
            }
        }
    }

    private function share_extension()
    {
        $fsxet = new fs_extension();
        $fsxet->name = 'tab_devoluciones';
        $fsxet->from = __CLASS__;
        $fsxet->to = 'ventas_factura';
        $fsxet->type = 'tab';
        $fsxet->text = '<span class="glyphicon glyphicon-share" aria-hidden="true"></span>'
                . '<span class="hidden-xs">&nbsp; Devoluciones</span>';
        $fsxet->save();

        $fsxet2 = new fs_extension();
        $fsxet2->name = 'tab_editar_factura';
        $fsxet2->from = __CLASS__;
        $fsxet2->to = 'editar_factura';
        $fsxet2->type = 'tab';
        $fsxet2->text = '<span class="glyphicon glyphicon-share" aria-hidden="true"></span>'
                . '<span class="hidden-xs">&nbsp; Devoluciones</span>';
        $fsxet2->save();
    }
}
