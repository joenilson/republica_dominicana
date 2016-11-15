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
require_model('albaran_cliente.php');
require_model('albaran_proveedor.php');
require_model('asiento.php');
require_model('asiento_factura.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('forma_pago.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');
require_model('ncf_tipo.php');
require_model('ncf_entidad_tipo.php');
require_model('ncf_rango.php');
require_model('ncf_ventas.php');
require_once 'helper_ncf.php';

/**
 * Esta es una copia de funcionalidades minimas del plugin MegaFacturador
 * su uso está restringido solo para las facturas de venta
 * no se aplciará a un menu, sino que será un boton dentro de la lista de Albaranes
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ventas_megafacturador extends fs_controller {

    public $numasientos;
    public $opciones;
    public $serie;
    public $series_elegidas;
    public $url_recarga;
    public $fecha_pedido;
    public $fecha_albaran;
    public $fecha_facturas;
    private $asiento_factura;
    private $cliente;
    private $ejercicio;
    private $ejercicios;
    private $forma_pago;
    private $formas_pago;
    private $proveedor;
    private $regularizacion;
    private $total;
    public $ncf_tipo;
    public $ncf_rango;
    public $ncf_ventas;
    public $ncf_entidad_tipo;

    public function __construct() {
        parent::__construct(__CLASS__, 'Facturación masiva', 'ventas', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->asiento_factura = new asiento_factura();
        $this->cliente = new cliente();
        $this->ejercicio = new ejercicio();
        $this->ejercicios = array();
        $this->forma_pago = new forma_pago();
        $this->formas_pago = $this->forma_pago->all();
        $this->numasientos = 0;
        $this->proveedor = new proveedor();
        $this->regularizacion = new regularizacion_iva();
        $this->serie = new serie();
        $this->ncf_tipo = new ncf_tipo();
        $this->ncf_rango = new ncf_rango();
        $this->ncf_entidad_tipo = new ncf_entidad_tipo();
        $this->ncf_ventas = new ncf_ventas();
        $this->series_elegidas['E'] = "E";
        $this->fecha_pedido = \date('d-m-Y');
        $this->fecha_albaran = \date('d-m-Y');
        $this->fecha_facturas = \date('d-m-Y',strtotime('+1 day'));

        $this->share_extensions();

        $procesar_g = filter_input(INPUT_GET, 'procesar');
        $procesar_p = filter_input(INPUT_POST, 'procesar');
        
        $fecha_albaran = filter_input(INPUT_POST, 'fecha_albaran');
        $fecha_facturas = filter_input(INPUT_POST, 'fecha_facturas');
        $this->fecha_albaran = ($fecha_albaran)?$fecha_albaran:$this->fecha_albaran;
        $this->fecha_facturas = ($fecha_facturas)?$fecha_facturas:$this->fecha_facturas;
        
        $procesar = ($procesar_g) ? $procesar_g : $procesar_p;
        if ($procesar == 'TRUE') {
            $this->generar_facturas();
        }
    }

    private function generar_facturas() {
        $recargar = FALSE;
        $this->total = 0;
        foreach ($this->pendientes_venta() as $alb) {
            if ($this->generar_factura_cliente(array($alb))) {
                $recargar = TRUE;
            } else {
                break;
            }
        }

        if ($recargar) {
            $this->new_message($this->total . ' ' . FS_ALBARANES . ' de cliente facturados.');
        }

        /// ¿Recargamos?
        if (count($this->get_errors()) > 0) {
            $this->new_error_msg('Se han producido errores. Proceso detenido.');
        } else if ($recargar) {
            $this->url_recarga = $this->url() . '&megafac_fecha=' . $this->opciones['megafac_fecha']
                    . '&megafac_hasta=' . $this->opciones['megafac_hasta']
                    . '&megafac_codserie=' . $this->opciones['megafac_codserie']
                    . '&procesar=TRUE';

            if ($this->opciones['megafac_ventas']) {
                $this->url_recarga .= '&megafac_ventas=TRUE';
            }

            $this->new_message('Recargando... &nbsp; <i class="fa fa-refresh fa-spin"></i>');
        } else {
            $this->new_advice('Finalizado. <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>');
        }
    }

    /**
     * El original es el plugin megafacturador
     * Genera una factura a partir de un array de albaranes.
     * @param albaran_cliente $albaranes
     */
    private function generar_factura_cliente($albaranes) {
        $continuar = TRUE;

        $factura = new factura_cliente();
        $factura->codagente = $albaranes[0]->codagente;
        $factura->codalmacen = $albaranes[0]->codalmacen;
        $factura->coddivisa = $albaranes[0]->coddivisa;
        $factura->tasaconv = $albaranes[0]->tasaconv;
        $factura->codpago = $albaranes[0]->codpago;
        $factura->codserie = $albaranes[0]->codserie;
        $factura->irpf = $albaranes[0]->irpf;
        $factura->numero2 = $albaranes[0]->numero2;
        $factura->observaciones = $albaranes[0]->observaciones;

        $factura->apartado = $albaranes[0]->apartado;
        $factura->cifnif = $albaranes[0]->cifnif;
        $factura->ciudad = $albaranes[0]->ciudad;
        $factura->codcliente = $albaranes[0]->codcliente;
        $factura->coddir = $albaranes[0]->coddir;
        $factura->codpais = $albaranes[0]->codpais;
        $factura->codpostal = $albaranes[0]->codpostal;
        $factura->direccion = $albaranes[0]->direccion;
        $factura->nombrecliente = $albaranes[0]->nombrecliente;
        $factura->provincia = $albaranes[0]->provincia;

        $factura->envio_apartado = $albaranes[0]->envio_apartado;
        $factura->envio_apellidos = $albaranes[0]->envio_apellidos;
        $factura->envio_ciudad = $albaranes[0]->envio_ciudad;
        $factura->envio_codigo = $albaranes[0]->envio_codigo;
        $factura->envio_codpais = $albaranes[0]->envio_codpais;
        $factura->envio_codpostal = $albaranes[0]->envio_codpostal;
        $factura->envio_codtrans = $albaranes[0]->envio_codtrans;
        $factura->envio_direccion = $albaranes[0]->envio_direccion;
        $factura->envio_nombre = $albaranes[0]->envio_nombre;
        $factura->envio_provincia = $albaranes[0]->envio_provincia;

        /// asignamos fecha y ejercicio usando la del albarán
        if ($this->opciones['megafac_fecha'] == 'albaran') {
            $eje0 = $this->get_ejercicio($albaranes[0]->fecha);
            if ($eje0) {
                $factura->codejercicio = $eje0->codejercicio;
                $factura->set_fecha_hora($albaranes[0]->fecha, $albaranes[0]->hora);
            }
        }

        /**
         * Si se ha elegido fecha de hoy o no se ha podido usar la del albarán porque
         * el ejercicio estaba cerrado, asignamos ejercicio para hoy y usamos la mejor
         * fecha y hora.
         */
        if (is_null($factura->codejercicio)) {
            $eje0 = $this->ejercicio->get_by_fecha($factura->fecha);
            if ($eje0) {
                $factura->codejercicio = $eje0->codejercicio;
                $factura->set_fecha_hora($factura->fecha, $factura->hora);
            }
        }

        /// calculamos neto e iva
        foreach ($albaranes as $alb) {
            foreach ($alb->get_lineas() as $l) {
                $factura->neto += $l->pvptotal;
                $factura->totaliva += $l->pvptotal * $l->iva / 100;
                $factura->totalirpf += $l->pvptotal * $l->irpf / 100;
                $factura->totalrecargo += $l->pvptotal * $l->recargo / 100;
            }
        }

        /// redondeamos
        $factura->neto = round($factura->neto, FS_NF0);
        $factura->totaliva = round($factura->totaliva, FS_NF0);
        $factura->totalirpf = round($factura->totalirpf, FS_NF0);
        $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
        $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

        /// comprobamos la forma de pago para saber si hay que marcar la factura como pagada
        $formapago = $this->get_forma_pago($factura->codpago);
        if ($formapago) {
            if ($formapago->genrecibos == 'Pagados') {
                $factura->pagada = TRUE;
            }
            $factura->vencimiento = Date('d-m-Y', strtotime($factura->fecha . ' ' . $formapago->vencimiento));
        }

        if (!$eje0) {
            $this->new_error_msg("Ningún ejercicio encontrado.");
            $continuar = FALSE;
        } else if ($this->regularizacion->get_fecha_inside($factura->fecha)) {
            /*
             * comprobamos que la fecha de la factura no esté dentro de un periodo de
             * IVA regularizado.
             */
            $this->new_error_msg('El ' . FS_IVA . ' de ese periodo ya ha sido regularizado. No se pueden añadir más facturas en esa fecha.');
            $continuar = FALSE;
        } else if ($factura->save()) {
            foreach ($albaranes as $alb) {
                foreach ($alb->get_lineas() as $l) {
                    $n = new linea_factura_cliente();
                    $n->idalbaran = $alb->idalbaran;
                    $n->idfactura = $factura->idfactura;
                    $n->cantidad = $l->cantidad;
                    $n->codimpuesto = $l->codimpuesto;
                    $n->descripcion = $l->descripcion;
                    $n->dtopor = $l->dtopor;
                    $n->irpf = $l->irpf;
                    $n->iva = $l->iva;
                    $n->pvpsindto = $l->pvpsindto;
                    $n->pvptotal = $l->pvptotal;
                    $n->pvpunitario = $l->pvpunitario;
                    $n->recargo = $l->recargo;
                    $n->referencia = $l->referencia;

                    if (!$n->save()) {
                        $continuar = FALSE;
                        $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
                        break;
                    }
                }
            }

            if ($continuar) {
                foreach ($albaranes as $alb) {
                    $alb->idfactura = $factura->idfactura;
                    $alb->ptefactura = FALSE;

                    if (!$alb->save()) {
                        $this->new_error_msg("¡Imposible vincular el " . FS_ALBARAN . " con la nueva factura!");
                        $continuar = FALSE;
                        break;
                    }
                }

                if ($continuar) {
                    $continuar = $this->generar_asiento_cliente($factura);
                    $this->total++;
                } else {
                    if ($factura->delete()) {
                        $this->new_error_msg("La factura se ha borrado.");
                    } else
                        $this->new_error_msg("¡Imposible borrar la factura!");
                }
            }
            else {
                if ($factura->delete()) {
                    $this->new_error_msg("La factura se ha borrado.");
                } else
                    $this->new_error_msg("¡Imposible borrar la factura!");
            }
        } else
            $this->new_error_msg("¡Imposible guardar la factura!");

        return $continuar;
    }

    public function pendientes_venta() {
        $alblist = array();

        $sql = "SELECT * FROM albaranescli WHERE ptefactura = true";
        if ($this->opciones['megafac_codserie'] != '') {
            $sql .= " AND codserie = " . $this->serie->var2str($this->opciones['megafac_codserie']);
        }
        if ($this->fecha_albaran) {
            $sql .= " AND fecha <= " . $this->serie->var2str(\date('Y-m-d',strtotime($this->fecha_albaran)));
        }

        $data = $this->db->select_limit($sql . ' ORDER BY fecha ASC, hora ASC', 20, 0);
        if ($data) {
            foreach ($data as $d) {
                $alblist[] = new albaran_cliente($d);
            }
        }

        return $alblist;
    }

    public function total_pendientes_venta() {
        $total = 0;
        $subtotal = array();
        $sql = "SELECT fecha, count(idalbaran) as total FROM albaranescli WHERE ptefactura = true";
        /**
        if ($this->opciones['megafac_codserie'] != '') {
            $sql .= " AND codserie = " . $this->serie->var2str($this->opciones['megafac_codserie']);
        }
         * 
         */
        if ($this->fecha_albaran) {
            $sql .= " AND fecha <= " . $this->serie->var2str(\date('Y-m-d',strtotime($this->fecha_albaran)));
            $sql .= "GROUP BY fecha ORDER BY fecha";
        }

        $data = $this->db->select($sql);
        if ($data) {
            foreach($data as $d){
                $total += intval($d['total']);
                $subtotal[$d['fecha']]=intval($d['total']);
            }
        }

        $resultados = array('total'=>$total,'lista'=>$subtotal);
        return $resultados;
    }

    public function total_pedidos_pendientes() {
        $total = 0;
        $subtotal = array();
        $sql = "SELECT fecha, count(idpedido) as total FROM pedidoscli WHERE idalbaran IS NULL AND status = 0 ";
        /**
        if ($this->opciones['megafac_codserie'] != '') {
            $sql .= " AND codserie = " . $this->serie->var2str($this->opciones['megafac_codserie']);
        }
         * 
         */
        if ($this->fecha_albaran) {
            $sql .= " AND fecha <= " . $this->serie->var2str(\date('Y-m-d',strtotime($this->fecha_albaran)));
            $sql .= "GROUP BY fecha ORDER BY fecha";
        }

        $data = $this->db->select($sql);
        if ($data) {
            foreach($data as $d){
                $total += intval($d['total']);
                $subtotal[$d['fecha']]=intval($d['total']);
            }
        }

        $resultados = array('total'=>$total,'lista'=>$subtotal);
        return $resultados;
    }

    private function generar_asiento_cliente($factura, $forzar = FALSE, $soloasiento = FALSE) {
        $ok = TRUE;

        if ($this->empresa->contintegrada OR $forzar) {
            $this->asiento_factura->soloasiento = $soloasiento;
            $ok = $this->asiento_factura->generar_asiento_venta($factura);

            foreach ($this->asiento_factura->errors as $err) {
                $this->new_error_msg($err);
            }

            foreach ($this->asiento_factura->messages as $msg) {
                $this->new_message($msg);
            }
        }

        return $ok;
    }

    private function share_extensions() {
        $extensions = array(
            array(
                'name' => 'ventas_megafacturador',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_albaranes',
                'type' => 'button',
                'text' => '<i class="fa fa-check-square-o" aria-hidden="true"></i><span class="hidden-xs">&nbsp; Facturación masiva</span>',
                'params' => ''
            )
        );
        foreach ($extensions as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }

}
