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
require_once 'plugins/republica_dominicana/extras/rd_controller.php';

/**
 * Esta es una copia de funcionalidades minimas del plugin MegaFacturador
 * su uso está restringido solo para las facturas de venta
 * no se aplicará a un menu, sino que será un boton dentro de la lista de
 * Albaranes
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ventas_megafacturador extends rd_controller
{
    public $codalmacen;
    public $codalmacen_ped;
    public $codalmacen_alb;
    public $documento;
    public $numasientos;
    public $opciones;
    public $series_elegidas;
    public $series_elegidas_albaranes;
    public $series_elegidas_pedidos;
    public $url_recarga;
    public $fecha_pedido;
    public $fecha_pedido_desde;
    public $fecha_pedido_hasta;
    public $fecha_albaran;
    public $fecha_albaran_desde;
    public $fecha_albaran_hasta;
    public $fecha_albaranes_gen;
    public $fecha_facturas_gen;
    public $fechas_elegidas;
    public $fechas_elegidas_albaranes;
    public $fechas_elegidas_pedidos;
    public $lista_pedidos_pendientes;
    public $lista_pedidos_pendientes_total;
    public $lista_albaranes_pendientes;
    public $lista_albaranes_pendientes_total;
    public $asiento_factura;
    public $articulos;
    public $cliente;
    public $ejercicio;
    public $ejercicios;
    public $forma_pago;
    public $formas_pago;
    public $regularizacion;
    public $total;
    public $stock;
    public $cliente_rutas;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Facturación masiva', 'ventas', false, true, false);
    }

    protected function private_core()
    {
        parent::private_core();

        $this->init_variables();

        $documento = filter_input(INPUT_GET, 'documento');
        $this->codalmacen = filter_input(INPUT_POST, 'codalmacen');

        $this->fecha_pedido_desde = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_pedido_desde'), \date('Y-m-01'));
        $this->fecha_pedido_hasta = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_pedido_hasta'), \date('Y-m-d'));
        $this->fecha_albaran_desde = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_albaran_desde'), \date('Y-m-01'));
        $this->fecha_albaran_hasta = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_albaran_hasta'), \date('Y-m-d'));
        $this->fecha_albaranes_gen = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_albaranes_gen'), $this->fecha_albaranes_gen);
        $this->fecha_facturas_gen = $this->confirmarValor(\filter_input(INPUT_POST, 'fecha_facturas_gen'), $this->fecha_facturas_gen);

        $procesar = $this->filter_request('procesar');
        if ($documento == 'pedidos') {
            $this->procesar_pedidos($procesar);
        } elseif ($documento == 'albaranes') {
            $this->procesar_albaranes($procesar);
        }
    }

    public function procesar_pedidos($procesar)
    {
        $this->series_elegidas_pedidos = filter_input(INPUT_POST, 'serie_pedidos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->fechas_elegidas_pedidos = filter_input(INPUT_POST, 'fecha_pedidos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->codalmacen_ped = filter_input(INPUT_POST, 'codalmacen');
        $accion = filter_input(INPUT_POST, 'accion');
        if ($accion == 'buscar_pedidos') {
            $buscar = $this->total_pedidos_pendientes();
            $this->lista_pedidos_pendientes = $buscar['lista'];
            $this->lista_pedidos_pendientes_total = $buscar['total'];
        } elseif ($accion == 'generar_albaranes' and $procesar == 'TRUE') {
            $this->generar_albaranes();
        }
    }

    /**
     * Funcion para Procesar los Albaranes y convertirlos en Facturas
     * @param string $procesar
     */
    public function procesar_albaranes($procesar)
    {
        $this->series_elegidas_albaranes = (array) filter_input(INPUT_POST, 'serie_albaranes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->fechas_elegidas_albaranes = (array) filter_input(INPUT_POST, 'fecha_albaranes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->codalmacen_alb = filter_input(INPUT_POST, 'codalmacen');
        $accion = filter_input(INPUT_POST, 'accion');
        if ($accion == 'buscar_albaranes') {
            $buscar = $this->total_pendientes_venta();
            $this->lista_albaranes_pendientes = $buscar['lista'];
            $this->lista_albaranes_pendientes_total = $buscar['total'];
        } elseif ($accion == 'generar_facturas' and $procesar == 'TRUE') {
            $this->generar_facturas();
        }
    }

    public function init_variables()
    {
        $this->articulos = new articulo();
        $this->asiento_factura = new asiento_factura();
        $this->cliente = new cliente();
        $this->ejercicio = new ejercicio();
        $this->ejercicios = array();
        $this->forma_pago = new forma_pago();
        $this->stock = new stock();
        $this->formas_pago = $this->forma_pago->all();
        $this->numasientos = 0;
        $this->regularizacion = new regularizacion_iva();
        $this->series_elegidas_pedidos = array();
        $this->series_elegidas_albaranes = array();
        $this->fecha_albaranes_gen = \date('d-m-Y', strtotime('+1 day'));
        $this->fecha_facturas_gen = \date('d-m-Y', strtotime('+1 day'));
        $this->lista_pedidos_pendientes = array();
        $this->lista_albaranes_pendientes = array();
        $this->lista_pedidos_pendientes_total = 0;
        $this->lista_albaranes_pendientes_total = 0;
        $this->share_extensions();
    }

    public function crear_albaran($pedido, &$contador, &$errores)
    {
        $continuar = false;
        $albaran = new albaran_cliente();
        //Para el plugin distribucion
        if (property_exists('albaran_cliente', 'codruta')) {
            $albaran->codruta = $pedido->codruta;
            $albaran->codvendedor = $pedido->codvendedor;
        }

        $albaran->apartado = $pedido->apartado;
        $albaran->cifnif = $pedido->cifnif;
        $albaran->ciudad = $pedido->ciudad;
        $albaran->fecha = $this->fecha_albaranes_gen;
        $albaran->hora = \date('H:i:s');
        $albaran->codagente = $pedido->codagente;
        $albaran->codalmacen = $pedido->codalmacen;
        $albaran->codcliente = $pedido->codcliente;
        $albaran->coddir = $pedido->coddir;
        $albaran->coddivisa = $pedido->coddivisa;
        $albaran->tasaconv = $pedido->tasaconv;
        $albaran->codpago = $pedido->codpago;
        $albaran->codpais = $pedido->codpais;
        $albaran->codpostal = $pedido->codpostal;
        $albaran->codserie = $pedido->codserie;
        $albaran->direccion = $pedido->direccion;
        $albaran->nombrecliente = $pedido->nombrecliente;
        $albaran->observaciones = $pedido->observaciones;
        $albaran->provincia = $pedido->provincia;
        $albaran->numero2 = $pedido->numero2;
        $albaran->porcomision = $pedido->porcomision;
        $albaran->neto = 0;
        $albaran->total = 0;
        $albaran->totaliva = 0;
        $albaran->irpf = 0;
        $albaran->totalirpf = 0;
        $albaran->totalrecargo = 0;

        $albaran->envio_nombre = $pedido->envio_nombre;
        $albaran->envio_apellidos = $pedido->envio_apellidos;
        $albaran->envio_codtrans = $pedido->envio_codtrans;
        $albaran->envio_codigo = $pedido->envio_codigo;
        $albaran->envio_codpais = $pedido->envio_codpais;
        $albaran->envio_provincia = $pedido->envio_provincia;
        $albaran->envio_ciudad = $pedido->envio_ciudad;
        $albaran->envio_codpostal = $pedido->envio_codpostal;
        $albaran->envio_direccion = $pedido->envio_direccion;
        $albaran->envio_apartado = $pedido->envio_apartado;

        if (is_null($albaran->codagente)) {
            $albaran->codagente = $this->user->codagente;
        }

        /**
         * Obtenemos el ejercicio para la fecha de hoy (puede que
         * no sea el mismo ejercicio que el del pedido, por ejemplo
         * si hemos cambiado de año)
         */
        $eje0 = $this->ejercicio->get_by_fecha($albaran->fecha, false);
        if ($eje0) {
            $albaran->codejercicio = $eje0->codejercicio;
        }

        if (!$eje0) {
            $this->new_error_msg("Ejercicio no encontrado.");
        } elseif (!$eje0->abierto()) {
            $this->new_error_msg("El ejercicio está cerrado.");
        } elseif ($albaran->save()) {
            $trazabilidad = false;
            $continuar = true;
            $lista_errores = array();
            $art0 = new articulo();
            foreach ($pedido->get_lineas() as $l) {
                //Si el articulo existe
                $articulo = false;
                if (!is_null($l->referencia)) {
                    $articulo = $art0->get($l->referencia);
                    $articulo_stock = $this->stock->total_from_articulo($articulo->referencia, $pedido->codalmacen);
                }
                $n = new linea_albaran_cliente();
                $n->idlineapedido = $l->idlinea;
                $n->idpedido = $l->idpedido;
                $n->idalbaran = $albaran->idalbaran;
                $n->cantidad = $l->cantidad;
                $n->codimpuesto = $l->codimpuesto;
                $n->codcombinacion = $l->codcombinacion;
                $n->descripcion = $l->descripcion;
                $n->dtopor = $l->dtopor;
                $n->irpf = $l->irpf;
                $n->iva = $l->iva;
                $n->pvpsindto = $l->pvpsindto;
                $n->pvptotal = $l->pvptotal;
                $n->pvpunitario = $l->pvpunitario;
                $n->recargo = $l->recargo;
                $n->referencia = $l->referencia;

                if ($n->save()) {
                    /// descontamos del stock
                    if ($n->referencia) {
                        if ($articulo) {
                            $articulo->sum_stock($albaran->codalmacen, 0 - $l->cantidad, false, $l->codcombinacion);
                            if ($articulo->trazabilidad) {
                                $trazabilidad = true;
                            }
                        }
                    }

                    $albaran->neto += $n->pvptotal;
                    $albaran->totaliva += ($n->pvptotal * $n->iva / 100);
                    $albaran->totalirpf += ($n->pvptotal * $n->irpf / 100);
                    $albaran->totalrecargo += ($n->pvptotal * $n->recargo / 100);

                    if ($n->irpf > $albaran->irpf) {
                        $albaran->irpf = $n->irpf;
                    }
                } else {
                    $continuar = false;
                    $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
                    break;
                }
            }

            //Validamos la información nueva del albarán
            $albaran->neto = round($albaran->neto, FS_NF0);
            $albaran->totaliva = round($albaran->totaliva, FS_NF0);
            $albaran->totalirpf = round($albaran->totalirpf, FS_NF0);
            $albaran->totalrecargo = round($albaran->totalrecargo, FS_NF0);
            $albaran->total = $albaran->neto + $albaran->totaliva - $albaran->totalirpf + $albaran->totalrecargo;

            if ($continuar) {
                if ($albaran->save()) {
                    $pedido->idalbaran = $albaran->idalbaran;
                    $pedido->fechasalida = $albaran->fecha;
                    $contador++;
                    if (!$pedido->save()) {
                        $this->new_error_msg("¡Imposible vincular el " . FS_PEDIDO . " con el nuevo " . FS_ALBARAN . "!");
                        if ($albaran->delete()) {
                            $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado porque no se pudo enlazar con el " . FS_PEDIDO . " " . $pedido->codigo . ".");
                        } else {
                            $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
                        }
                    }
                } else {
                    $this->new_error_msg('Ocurrio un error al intentar grabar el ' . FS_ALBARAN . ', hubo un problema con los artículos del ' . FS_PEDIDO . ' <a href="' . $pedido->url() . '" target="_blank">' . $pedido->codigo . '</a> verifique el mismo e intente generar un albaran');
                }
            } else {
                //Corregimos el stock si es que los articulos tienen control de stock
                foreach ($albaran->get_lineas() as $linea) {
                    if ($linea->referencia) {
                        $art1 = $this->articulos->get($linea->referencia);
                        $articulo_stock = $this->stock->total_from_articulo($linea->referencia, $albaran->codalmacen);
                        if (!isset($lista_errores[$linea->referencia])) {
                            $art1->sum_stock($albaran->codalmacen, $linea->cantidad, false, $linea->codcombinacion);
                        }
                    }
                }
                $errores++;
                if ($albaran->delete()) {
                    $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
                } else {
                    $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
                }
            }
        } else {
            $this->new_error_msg("¡Imposible guardar el " . FS_ALBARAN . "!");
        }
    }

    public function crear_lineas_albaran($albaran,$pedido)
    {

    }

    /**
     * Funcion de generar_albaranes
     * Esta función genera los albaranes del listado de pedidos pendientes
     * generados en base a las series de los pedidos y las fechas elegidas
     */
    private function generar_albaranes()
    {
        $total_pedidos = $this->total_pedidos_pendientes();
        $total = $total_pedidos['total'];
        $contador = 0;
        $errores = 0;
        foreach ($this->pedidos_pendientes() as $pedido) {
            if ($this->comprobar_stock($pedido)) {
                $this->crear_albaran($pedido, $contador, $errores);
            } else {
                $this->new_error_msg("¡Artículos del " . FS_PEDIDO . " <a href=\"" . $pedido->url() . "\" target=\"_blank\">" . $pedido->codigo . "</a> sin stock suficiente!");
            }
        }
        $this->new_message('Se procesaron correctamente ' . $contador . ' de ' . $total . ' pedidos y ' . $errores . ' no se procesaron por errores en stock o la información.');
    }

    public function crear_factura($albaran,$cliente,$numero_ncf,$tipo_comprobante)
    {
        $factura = new factura_cliente();
        //Para el plugin distribucion
        if (property_exists('factura_cliente', 'codruta')) {
            $factura->codruta = $albaran->codruta;
            $factura->codvendedor = $albaran->codvendedor;
        }
        $factura->apartado = $albaran->apartado;
        $factura->cifnif = $albaran->cifnif;
        $factura->ciudad = $albaran->ciudad;
        $factura->codagente = $albaran->codagente;
        $factura->codalmacen = $albaran->codalmacen;
        $factura->codcliente = $albaran->codcliente;
        $factura->coddir = $albaran->coddir;
        $factura->coddivisa = $albaran->coddivisa;
        $factura->tasaconv = $albaran->tasaconv;
        $factura->codpago = $albaran->codpago;
        $factura->codpais = $albaran->codpais;
        $factura->codpostal = $albaran->codpostal;
        $factura->codserie = $albaran->codserie;
        $factura->direccion = $albaran->direccion;
        $factura->neto = $albaran->neto;
        $factura->nombrecliente = $albaran->nombrecliente;
        $factura->observaciones = $albaran->observaciones;
        $factura->provincia = $albaran->provincia;
        $factura->envio_apartado = $albaran->envio_apartado;
        $factura->envio_apellidos = $albaran->envio_apellidos;
        $factura->envio_ciudad = $albaran->envio_ciudad;
        $factura->envio_codigo = $albaran->envio_codigo;
        $factura->envio_codpais = $albaran->envio_codpais;
        $factura->envio_codpostal = $albaran->envio_codpostal;
        $factura->envio_codtrans = $albaran->envio_codtrans;
        $factura->envio_direccion = $albaran->envio_direccion;
        $factura->envio_nombre = $albaran->envio_nombre;
        $factura->envio_provincia = $albaran->envio_provincia;
        $factura->total = $albaran->total;
        $factura->totaliva = $albaran->totaliva;
        $factura->numero2 = $numero_ncf['NCF'];
        $factura->irpf = $albaran->irpf;
        $factura->totalirpf = $albaran->totalirpf;
        $factura->totalrecargo = $albaran->totalrecargo;
        $factura->porcomision = $albaran->porcomision;

        if (is_null($factura->codagente)) {
            $factura->codagente = $this->user->codagente;
        }
        /// asignamos el ejercicio que corresponde a la fecha elegida
        $eje0 = $this->ejercicio->get_by_fecha($this->fecha_facturas_gen);
        if ($eje0) {
            $factura->codejercicio = $eje0->codejercicio;
            $factura->set_fecha_hora($this->fecha_facturas_gen, \date('H:i:s'));
        }

        /// comprobamos la forma de pago para saber si hay que marcar la factura como pagada
        $forma0 = new forma_pago();
        $formapago = $forma0->get($factura->codpago);
        if ($formapago) {
            if ($formapago->genrecibos == 'Pagados') {
                $factura->pagada = true;
            }
            $factura->vencimiento = $formapago->calcular_vencimiento($factura->fecha, $cliente->diaspago);
        }

        $regularizacion = new regularizacion_iva();

        if (!$eje0) {
            $this->new_error_msg("Ejercicio no encontrado o está cerrado.");
        } elseif (!$eje0->abierto()) {
            $this->new_error_msg("El ejercicio está cerrado.");
        } elseif ($regularizacion->get_fecha_inside($factura->fecha)) {
            $this->new_error_msg("El " . FS_IVA . " de ese periodo ya ha sido regularizado. No se pueden añadir más facturas en esa fecha.");
        } elseif ($factura->save()) {
            $continuar = true;
            $this->guardar_ncf($this->empresa->id, $factura, $tipo_comprobante, $numero_ncf);
            if ($this->tesoreria) {
                require_model('pago_recibo_cliente.php');
                require_model('recibo_cliente.php');
                require_model('recibo_factura.php');
                $this->nuevo_recibo($factura);
            }
            foreach ($albaran->get_lineas() as $l) {
                $n = new linea_factura_cliente();
                $n->idalbaran = $l->idalbaran;
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
                $n->orden = $l->orden;
                $n->mostrar_cantidad = $l->mostrar_cantidad;
                $n->mostrar_precio = $l->mostrar_precio;
                $n->codcombinacion = $l->codcombinacion;

                if (!$n->save()) {
                    $continuar = false;
                    $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
                    break;
                }
            }

            if ($continuar) {
                $albaran->idfactura = $factura->idfactura;
                $albaran->ptefactura = false;
                if ($albaran->save()) {
                    $this->generar_asiento_cliente($factura);
                } else {
                    $this->new_error_msg("¡Imposible vincular el " . FS_ALBARAN . " con la nueva factura!");
                    if ($factura->delete()) {
                        $this->new_error_msg("La factura se ha borrado.");
                    } else {
                        $this->new_error_msg("¡Imposible borrar la factura!");
                    }
                }
            } else {
                if ($factura->delete()) {
                    $this->new_error_msg("La factura se ha borrado.");
                } else {
                    $this->new_error_msg("¡Imposible borrar la factura!");
                }
            }
        } else {
            $this->new_error_msg("¡Imposible guardar la factura!");
        }
    }

    /**
     * Funcion de generar_facturas
     * Esta función genera las facturas del listado de albaranes pendientes
     * generados en base a las series de los albaranes y las fechas elegidas
     */
    private function generar_facturas()
    {
        $total_albaranes = $this->total_pendientes_venta();
        $total = $total_albaranes['total'];
        $contador = 0;
        foreach ($this->pendientes_venta() as $albaran) {
            $cliente = $this->cliente->get($albaran->codcliente);
            /*
             * Verificación de disponibilidad del Número de NCF para República Dominicana
             */
            //Obtenemos el tipo de comprobante a generar para el cliente
            $tipo_comprobante = $this->ncf_tipo_comprobante($this->empresa->id, $albaran->codcliente);
            if (strlen($albaran->cifnif) < 9 and $tipo_comprobante == '01') {
                $this->new_error_msg('El cliente del ' . FS_ALBARAN . ' ' . $albaran->numero . ' tiene un tipo de comprobante 01 pero no tiene Cédula o RNC Válido, por favor corrija esta información!');
            }else{
                //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
                $numero_ncf = $this->generar_numero_ncf($this->empresa->id, $albaran->codalmacen, $tipo_comprobante, $albaran->codpago);
                $contador++;
                $this->crear_factura($albaran,$cliente,$numero_ncf,$tipo_comprobante);
            }
        }
        $this->new_message('Se procesaron correctamente ' . $contador . ' de ' . $total . ' ' . FS_ALBARANES);
    }

    private function nuevo_recibo($factura)
    {
        $recibo = new recibo_cliente();
        $recibo->apartado = $factura->apartado;
        $recibo->cifnif = $factura->cifnif;
        $recibo->ciudad = $factura->ciudad;
        $recibo->codcliente = $factura->codcliente;
        $recibo->coddir = $factura->coddir;
        $recibo->coddivisa = $factura->coddivisa;
        $recibo->tasaconv = $factura->tasaconv;
        $recibo->codpago = $factura->codpago;
        $recibo->codserie = $factura->codserie;
        $recibo->numero = $recibo->new_numero($factura->idfactura);
        $recibo->codigo = $factura->codigo . '-' . sprintf('%02s', $recibo->numero);
        $recibo->codpais = $factura->codpais;
        $recibo->codpostal = $factura->codpostal;
        $recibo->direccion = $factura->direccion;
        $recibo->estado = 'Emitido';
        $recibo->fecha = $factura->fecha;
        $recibo->fechav = $factura->vencimiento;
        $recibo->idfactura = $factura->idfactura;
        $recibo->importe = floatval($factura->total);
        $recibo->nombrecliente = $factura->nombrecliente;
        $recibo->provincia = $factura->provincia;

        $cbc = new cuenta_banco_cliente();
        foreach ($cbc->all_from_cliente($factura->codcliente) as $cuenta) {
            if (is_null($recibo->codcuenta) or $cuenta->principal) {
                $recibo->codcuenta = $cuenta->codcuenta;
                $recibo->iban = $cuenta->iban;
                $recibo->swift = $cuenta->swift;
            }
        }
        $recibo->save();
    }

    public function pendientes_venta()
    {
        $alblist = array();

        $sql = "SELECT * FROM albaranescli WHERE ptefactura = true";
        if (!empty($this->codalmacen)) {
            $sql .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen) . " ";
        }
        if (!empty($this->series_elegidas_albaranes)) {
            $series = $this->array_to_text($this->series_elegidas_albaranes);
            $sql .= " AND codserie IN (" . $series . ") ";
        }
        if ($this->fecha_albaran_desde and empty($this->fechas_elegidas_albaranes)) {
            $sql .= " AND fecha >= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_desde)))
                    . " AND fecha <= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_hasta)));
        } elseif (!empty($this->fechas_elegidas_albaranes)) {
            $fechas = $this->date_to_text($this->fechas_elegidas_albaranes);
            $sql .= " AND fecha IN (" . $fechas . ") ";
        }

        $data = $this->db->select($sql . ' ORDER BY fecha ASC, hora ASC');
        if ($data) {
            foreach ($data as $d) {
                $alblist[] = new albaran_cliente($d);
            }
        }

        return $alblist;
    }

    public function total_pendientes_venta()
    {
        $total_alb = 0;
        $subtotal_alb = array();
        $sql = "SELECT fecha, count(idalbaran) as total FROM albaranescli WHERE ptefactura = true";
        if (!empty($this->codalmacen)) {
            $sql .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen) . " ";
        }
        if (!empty($this->series_elegidas_albaranes)) {
            $series = $this->array_to_text($this->series_elegidas_albaranes);
            $sql .= " AND codserie IN (" . $series . ") ";
        }
        if ($this->fecha_albaran_desde and empty($this->fechas_elegidas_albaranes)) {
            $sql .= " AND fecha >= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_desde)))
                    . " AND fecha <= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_hasta)));
            $sql .= " GROUP BY fecha ORDER BY fecha";
        } elseif ($this->fechas_elegidas_albaranes) {
            $fechas = $this->date_to_text($this->fechas_elegidas_albaranes);
            $sql .= " AND fecha IN (" . $fechas . ") ";
            $sql .= " GROUP BY fecha ORDER BY fecha";
        }

        $data_alb = $this->db->select($sql);
        if ($data_alb) {
            foreach ($data_alb as $d) {
                $total_alb += intval($d['total']);
                $subtotal_alb[$d['fecha']] = intval($d['total']);
            }
        }

        $resultados = array('total' => $total_alb, 'lista' => $subtotal_alb);
        return $resultados;
    }

    public function pedidos_pendientes()
    {
        $pedlist = array();

        $sql = "SELECT * FROM pedidoscli WHERE idalbaran IS NULL AND status = 0 ";
        if (!empty($this->codalmacen)) {
            $sql .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen) . " ";
        }
        if (!empty($this->series_elegidas_pedidos)) {
            $series = $this->array_to_text($this->series_elegidas_pedidos);
            $sql .= " AND codserie IN (" . $series . ") ";
        }
        if ($this->fecha_pedido_desde and empty($this->fechas_elegidas_pedidos)) {
            $sql .= " AND fecha >= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_pedido_desde)))
                    . " AND fecha <= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_pedido_hasta)));
        } elseif ($this->fechas_elegidas_pedidos) {
            $fechas = $this->date_to_text($this->fechas_elegidas_pedidos);
            $sql .= " AND fecha IN (" . $fechas . ") ";
        }

        $data = $this->db->select($sql . ' ORDER BY fecha ASC, hora ASC');
        if ($data) {
            foreach ($data as $d) {
                $pedlist[] = new pedido_cliente($d);
            }
        }

        return $pedlist;
    }

    public function total_pedidos_pendientes()
    {
        $total_ped = 0;
        $subtotal_ped = array();
        $sql = "SELECT fecha, count(idpedido) as total FROM pedidoscli WHERE idalbaran IS NULL AND status = 0 ";

        if (!empty($this->codalmacen)) {
            $sql .= " AND codalmacen = " . $this->empresa->var2str($this->codalmacen) . " ";
        }

        if (!empty($this->series_elegidas_pedidos)) {
            $series = $this->array_to_text($this->series_elegidas_pedidos);
            $sql .= " AND codserie IN (" . $series . ") ";
        }
        if ($this->fecha_pedido_desde and empty($this->fechas_elegidas_pedidos)) {
            $sql .= " AND fecha >= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_pedido_desde)))
                    . " AND fecha <= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_pedido_hasta)));
            $sql .= " GROUP BY fecha ORDER BY fecha";
        } elseif ($this->fechas_elegidas_pedidos) {
            $fechas = $this->date_to_text($this->fechas_elegidas_pedidos);
            $sql .= " AND fecha IN (" . $fechas . ") ";
            $sql .= " GROUP BY fecha ORDER BY fecha";
        }
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $total_ped += intval($d['total']);
                $subtotal_ped[$d['fecha']] = intval($d['total']);
            }
        }

        $resultados = array('total' => $total_ped, 'lista' => $subtotal_ped);
        return $resultados;
    }

    private function generar_asiento_cliente($factura, $forzar = false, $soloasiento = false)
    {
        $ok = true;

        if ($this->empresa->contintegrada or $forzar) {
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

    /**
     * Comprueba el stock de cada uno de los artículos del documento.
     * Devuelve TRUE si hay suficiente stock.
     * @return boolean
     */
    private function comprobar_stock($documento)
    {
        $ok = true;
        $stock0 = new stock();
        $art0 = new articulo();
        foreach ($documento->get_lineas() as $linea) {
            if ($linea->referencia and $art0->get($linea->referencia)) {
                $articulo = $art0->get($linea->referencia);
                $stockfis = $articulo->stockfis;
                if ($this->multi_almacen) {
                    $stockfis = $stock0->total_from_articulo($articulo->referencia, $documento->codalmacen);
                }

                if (!$articulo->controlstock and $linea->cantidad > $stockfis) {
                    $ok = false;
                }

                if (!$ok) {
                    $this->new_error_msg('No hay suficiente stock del artículo ' . $linea->referencia);
                    break;
                }
            }
        }

        return $ok;
    }

    private function array_to_text(array $array)
    {
        $substring = "";
        foreach ($array as $item) {
            $substring .= "'" . $item . "',";
        }
        $string = substr($substring, 0, strlen($substring) - 1);
        return $string;
    }

    private function date_to_text(array $array)
    {
        $substring = "";
        foreach ($array as $item) {
            $substring .= "'" . \date('Y-m-d', strtotime($item)) . "',";
        }
        $string = substr($substring, 0, strlen($substring) - 1);
        return $string;
    }

    private function share_extensions()
    {
        $extensions2 = array(
            array(
                'name' => '005_daterangepicker_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/2/daterangepicker.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_moment_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/1/moment-with-locales.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_daterangepicker_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="' . FS_PATH . 'plugins/republica_dominicana/view/css/daterangepicker.min.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => '002_republica_dominicana_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="' . FS_PATH . 'plugins/republica_dominicana/view/css/rd.css?build=' . rand(1, 1000) . '" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => '001_republica_dominicana_commons_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/rd_common.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_ventas_megafacturador',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_albaranes',
                'type' => 'button',
                'text' => '<i class="fa fa-check-square-o" aria-hidden="true"></i><span class="hidden-xs">&nbsp; Facturación masiva</span>',
                'params' => ''
            )
        );

        foreach ($extensions2 as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }
}
