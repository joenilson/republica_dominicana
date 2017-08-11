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
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Estado de Cuenta Clientes', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core()
    {
        parent::private_core();
        $this->init_filters();
        if (isset($_REQUEST['buscar_cliente'])) {
            $this->fbase_buscar_cliente($_REQUEST['buscar_cliente']);
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
        $this->estado = (isset($_REQUEST['estado']))?$_REQUEST['estado']:FALSE;
        $this->cliente = (isset($_REQUEST['codcliente']) && $_REQUEST['codcliente'] != '')?$cli0->get($_REQUEST['codcliente']):FALSE;
    }
    
    public function vencimiento_facturas()
    {
        $sql_aux = $this->sql_aux();
        $current_date = $this->empresa->var2str(\date('Y-m-d',strtotime($this->hasta)));
        $sql = "select codalmacen, ".
        " sum(case when (".$current_date." - vencimiento) < 30 then totaleuros else 0 end) as d30, ".
        " sum(case when (".$current_date." - vencimiento) > 30 and (".$current_date." - vencimiento) < 60 then totaleuros else 0 end) as d60, ".
        " sum(case when (".$current_date." - vencimiento) > 60 and (".$current_date." - vencimiento) < 90 then totaleuros else 0 end) as d90, ". 
        " sum(case when (".$current_date." - vencimiento) > 90 and (".$current_date." - vencimiento) < 120 then totaleuros else 0 end) as d120, ".
        " sum(case when (".$current_date." - vencimiento) > 120 then totaleuros else 0 end) as mas120 ".
        " from facturascli".
        " where anulada = false and idfacturarect IS NULL ".$sql_aux.
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
        return $sql;
    }
}
