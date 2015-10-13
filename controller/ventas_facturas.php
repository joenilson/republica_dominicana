<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('agente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('linea_factura_cliente.php');
require_model('asiento_factura.php');
require_model('ncf_ventas.php');
require_model('ncf_rango.php');

class ventas_facturas extends fs_controller {

    public $agente;
    public $articulo;
    public $buscar_lineas;
    public $cliente;
    public $codagente;
    public $codserie;
    public $desde;
    public $factura;
    public $hasta;
    public $huecos;
    public $lineas;
    public $mostrar;
    public $num_resultados;
    public $offset;
    public $order;
    public $resultados;
    public $serie;
    public $total_resultados;
    public $total_resultados_comision;
    public $total_resultados_txt;
    public $ncf_ventas;
    public $ncf_resultados;

    public function __construct() {
        parent::__construct(__CLASS__, ucfirst(FS_FACTURAS) . ' de cliente', 'ventas');
    }

    protected function private_core() {
        $this->agente = new agente();
        $this->factura = new factura_cliente();
        $this->huecos = array();
        $this->serie = new serie();
        $this->ncf_ventas = new ncf_ventas();

        $this->mostrar = 'todo';
        if (isset($_GET['mostrar'])) {
            $this->mostrar = $_GET['mostrar'];
            setcookie('ventas_fac_mostrar', $this->mostrar, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['ventas_fac_mostrar'])) {
            $this->mostrar = $_COOKIE['ventas_fac_mostrar'];
        }

        $this->offset = 0;
        if (isset($_REQUEST['offset'])) {
            $this->offset = intval($_REQUEST['offset']);
        }

        $this->order = 'facturascli.fecha DESC';
        if (isset($_GET['order'])) {
            if ($_GET['order'] == 'fecha_desc') {
                $this->order = 'facturascli.fecha DESC';
            } else if ($_GET['order'] == 'fecha_asc') {
                $this->order = 'facturascli.fecha ASC';
            } else if ($_GET['order'] == 'vencimiento_desc') {
                $this->order = 'vencimiento DESC';
            } else if ($_GET['order'] == 'vencimiento_asc') {
                $this->order = 'vencimiento ASC';
            }

            setcookie('ventas_fac_order', $this->order, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['ventas_fac_order'])) {
            $this->order = $_COOKIE['ventas_fac_order'];
        }

        if (isset($_POST['buscar_lineas'])) {
            $this->buscar_lineas();
        } else if (isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        } else if (isset($_GET['ref'])) {
            $this->template = 'extension/ventas_facturas_articulo';

            $articulo = new articulo();
            $this->articulo = $articulo->get($_GET['ref']);

            $linea = new linea_factura_cliente();
            $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
        } else {
            $this->share_extension();
            $this->huecos = $this->factura->huecos();
            $this->cliente = FALSE;
            $this->codagente = '';
            $this->codserie = '';
            $this->desde = '';
            $this->hasta = '';
            $this->num_resultados = '';
            $this->total_resultados = '';
            $this->total_resultados_comision = 0;
            $this->total_resultados_txt = '';

            if (isset($_GET['delete'])) {
                $this->delete_factura();
            } elseif (isset($_GET['anular'])) {
                $this->anular_factura();
            } else {
                if (!isset($_GET['mostrar']) AND ( isset($_REQUEST['codagente']) OR isset($_REQUEST['codcliente']))) {
                    /**
                     * si obtenermos un codagente o un codcliente pasamos direcatemente
                     * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                     * entonces nos indica donde tenemos que estar.
                     */
                    $this->mostrar = 'buscar';
                }

                if (isset($_REQUEST['codcliente'])) {
                    if ($_REQUEST['codcliente'] != '') {
                        $cli0 = new cliente();
                        $this->cliente = $cli0->get($_REQUEST['codcliente']);
                    }
                }

                if (isset($_REQUEST['codagente'])) {
                    $this->codagente = $_REQUEST['codagente'];
                }

                if (isset($_REQUEST['codserie'])) {
                    $this->codserie = $_REQUEST['codserie'];
                    $this->desde = $_REQUEST['desde'];
                    $this->hasta = $_REQUEST['hasta'];
                }
            }

            /// añadimos segundo nivel de ordenación
            $order2 = '';
            if (substr($this->order, -4) == 'DESC') {
                $order2 = ', codigo DESC';
            } else {
                $order2 = ', codigo ASC';
            }

            if ($this->mostrar == 'sinpagar') {
                $listado_resultante = $this->factura_all_sin_pagar($this->offset, FS_ITEM_LIMIT, $this->order . $order2);
                $this->resultados = $listado_resultante['facturas'];
                $this->ncf_resultados = $listado_resultante['ncf'];

                if ($this->offset == 0) {
                    $this->total_resultados = 0;
                    $this->total_resultados_txt = 'Suma total de esta página:';
                    foreach ($this->resultados as $fac) {
                        $this->total_resultados += $fac->total;
                    }
                }
            } else if ($this->mostrar == 'buscar') {
                $this->buscar($order2);
            } else {
                $listado_resultante = $this->factura_all($this->offset, FS_ITEM_LIMIT, $this->order . $order2);
                $this->resultados = $listado_resultante['facturas'];
                $this->ncf_resultados = $listado_resultante['ncf'];
            }
        }
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $cli0 = new cliente();
        $json = array();
        foreach ($cli0->search($_REQUEST['buscar_cliente']) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
    }

    public function anterior_url() {
        $url = '';
        $codcliente = '';
        if ($this->cliente) {
            $codcliente = $this->cliente->codcliente;
        }

        if ($this->offset > 0) {
            $url = $this->url() . "&mostrar=" . $this->mostrar
                    . "&query=" . $this->query
                    . "&codserie=" . $this->codserie
                    . "&codagente=" . $this->codagente
                    . "&codcliente=" . $codcliente
                    . "&desde=" . $this->desde
                    . "&hasta=" . $this->hasta
                    . "&offset=" . ($this->offset - FS_ITEM_LIMIT);
        }

        return $url;
    }

    public function siguiente_url() {
        $url = '';
        $codcliente = '';
        if ($this->cliente) {
            $codcliente = $this->cliente->codcliente;
        }

        if (count($this->resultados) == FS_ITEM_LIMIT) {
            $url = $this->url() . "&mostrar=" . $this->mostrar
                    . "&query=" . $this->query
                    . "&codserie=" . $this->codserie
                    . "&codagente=" . $this->codagente
                    . "&codcliente=" . $codcliente
                    . "&desde=" . $this->desde
                    . "&hasta=" . $this->hasta
                    . "&offset=" . ($this->offset + FS_ITEM_LIMIT);
        }

        return $url;
    }

    public function buscar_lineas() {
        /// cambiamos la plantilla HTML
        $this->template = 'ajax/ventas_lineas_facturas';

        $this->buscar_lineas = $_POST['buscar_lineas'];
        $linea = new linea_factura_cliente();

        if (isset($_POST['codcliente'])) {
            $this->lineas = $linea->search_from_cliente2($_POST['codcliente'], $this->buscar_lineas, $_POST['buscar_lineas_o']);
        } else {
            $this->lineas = $linea->search($this->buscar_lineas);
        }
    }

    private function share_extension() {
        /// añadimos las extensiones para clientes, agentes y artículos
        $extensiones = array(
            array(
                'name' => 'facturas_cliente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; Facturas',
                'params' => ''
            ),
            array(
                'name' => 'facturas_agente',
                'page_from' => __CLASS__,
                'page_to' => 'admin_agente',
                'type' => 'button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; Facturas de cliente',
                'params' => ''
            ),
            array(
                'name' => 'facturas_articulo',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_articulo',
                'type' => 'tab_button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; Facturas de cliente',
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

    public function total_sinpagar() {
        $data = $this->db->select("SELECT COUNT(idfactura) as total FROM facturascli WHERE pagada = false;");
        if ($data) {
            return intval($data[0]['total']);
        } else
            return 0;
    }

    private function buscar($order2) {
        $this->resultados = array();
        $this->ncf_resultados = array();
        $this->num_resultados = 0;
        $query = $this->agente->no_html(strtolower($this->query));
        $sql = " FROM facturascli, ncf_ventas WHERE idfactura = documento AND entidad = codcliente ";
        $where = 'AND ';

        if ($this->query != '') {
            $sql .= $where;
            if (is_numeric($query)) {
                $sql .= "(codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%')";
            } else {
                $sql .= "(lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                        . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%')";
            }
            $where = ' AND ';
        }

        if ($this->codagente != '') {
            $sql .= $where . "codagente = " . $this->agente->var2str($this->codagente);
            $where = ' AND ';
        }

        if ($this->cliente) {
            $sql .= $where . "codcliente = " . $this->agente->var2str($this->cliente->codcliente) . " AND codcliente = entidad ";
            $where = ' AND ';
        }

        if ($this->codserie != '') {
            $sql .= $where . "codserie = " . $this->agente->var2str($this->codserie);
            $where = ' AND ';
        }

        if ($this->desde != '') {
            $sql .= $where . "facturascli.fecha >= " . $this->agente->var2str($this->desde);
            $where = ' AND ';
        }

        if ($this->hasta != '') {
            $sql .= $where . "facturascli.fecha <= " . $this->agente->var2str($this->hasta) . " AND facturascli.fecha = ncf_ventas.fecha ";
            $where = ' AND ';
        }

        $data = $this->db->select("SELECT COUNT(idfactura) as total" . $sql);
        if ($data) {
            $this->num_resultados = intval($data[0]['total']);

            $data2 = $this->db->select_limit("SELECT *" . $sql . " ORDER BY " . $this->order . $order2, FS_ITEM_LIMIT, $this->offset);
            if ($data2) {
                foreach ($data2 as $d) {
                    $this->resultados[] = new factura_cliente($d);
                    $this->ncf_resultados[$d['idfactura']] = new ncf_ventas($d);
                }
            }

            $data2 = $this->db->select("SELECT SUM(total) as total" . $sql);
            if ($data2) {
                $this->total_resultados = floatval($data2[0]['total']);
                $this->total_resultados_txt = 'Suma total de los resultados:';
            }

            if ($this->codagente !== '') {
                /// calculamos la comisión del empleado
                $data2 = $this->db->select("SELECT SUM(neto*porcomision/100) as total" . $sql);
                if ($data2) {
                    $this->total_resultados_comision = floatval($data2[0]['total']);
                }
            }
        }
    }

    private function delete_factura() {
        $fact = $this->factura->get($_GET['delete']);
        if ($fact) {
            /// ¿Sumamos stock?
            $art0 = new articulo();
            foreach ($fact->get_lineas() as $linea) {
                if (is_null($linea->idalbaran)) {
                    $articulo = $art0->get($linea->referencia);
                    if ($articulo) {
                        $articulo->sum_stock($fact->codalmacen, $linea->cantidad);
                    }
                }
            }

            if ($fact->delete()) {
                $this->new_message("Factura eliminada correctamente.");
            } else
                $this->new_error_msg("¡Imposible eliminar la factura!");
        } else
            $this->new_error_msg("Factura no encontrada.");
    }

    private function anular_factura() {
        /*
         * Traemos la factura que vamos a anular / corregir
         */
        $fact = $this->factura->get($_GET['anular']);

        /*
         * Verificación de disponibilidad del Número de NCF para Notas de Crédito
         */
        $tipo_comprobante = '04';
        $this->ncf_rango = new ncf_rango();
        $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            $continuar = FALSE;
            return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', no se podrá generar la Nota de Crédito.');
        }

        $fact_lineas = $fact->get_lineas();
        if ($fact) {
            $fact->deabono = TRUE;
            $fact->idfacturarect = $fact->idfactura;
            $fact->codigorect = $fact->codigo;

            /// Regresamos el stock al almacén
            $art0 = new articulo();
            foreach ($fact_lineas as $linea) {
                if (is_null($linea->idalbaran)) {
                    $articulo = $art0->get($linea->referencia);
                    if ($articulo) {
                        $articulo->sum_stock($fact->codalmacen, $linea->cantidad);
                    }
                }
            }

            /*
             * Mantenemos los valores de la factura menos su id para no repetir toda la data
             */
            $fact->idfactura = NULL;
            $fact->codigo = NULL;
            if ($fact->save()) {
                $linea_factura = new linea_factura_cliente();
                /// Guardamos la información sin modificar el stock
                foreach ($fact_lineas as $linea) {
                    $linea->idfactura = $fact->idfactura;
                    $linea->idlinea = NULL;
                    $linea_factura = $linea;
                    $linea_factura->save();
                }
                /*
                 * Generamos el asiento de venta y le agregamos el parámetro de $tipo en este caso con el valor 'inverso'
                 */
                $asiento_factura = new asiento_factura();
                $asiento_factura->soloasiento = TRUE;
                if ($asiento_factura->generar_asiento_venta($fact, 'inverso')) {
                    $this->new_message("<a href='" . $asiento_factura->asiento->url() . "'>Asiento</a> generado correctamente.");
                    $this->new_change('Nota de Crédito ' . $fact->codigo, $fact->url());
                }
                /*
                 * Luego de que todo este correcto generamos el NCF la Nota de Credito 
                 */
                //Con el codigo del almacen desde donde facturaremos generamos el número de NCF
                $numero_ncf = $this->ncf_rango->generate($this->empresa->id, $fact->codalmacen, $tipo_comprobante);
                $this->guardar_ncf($this->empresa->id, $fact, $tipo_comprobante, $numero_ncf);
                
                $this->new_message("Factura anulada correctamente, se generó la nota de crédito: ".$numero_ncf['NCF']);
            } else
                $this->new_error_msg("¡Imposible anular la factura!");
        } else
            $this->new_error_msg("Factura no encontrada.");
    }

    private function factura_all_sin_pagar($offset = 0, $limit = FS_ITEM_LIMIT, $order = 'vencimiento ASC, codigo ASC') {
        $faclist = array();
        $ncflist = array();
        $sql = "SELECT * FROM facturascli, ncf_ventas WHERE idfactura = documento AND pagada = false ORDER BY " . $order;

        $data = $this->db->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $f) {
                $faclist[] = new factura_cliente($f);
                $ncflist[$f['idfactura']] = new ncf_ventas($f);
            }
        }

        return array('facturas' => $faclist, 'ncf' => $ncflist);
    }

    private function factura_all($offset = 0, $limit = FS_ITEM_LIMIT, $order = 'facturascli.fecha DESC, codigo DESC') {
        $faclist = array();
        $ncflist = array();
        $data = $this->db->select_limit("SELECT * FROM facturascli, ncf_ventas WHERE idfactura = documento ORDER BY " . $order, $limit, $offset);
        if ($data) {
            foreach ($data as $f) {
                $faclist[] = new factura_cliente($f);
                $ncflist[$f['idfactura']] = new ncf_ventas($f);
            }
        }

        return array('facturas' => $faclist, 'ncf' => $ncflist);
    }

    private function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf) {
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', la factura ' . $factura->idfactura . ' se creo sin NCF.');
        } else {
            $ncf_factura = new ncf_ventas();
            $ncf_factura->idempresa = $idempresa;
            $ncf_factura->codalmacen = $factura->codalmacen;
            $ncf_factura->entidad = $factura->codcliente;
            $ncf_factura->cifnif = $factura->cifnif;
            $ncf_factura->documento = $factura->idfactura;
            $ncf_factura->documento_modifica = NULL;
            $ncf_factura->fecha = $factura->fecha;
            $ncf_factura->tipo_comprobante = $tipo_comprobante;
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->ncf = NULL;
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            if($factura->deabono){
                $sql = new ncf_ventas();
                $data0 = $sql->get_ncf($idempresa, $factura->idfacturarect, $factura->codcliente);
                $ncf_factura->documento_modifica = $factura->idfacturarect;
                $ncf_factura->ncf_modifica = $data0->ncf;
            }
            if (!$ncf_factura->save()) {
                return $this->new_error_msg('Ocurrió un error al grabar la factura ' . $factura->idfactura . ' con el NCF: ' . $numero_ncf['NCF'] . ' Anule la factura e intentelo nuevamente.');
            } else {
                $this->ncf_rango->update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
    }

}
