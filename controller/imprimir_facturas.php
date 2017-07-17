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
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('linea_factura_cliente.php');
require_model('asiento_factura.php');
require_model('ncf_ventas.php');
require_model('ncf_rango.php');

class imprimir_facturas extends fs_controller {

    public $agente;
    public $codalmacen;
    public $almacenes;
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
    public $listar;

    public function __construct() {
        parent::__construct(__CLASS__, 'Imprimir ' . ucfirst(FS_FACTURAS), 'ventas');
    }

    protected function private_core() {
        $this->agente = new agente();
        $this->almacenes = new almacen();
        $this->factura = new factura_cliente();
        $this->huecos = array();
        $this->serie = new serie();
        $this->ncf_ventas = new ncf_ventas();
        $this->mostrar = 'todo';
        $this->listar = 'todo';
        $mostrar = \filter_input(INPUT_GET, 'mostrar');
        if ($mostrar) {
            $this->mostrar = $mostrar;
            setcookie('ventas_fac_mostrar', $this->mostrar, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['ventas_fac_mostrar'])) {
            $this->mostrar = $_COOKIE['ventas_fac_mostrar'];
        }

        if (\filter_input(INPUT_POST, 'listar')) {
            $listar = \filter_input(INPUT_POST, 'listar');
            $this->listar = ($listar) ? $listar : $this->listar;
        }

        $this->offset = 0;
        if (isset($_REQUEST['offset'])) {
            $this->offset = intval($_REQUEST['offset']);
        }

        $this->order = 'facturascli.fecha DESC';
        $order = \filter_input(INPUT_GET, 'order');
        if ($order) {
            if ($order == 'fecha_desc') {
                $this->order = 'facturascli.fecha DESC';
            } else if ($order == 'fecha_asc') {
                $this->order = 'facturascli.fecha ASC';
            } else if ($order == 'vencimiento_desc') {
                $this->order = 'vencimiento DESC';
            } else if ($order == 'vencimiento_asc') {
                $this->order = 'vencimiento ASC';
            }

            setcookie('ventas_fac_order', $this->order, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['ventas_fac_order'])) {
            $this->order = $_COOKIE['ventas_fac_order'];
        }

        $buscar_lineas = \filter_input(INPUT_POST, 'buscar_lineas');
        $ref = \filter_input(INPUT_GET, 'ref');
        //Variables por REQUEST
        $buscar_cliente = $this->filter_request('buscar_cliente');
        $codagente = $this->filter_request('codagente');
        $codcliente = $this->filter_request('codcliente');
        $codserie = $this->filter_request('codserie');
        $codalmacen = $this->filter_request('codalmacen');
        $desde = $this->filter_request('desde');
        $hasta = $this->filter_request('hasta');
        if ($buscar_lineas) {
            $this->buscar_lineas();
        } else if ($buscar_cliente) {
            $this->buscar_cliente();
        } else if ($ref) {
            $this->template = 'extension/ventas_facturas_articulo';

            $articulo = new articulo();
            $this->articulo = $articulo->get($ref);

            $linea = new linea_factura_cliente();
            $this->resultados = $linea->all_from_articulo($ref, $this->offset);
        } else {
            $this->huecos = $this->factura->huecos();
            $this->cliente = FALSE;
            $this->codagente = '';
            $this->codalmacen = '';
            $this->codserie = '';
            $this->desde = '';
            $this->hasta = '';
            $this->num_resultados = '';
            $this->total_resultados = '';
            $this->total_resultados_comision = 0;
            $this->total_resultados_txt = '';
            
            if (!$mostrar AND ( $codagente OR $codcliente OR $codserie)) {
                /**
                 * si obtenermos un codagente, un codcliente o un codserie pasamos directamente
                 * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                 * entonces nos indica donde tenemos que estar.
                 */
                $this->mostrar = 'buscar';
            }

            if ($codcliente) {
                if ($codcliente != '') {
                    $cli0 = new cliente();
                    $this->cliente = $cli0->get($codcliente);
                }
            }

            if ($codagente) {
                $this->codagente = $codagente;
            }

            if ($codserie) {
                $this->codserie = $codserie;
            }

            if ($codalmacen) {
                $this->codalmacen = $codalmacen;
            }

            if ($desde) {
                $this->desde = $desde;
                $this->hasta = $hasta;
            }

            /// añadimos segundo nivel de ordenación
            $order2 = '';
            if (substr($this->order, -4) == 'DESC') {
                $order2 = ', codigo DESC';
            } else {
                $order2 = ', codigo ASC';
            }

            if ($this->mostrar == 'buscar') {
                $this->buscar($order2);
            } else {
                $this->resultados = $this->factura->all($this->offset, FS_ITEM_LIMIT, $this->order . $order2);
            }
        }
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;
        $buscar_cliente = $this->filter_request('buscar_cliente');
        $cli0 = new cliente();
        $json = array();
        foreach ($cli0->search($buscar_cliente) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $buscar_cliente, 'suggestions' => $json));
    }

    public function paginas() {
        $codcliente = '';
        if ($this->cliente) {
            $codcliente = $this->cliente->codcliente;
        }

        $url = $this->url() . "&mostrar=" . $this->mostrar
                . "&query=" . $this->query
                . "&codserie=" . $this->codserie
                . "&codagente=" . $this->codagente
                . "&codalmacen=" . $this->codalmacen
                . "&codcliente=" . $codcliente
                . "&desde=" . $this->desde
                . "&hasta=" . $this->hasta;

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        if ($this->mostrar == 'buscar') {
            $total = $this->num_resultados;
        } else {
            $total = $this->total_registros();
        }

        /// añadimos todas la página
        while ($num < $total) {
            $paginas[$i] = array(
                'url' => $url . "&offset=" . ($i * FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num == $this->offset)
            );

            if ($num == $this->offset) {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach ($paginas as $j => $value) {
            $enmedio = intval($i / 2);

            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j > 1 AND $j < $actual - 5 AND $j != $enmedio) OR ( $j > $actual + 5 AND $j < $i - 1 AND $j != $enmedio)) {
                unset($paginas[$j]);
            }
        }

        if (count($paginas) > 1) {
            return $paginas;
        } else {
            return array();
        }
    }

    public function buscar_lineas() {
        /// cambiamos la plantilla HTML
        $this->template = 'ajax/ventas_lineas_facturas';
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $buscar_lineas = \filter_input(INPUT_POST, 'buscar_lineas');
        $buscar_lineas_o = \filter_input(INPUT_POST, 'buscar_lineas_o');
        $this->buscar_lineas = $buscar_lineas;
        $linea = new linea_factura_cliente();

        if ($codcliente) {
            $this->lineas = $linea->search_from_cliente2($codcliente, $this->buscar_lineas, $buscar_lineas_o);
        } else {
            $this->lineas = $linea->search($this->buscar_lineas);
        }
    }

    private function total_registros() {
        $data = $this->db->select("SELECT COUNT(idfactura) as total FROM facturascli;");
        if ($data) {
            return intval($data[0]['total']);
        } else
            return 0;
    }

    private function buscar($order2) {
        $this->resultados = array();
        $this->num_resultados = 0;
        $query = $this->agente->no_html(strtolower($this->query));
        $sql = " FROM facturascli ";
        $where = 'WHERE ';

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

        if ($this->codalmacen != '') {

            $sql .= $where . "codalmacen = " . $this->agente->var2str($this->codalmacen);
            $where = ' AND ';
        }

        if ($this->cliente) {
            $sql .= $where . "codcliente = " . $this->agente->var2str($this->cliente->codcliente);
            $where = ' AND ';
        }

        if ($this->codserie != '') {
            $sql .= $where . "codserie = " . $this->agente->var2str($this->codserie);
            $where = ' AND ';
        }

        if ($this->desde != '') {
            $sql .= $where . "fecha >= " . $this->agente->var2str($this->desde);
            $where = ' AND ';
        }

        if ($this->hasta != '') {
            $sql .= $where . "fecha <= " . $this->agente->var2str($this->hasta);
            $where = ' AND ';
        }

        switch ($this->listar) {
            case "validas":
                $sql .= $where . " anulada = FALSE ";
                $where = ' AND ';
                break;
            case "rectificativas":
                $sql .= $where . " idfacturarect IS NOT NULL ";
                $where = ' AND ';
                break;
            case "anuladas":
                $sql .= $where . " anulada = TRUE ";
                $where = ' AND ';
                break;
            default:

                break;
        }

        $data = $this->db->select("SELECT COUNT(idfactura) as total" . $sql);
        if ($data) {
            $this->num_resultados = intval($data[0]['total']);

            $data2 = $this->db->select_limit("SELECT *" . $sql . " ORDER BY " . $this->order . $order2, FS_ITEM_LIMIT, $this->offset);
            if ($data2) {
                foreach ($data2 as $d) {
                    $values = new factura_cliente($d);
                    $this->resultados[] = $values;
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
    
    /**
    * Función para devolver el valor de una variable pasada ya sea por POST o GET
    * @param type string
    * @return type string
    */
   private function filter_request($nombre){
       $nombre_post = \filter_input(INPUT_POST, $nombre);
       $nombre_get = \filter_input(INPUT_GET, $nombre);
       return ($nombre_post)?$nombre_post:$nombre_get;
   }

}
