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
require_model('articulo.php');
require_model('albaran_cliente.php');
require_model('pedido_cliente.php');
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

    public $documento;
    public $numasientos;
    public $opciones;
    public $serie;
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
    private $asiento_factura;
    private $articulos;
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
        $this->articulos = new articulo();
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
        $this->series_elegidas_pedidos = array();
        $this->series_elegidas_albaranes = array();
        $this->fecha_albaranes_gen = \date('d-m-Y', strtotime('+1 day'));
        $this->fecha_facturas_gen = \date('d-m-Y', strtotime('+1 day'));
        $this->lista_pedidos_pendientes = array();
        $this->lista_albaranes_pendientes = array();
        $this->lista_pedidos_pendientes_total = 0;
        $this->lista_albaranes_pendientes_total = 0;
        $this->share_extensions();

        $documento = filter_input(INPUT_GET, 'documento');
        $procesar_g = filter_input(INPUT_GET, 'procesar');
        $procesar_p = filter_input(INPUT_POST, 'procesar');

        $fecha_pedido_desde = filter_input(INPUT_POST, 'fecha_pedido_desde');
        $fecha_pedido_hasta = filter_input(INPUT_POST, 'fecha_pedido_hasta');
        $fecha_albaran_desde = filter_input(INPUT_POST, 'fecha_albaran_desde');
        $fecha_albaran_hasta = filter_input(INPUT_POST, 'fecha_albaran_hasta');
        $fecha_albaranes_gen = filter_input(INPUT_POST, 'fecha_albaranes_gen');
        $fecha_facturas_gen = filter_input(INPUT_POST, 'fecha_facturas_gen');

        $this->fecha_pedido_desde = ($fecha_pedido_desde) ? $fecha_pedido_desde : \date('Y-m-01');
        $this->fecha_pedido_hasta = ($fecha_pedido_hasta) ? $fecha_pedido_hasta : \date('Y-m-d');
        $this->fecha_albaran_desde = ($fecha_albaran_desde) ? $fecha_albaran_desde : \date('Y-m-01');
        $this->fecha_albaran_hasta = ($fecha_albaran_hasta) ? $fecha_albaran_hasta : \date('Y-m-d');
        $this->fecha_albaranes_gen = ($fecha_albaranes_gen) ? $fecha_albaranes_gen : $this->fecha_albaranes_gen;
        $this->fecha_facturas_gen = ($fecha_facturas_gen) ? $fecha_facturas_gen : $this->fecha_facturas_gen;

        $procesar = ($procesar_g) ? $procesar_g : $procesar_p;

        if ($documento == 'pedidos') {
            $this->series_elegidas_pedidos = filter_input(INPUT_POST, 'serie_pedidos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $this->fechas_elegidas_pedidos = filter_input(INPUT_POST, 'fecha_pedidos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $accion = filter_input(INPUT_POST, 'accion');
            if ($accion == 'buscar_pedidos') {
                $buscar = $this->total_pedidos_pendientes();
                $this->lista_pedidos_pendientes = $buscar['lista'];
                $this->lista_pedidos_pendientes_total = $buscar['total'];
            } elseif ($accion == 'generar_albaranes' AND $procesar == 'TRUE') {
                $this->generar_albaranes();
            }
        } elseif ($documento == 'albaranes') {
            $this->series_elegidas_albaranes = (array) filter_input(INPUT_POST, 'serie_albaranes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $this->fechas_elegidas_albaranes = (array) filter_input(INPUT_POST, 'fecha_albaranes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $accion = filter_input(INPUT_POST, 'accion');
            if ($accion == 'buscar_albaranes') {
                $buscar = $this->total_pendientes_venta();
                $this->lista_albaranes_pendientes = $buscar['lista'];
                $this->lista_albaranes_pendientes_total = $buscar['total'];
            } elseif ($accion == 'generar_facturas' AND $procesar == 'TRUE') {
                $this->generar_facturas();
            }
        }
    }

    /**
     * Funcion de generar_albaranes
     * Esta función genera los albaranes del listado de pedidos pendientes
     * generados en base a las series de los pedidos y las fechas elegidas
     */
    private function generar_albaranes() {
        $total_pedidos = $this->total_pedidos_pendientes();
        $total = $total_pedidos['total'];
        $contador = 0;
        foreach ($this->pedidos_pendientes() as $pedido) {
            $continuar = FALSE;
            $albaran = new albaran_cliente();
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
            $albaran->neto = $pedido->neto;
            $albaran->nombrecliente = $pedido->nombrecliente;
            $albaran->observaciones = $pedido->observaciones;
            $albaran->provincia = $pedido->provincia;
            $albaran->total = $pedido->total;
            $albaran->totaliva = $pedido->totaliva;
            $albaran->numero2 = $pedido->numero2;
            $albaran->irpf = $pedido->irpf;
            $albaran->porcomision = $pedido->porcomision;
            $albaran->totalirpf = $pedido->totalirpf;
            $albaran->totalrecargo = $pedido->totalrecargo;

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

            if( is_null($albaran->codagente) )
            {
                $albaran->codagente = $this->user->codagente;
            }

            /**
             * Obtenemos el ejercicio para la fecha de hoy (puede que
             * no sea el mismo ejercicio que el del pedido, por ejemplo
             * si hemos cambiado de año)
             */
            $eje0 = $this->ejercicio->get_by_fecha($albaran->fecha, FALSE);
            if ($eje0) {
                $albaran->codejercicio = $eje0->codejercicio;
            }

            if (!$eje0) {
                $this->new_error_msg("Ejercicio no encontrado.");
            } else if (!$eje0->abierto()) {
                $this->new_error_msg("El ejercicio está cerrado.");
            } else if ($albaran->save()) {
                $trazabilidad = FALSE;
                $continuar = TRUE;
                $contador++;
                $art0 = new articulo();
                foreach ($pedido->get_lineas() as $l) {
                    $articulo_pedido = $art0->get($l->referencia);
                    if(!$articulo_pedido->controlstock AND $articulo_pedido->stockfis>=$l->cantidad){
                        $n = new linea_albaran_cliente();
                        $n->idlineapedido = $l->idlinea;
                        $n->idpedido = $l->idpedido;
                        $n->idalbaran = $albaran->idalbaran;
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

                        if ($n->save()) {
                            /// descontamos del stock
                            if (!is_null($n->referencia)) {
                                $articulo = $art0->get($n->referencia);
                                if ($articulo) {
                                    $articulo->sum_stock($albaran->codalmacen, 0 - $l->cantidad);
                                }
                            }
                        } else {
                            $continuar = FALSE;
                            $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
                            break;
                        }
                    }else{
                        //$albaran->total -= $l->pvptotal;
                        //$albaran->totaliva -= $l->iva;
                        //$albaran->neto -= $l->pvptotal;
                        $this->new_error_msg("¡No hay stock del artículo " . $l->referencia . " en el pedido ".$pedido->codigo."! ");
                    }
                }

                if ($continuar) {
                    $pedido->idalbaran = $albaran->idalbaran;
                    $pedido->fechasalida = $albaran->fecha;

                    if (!$pedido->save()) {
                        $this->new_error_msg("¡Imposible vincular el " . FS_PEDIDO . " con el nuevo " . FS_ALBARAN . "!");
                        if ($albaran->delete()) {
                            $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
                        } else {
                            $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
                        }
                    }
                } else {
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
        $this->new_message('Se procesaron correctamente '.$contador.' de '.$total.' pedidos.');
    }

    /**
     * Funcion de generar_facturas
     * Esta función genera las facturas del listado de albaranes pendientes
     * generados en base a las series de los albaranes y las fechas elegidas
     */
    private function generar_facturas(){
        
        $total_albaranes = $this->total_pendientes_venta();
        $total = $total_albaranes['total'];
        $contador = 0;
        foreach ($this->pendientes_venta() as $albaran) {
            $cliente = $this->cliente->get($albaran->codcliente);
            /*
            * Verificación de disponibilidad del Número de NCF para República Dominicana
            */
            //Obtenemos el tipo de comprobante a generar para el cliente
            $tipo_comprobante_d = $this->ncf_entidad_tipo->get($this->empresa->id, $albaran->codcliente, 'CLI');
            $tipo_comprobante = $tipo_comprobante_d->tipo_comprobante;
            if(strlen($albaran->cifnif)<9 AND $tipo_comprobante == '01'){
               $this->new_error_msg('El cliente del '.FS_ALBARAN.' '.$albaran->numero.' tiene un tipo de comprobante 01 pero no tiene Cédula o RNC Válido, por favor corrija esta información!');
            }
            //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
            $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $albaran->codalmacen, $tipo_comprobante, $albaran->codpago);
            if ($numero_ncf['NCF'] == 'NO_DISPONIBLE')
            {
                $this->new_error_msg('No hay números NCF disponibles del tipo '.$tipo_comprobante.', el '. FS_ALBARAN .' '.$albaran->numero.' no será facturado.');
            }else{
                $contador++;
                $factura = new factura_cliente();
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

                if( is_null($factura->codagente) )
                {
                   $factura->codagente = $this->user->codagente;
                }

                /// asignamos el ejercicio que corresponde a la fecha elegida
                $eje0 = $this->ejercicio->get_by_fecha($this->fecha_facturas_gen);
                if($eje0)
                {
                   $factura->codejercicio = $eje0->codejercicio;
                   $factura->set_fecha_hora($this->fecha_facturas_gen, \date('H:i:s'));
                }

                /// comprobamos la forma de pago para saber si hay que marcar la factura como pagada
                $forma0 = new forma_pago();
                $formapago = $forma0->get($factura->codpago);
                if($formapago)
                {
                   if($formapago->genrecibos == 'Pagados')
                   {
                      $factura->pagada = TRUE;
                   }
                   $factura->vencimiento = $formapago->calcular_vencimiento($factura->fecha, $cliente->diaspago);
                }

                $regularizacion = new regularizacion_iva();

                if( !$eje0 )
                {
                   $this->new_error_msg("Ejercicio no encontrado o está cerrado.");
                }
                else if( !$eje0->abierto() )
                {
                   $this->new_error_msg("El ejercicio está cerrado.");
                }
                else if( $regularizacion->get_fecha_inside($factura->fecha) )
                {
                   $this->new_error_msg("El ".FS_IVA." de ese periodo ya ha sido regularizado. No se pueden añadir más facturas en esa fecha.");
                }
                else if( $factura->save() )
                {
                   $continuar = TRUE;
                   $ncf_controller = new helper_ncf();
                   $ncf_controller->guardar_ncf($this->empresa->id, $factura, $tipo_comprobante, $numero_ncf);
                   foreach($albaran->get_lineas() as $l)
                   {
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

                      if( !$n->save() )
                      {
                         $continuar = FALSE;
                         $this->new_error_msg("¡Imposible guardar la línea el artículo ".$n->referencia."! ");
                         break;
                      }
                   }

                   if($continuar)
                   {
                      $albaran->idfactura = $factura->idfactura;
                      $albaran->ptefactura = FALSE;
                      if( $albaran->save() )
                      {
                         $this->generar_asiento_cliente($factura);
                      } else {
                         $this->new_error_msg("¡Imposible vincular el ".FS_ALBARAN." con la nueva factura!");
                         if( $factura->delete() )
                         {
                            $this->new_error_msg("La factura se ha borrado.");
                         } else {
                            $this->new_error_msg("¡Imposible borrar la factura!");
                         }
                      }
                   } else {
                      if( $factura->delete() )
                      {
                         $this->new_error_msg("La factura se ha borrado.");
                      } else {
                         $this->new_error_msg("¡Imposible borrar la factura!");
                      }
                   }
                } else {
                   $this->new_error_msg("¡Imposible guardar la factura!");
                }
            }
        }
        $this->new_message('Se procesaron correctamente '.$contador.' de '.$total.' '.FS_ALBARANES);
    }

    public function pendientes_venta() {
        $alblist = array();

        $sql = "SELECT * FROM albaranescli WHERE ptefactura = true";
        if (!empty($this->series_elegidas_albaranes)) {
            $series = $this->array_to_text($this->series_elegidas_albaranes);
            $sql .= " AND codserie IN (" . $series . ") ";
        }
        if ($this->fecha_albaran_desde and empty($this->fechas_elegidas_albaranes)) {
            $sql .= " AND fecha >= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_desde)))
                    . " AND fecha <= " . $this->serie->var2str(\date('Y-m-d', strtotime($this->fecha_albaran_hasta)));
        } elseif ($this->fechas_elegidas_albaranes) {
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

    public function total_pendientes_venta() {
        $total = 0;
        $subtotal = array();
        $sql = "SELECT fecha, count(idalbaran) as total FROM albaranescli WHERE ptefactura = true";
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

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $total += intval($d['total']);
                $subtotal[$d['fecha']] = intval($d['total']);
            }
        }

        $resultados = array('total' => $total, 'lista' => $subtotal);
        return $resultados;
    }

    public function pedidos_pendientes() {
        $pedlist = array();

        $sql = "SELECT * FROM pedidoscli WHERE idalbaran IS NULL AND status = 0 ";
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

    public function total_pedidos_pendientes() {
        $total = 0;
        $subtotal = array();
        $sql = "SELECT fecha, count(idpedido) as total FROM pedidoscli WHERE idalbaran IS NULL AND status = 0 ";

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
                $total += intval($d['total']);
                $subtotal[$d['fecha']] = intval($d['total']);
            }
        }

        $resultados = array('total' => $total, 'lista' => $subtotal);
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

    private function array_to_text(Array $array) {
        $substring = "";
        foreach ($array as $item) {
            $substring .= "'" . $item . "',";
        }
        $string = substr($substring, 0, strlen($substring) - 1);
        return $string;
    }

    private function date_to_text(Array $array) {
        $substring = "";
        foreach ($array as $item) {
            $substring .= "'" . \date('Y-m-d', strtotime($item)) . "',";
        }
        $string = substr($substring, 0, strlen($substring) - 1);
        return $string;
    }

    private function share_extensions() {
        $extensions = array(
            array(
                'name' => 'daterangepicker_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/2/daterangepicker.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'moment_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/1/moment-with-locales.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'daterangepicker_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="' . FS_PATH . 'plugins/republica_dominicana/view/css/daterangepicker.css" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'republica_dominicana_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link href="' . FS_PATH . 'plugins/republica_dominicana/view/css/rd.css?build=' . rand(1, 1000) . '" rel="stylesheet" type="text/css"/>',
                'params' => ''
            ),
            array(
                'name' => 'republica_dominicana_commons_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/rd_common.js" type="text/javascript"></script>',
                'params' => ''
            ),
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
