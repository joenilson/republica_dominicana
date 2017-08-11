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
 * Description of informe_estadocuenta
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_estadocuenta extends rd_controller
{
    public $cliente;
    public $desde;
    public $hasta;
    public $estado;
    public $resultados;
    public $resultados_d30;
    public $resultados_d60;
    public $resultados_d90;
    public $resultados_d120;
    public $resultados_md120;
    public $vencimientos = array(30,60,90,120,121);
    public $current_date;
    public $sql_aux;
    public $limit;
    public $sort;
    public $order;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Estado de Cuenta Clientes', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core()
    {
        parent::private_core();
        $this->share_extensions();
        $this->init_filters();
        $this->sql_aux();
        if (isset($_REQUEST['buscar_cliente'])) {
            $this->fbase_buscar_cliente($_REQUEST['buscar_cliente']);
        } elseif (\filter_input(INPUT_POST, 'listado_facturas')) {
            $this->tabla_de_datos();
        } else {
            $this->vencimiento_facturas();
        }
    }
    
    protected function init_filters()
    {
        $cli0 = new cliente();
        $this->hasta = (isset($_REQUEST['hasta']))?$_REQUEST['hasta']:Date('d-m-Y');
        $this->codserie = (isset($_REQUEST['codserie']))?$_REQUEST['codserie']:FALSE;
        $this->codpago = (isset($_REQUEST['codpago']))?$_REQUEST['codpago']:FALSE;
        $this->codagente = (isset($_REQUEST['codagente']))?$_REQUEST['codagente']:FALSE;
        $this->codalmacen = (isset($_REQUEST['codalmacen']))?$_REQUEST['codalmacen']:FALSE;
        $this->coddivisa = (isset($_REQUEST['coddivisa']))?$_REQUEST['coddivisa']:$this->empresa->coddivisa;
        $this->cliente = (isset($_REQUEST['codcliente']) && $_REQUEST['codcliente'] != '')?$cli0->get($_REQUEST['codcliente']):FALSE;
        $this->current_date = $this->empresa->var2str(\date('Y-m-d',strtotime($this->hasta)));
    }
    
    public function vencimiento_facturas()
    {
        $sql = "select codalmacen, ".
        " sum(case when (".$this->current_date." - vencimiento) < 30 then totaleuros else 0 end) as d30, ".
        " sum(case when (".$this->current_date." - vencimiento) > 30 and (".$this->current_date." - vencimiento) < 60 then totaleuros else 0 end) as d60, ".
        " sum(case when (".$this->current_date." - vencimiento) > 60 and (".$this->current_date." - vencimiento) < 90 then totaleuros else 0 end) as d90, ". 
        " sum(case when (".$this->current_date." - vencimiento) > 90 and (".$this->current_date." - vencimiento) < 120 then totaleuros else 0 end) as d120, ".
        " sum(case when (".$this->current_date." - vencimiento) > 120 then totaleuros else 0 end) as mas120 ".
        " from facturascli ".
        " where anulada = false and pagada = false and idfacturarect IS NULL ".$this->sql_aux.
        " group by codalmacen;";
        $data = $this->db->select($sql);
        $this->resultados = array();
        if(!empty($data)){
            $totalDeuda = 0;
            foreach($data as $d){
                $totalDeuda = $d['d30']+$d['d60']+$d['d90']+$d['d120']+$d['mas120'];
                $item = new stdClass();
                $item->codalmacen = $d['codalmacen'];
                $item->nombre_almacen = $this->almacen->get($d['codalmacen'])->nombre;
                $item->d30 = $d['d30'];
                $item->d30_pcj = round(($d['d30']/$totalDeuda)*100,0);
                $item->d60 = $d['d60'];
                $item->d60_pcj = round(($d['d60']/$totalDeuda)*100,0);                
                $item->d90 = $d['d90'];
                $item->d90_pcj = round(($d['d90']/$totalDeuda)*100,0);
                $item->d120 = $d['d120'];
                $item->d120_pcj = round(($d['d120']/$totalDeuda)*100,0);
                $item->mas120 = $d['mas120'];
                $item->mas120_pcj = round(($d['mas120']/$totalDeuda)*100,0);
                $item->totaldeuda = $totalDeuda;
                $this->resultados[] = $item;
            }
        }
    }
    
    public function tabla_de_datos(){
        $data = array();
        $this->template = false;
        $dias = \filter_input(INPUT_POST, 'dias');
        $offset = \filter_input(INPUT_POST, 'offset');
        $sort = \filter_input(INPUT_POST, 'sort');
        $order = \filter_input(INPUT_POST, 'order');
        $limit = \filter_input(INPUT_POST, 'limit');
        $this->limit = ($limit)?$limit:FS_ITEM_LIMIT;
        $this->sort = ($sort AND $sort!='undefined')?$sort:'codalmacen, fecha, idfactura ';
        $this->order = ($order AND $order!='undefined')?$order:'ASC';
        $datos = $this->listado_facturas($dias,$offset);
        $resultados = $datos['resultados'];
        $total_informacion = $datos['total'];
        header('Content-Type: application/json');
        $data['rows'] = $resultados;
        $data['total'] = $total_informacion;
        echo json_encode($data);
    }
    
    public function listado_facturas($dias,$offset)
    {
        $intervalo = $this->intervalo_tiempo($dias);
        $sql = "SELECT codalmacen, idfactura, codigo, numero2, nombrecliente, fecha, vencimiento, coddivisa, total, pagada, (".$this->current_date." - vencimiento) as atraso ".
            " FROM facturascli ".
            " WHERE anulada = false and pagada = false and idfacturarect IS NULL ".$intervalo.$this->sql_aux.
            " ORDER BY ".$this->sort.' '.$this->order;
        $data = $this->db->select_limit($sql, $this->limit, $offset);
        $sql_total = "SELECT count(*) as total".
            " FROM facturascli ".
            " WHERE anulada = false and pagada = false and idfacturarect IS NULL ".$intervalo.$this->sql_aux.";";
        $data_total = $this->db->select($sql_total);
        return array('resultados'=>$data,'total'=>$data_total[0]['total']);
    }
    
    public function intervalo_tiempo($dias)
    {
        $intervalo = '';        
        switch($dias){
            case 30:
                $intervalo = " AND (".$this->current_date." - vencimiento) <= 30 and (".$this->current_date." - vencimiento) > 0";
                break;
            case 60:
                $intervalo = " AND (".$this->current_date." - vencimiento) > 30 and (".$this->current_date." - vencimiento) <= 60";
                break;
            case 90:
                $intervalo = " AND (".$this->current_date." - vencimiento) > 60 and (".$this->current_date." - vencimiento) <= 90";
                break;
            case 120:
                $intervalo = " AND (".$this->current_date." - vencimiento) > 90 and (".$this->current_date." - vencimiento) <= 120";
                break;
            case 121:
                $intervalo = " AND (".$this->current_date." - vencimiento) > 120";
                break;
        }
        return $intervalo;
    }
    
    public function sql_aux()
    {
        $estado = ($this->estado == 'pagada')?TRUE:FALSE;
        $sql = '';
        $sql .= ($this->hasta)?' AND fecha <= '.$this->empresa->var2str(\date('Y-m-d',strtotime($this->hasta))):'';
        $sql .= ($this->codserie)?' AND codserie = '.$this->empresa->var2str($this->codserie):'';
        $sql .= ($this->codpago)?' AND codpago = '.$this->empresa->var2str($this->codpago):'';
        $sql .= ($this->codagente)?' AND codagente = '.$this->empresa->var2str($this->codagente):'';
        $sql .= ($this->codalmacen)?' AND codalmacen = '.$this->empresa->var2str($this->codalmacen):'';
        $sql .= ($this->coddivisa)?' AND coddivisa = '.$this->empresa->var2str($this->coddivisa):'';
        $sql .= ($this->cliente)?' AND codcliente = '.$this->empresa->var2str($this->cliente->codcliente):'';
        $sql .= ($this->estado)?' AND pagada = '.$this->empresa->var2str($estado):'';
        $this->sql_aux = $sql;
    }
    
    private function share_extensions() {
        $extensiones = array(
            array(
                'name' => '001_informe_estadocuenta_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_informe_estadocuenta_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/bootstrap-table-es-MX.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_informe_estadocuenta_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_informe_estadocuenta_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_informe_estadocuenta_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_informe_estadocuenta_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-table.min.css"/>',
                'params' => ''
            ),
        );

        foreach($extensiones as $ext){
            $fext = new fs_extension($ext);
            if(!$fext->save()){
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
