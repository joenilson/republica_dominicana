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
require_once 'plugins/republica_dominicana/extras/rd_controller.php';
class errores_ncf extends rd_controller
{
    
    public $almacenes;
    public $almacenes_seleccionados;
    public $codalmacen;
    public $fecha_inicio;
    public $fecha_fin;
    public $ncf_ventas;
    public $resultados;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Errores NCF', 'informes', false, true, false);
    }
    
    protected function private_core()
    {
        parent::private_core();
        $this->share_extensions();
        $this->almacenes = new almacen();
        $this->init_variables();
        $codalmacen = $this->filter_request_array('codalmacen');
        $almacen_defecto = false;
        if (count($this->almacenes->all())===1) {
            $almacen_defecto = $this->empresa->codalmacen;
        }

        $this->codalmacen = ($codalmacen)?$codalmacen:$almacen_defecto;
        $this->almacenes_seleccionados = (is_array($this->codalmacen))?$this->codalmacen:array($this->codalmacen);

        $this->verificarRequest();
    }
    
    private function init_variables()
    {
        $this->fecha_inicio = \date('01-m-Y');
        $this->fecha_fin = \date('d-m-Y');
        $this->resultados = array();
        $this->ncf_ventas = new ncf_ventas();
    }
    
    public function verificarRequest()
    {
        $offset = \filter_input(INPUT_GET, 'offset');
        $limit = \filter_input(INPUT_GET, 'limit');
        $search = \filter_input(INPUT_GET, 'search');
        $sort = \filter_input(INPUT_GET, 'sort');
        $order = \filter_input(INPUT_GET, 'order');
        $this->fecha_inicio = $this->filter_request('inicio');
        $this->fecha_fin = $this->filter_request('fin');
        $this->offset = $this->confirmarValor($offset,0);
        $this->limit = $this->confirmarValor($limit,FS_ITEM_LIMIT);
        $this->search = $this->confirmarValor($search,false);
        $this->sort = ($sort and $sort!='undefined')?$sort:'fecha, ncf';
        $this->order = ($order and $order!='undefined')?$order:'ASC';
        if (\filter_input(INPUT_POST, 'buscar')) {
            $this->generarResultados();
        }elseif($this->filter_request('corregir')){
            $this->corregirFacturas();
        }
    }
    
    public function comprobarNCF($ncf)
    {
        if($this->ncf_ventas->get($this->empresa->id, $ncf)){
            return true;
        }
        return false;
    }
    
    public function corregirFactura($factura)
    {
        $fact = $this->ncf_ventas->get_ncf($this->empresa->id, $factura->idfactura, $factura->codcliente);
        $cli = new cliente();
        if($fact->ncf == ''){
            $this->ncf_length = (\strtotime($factura->fecha) < (\strtotime('01-05-2018'))) ? 19 : $this->ncf_length;
            if($this->comprobarNCF($factura->numero2) !== true && strlen($factura->numero2) === $this->ncf_length){
                guardar_ncf($this->empresa->id, $factura, get_tipo_comprobante($factura->numero2), $factura->numero2);
            }else{
                $cliente = $cli->get($factura->codcliente);
                $tipo_comprobante = ncf_tipo_comprobante($this->empresa->id, $factura->codcliente, 'CLI');
                $numero_ncf = generar_comprobante_fiscal($cliente, $tipo_comprobante, $factura->codalmacen);
                $factura->numero2 = $numero_ncf['NCF'];
                $this->corregirFactura($factura);
            }
        }
    }

    public function corregirFacturas()
    {
        $data = array();
        $this->template = false;
        $facturas = \filter_input(INPUT_GET, 'facturas', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
        //Iteramos el array
        $factura_cliente = new factura_cliente();
        foreach($facturas as $id){
            $factura = $factura_cliente->get($id);
            $this->corregirFactura($factura);
        }
        $data['success']=true;
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    public function generarResultados()
    {
        $pos_substr = (strtotime($this->fecha_fin) < (strtotime('01-05-2018'))) ? 10 : 0;
        $sql_ncf_no_existen = "SELECT cifnif,codalmacen,idfactura,idfacturarect,codcliente,fecha,1,numero2,substr(numero2,$pos_substr,2) as tipo_ncf,'admin',1,null as area_impresion ".
            " FROM facturascli ".
            " WHERE idfactura NOT IN (SELECT documento from ncf_ventas where fecha BETWEEN ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->fecha_inicio)))." AND ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->fecha_fin))).")".
            " and fecha BETWEEN ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->fecha_inicio)))." AND ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->fecha_fin))).
            " ORDER BY fecha, idfactura, numero2".
            ";";
        $resultados = $this->db->select($sql_ncf_no_existen);
        if($resultados){
            foreach($resultados as $res){
                $line = (object) $res;
                $this->resultados[] = $line;
            }
        }
        
    }
    
    private function share_extensions()
    {
        $extensiones = array(
            array(
                'name' => '001_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/locale/bootstrap-table-es-MX.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '006_'.__CLASS__.'_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/republica_dominicana/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_'.__CLASS__.'_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/republica_dominicana/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),

            array(
                'name' => '002_'.__CLASS__.'_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/republica_dominicana/view/css/bootstrap-table.min.css"/>',
                'params' => ''
            ),
        );

        foreach ($extensiones as $ext) {
            $fext = new fs_extension($ext);
            if (!$fext->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
    
}
