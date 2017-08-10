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
    public $proveedor;
    public $desde;
    public $hasta;
    public $estado;
    public $resultados;
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
        } else if (isset($_REQUEST['buscar_proveedor'])) {
            $this->fbase_buscar_proveedor($_REQUEST['buscar_proveedor']);
        } else {
            $this->vencimiento_facturas();
        }
    }
    
    protected function init_filters()
    {
        $this->hasta = Date('d-m-Y');
        if (isset($_REQUEST['hasta'])) {
            $this->hasta = $_REQUEST['hasta'];
        }

        $this->codserie = FALSE;
        if (isset($_REQUEST['codserie'])) {
            $this->codserie = $_REQUEST['codserie'];
        }

        $this->codpago = FALSE;
        if (isset($_REQUEST['codpago'])) {
            $this->codpago = $_REQUEST['codpago'];
        }

        $this->codagente = FALSE;
        if (isset($_REQUEST['codagente'])) {
            $this->codagente = $_REQUEST['codagente'];
        }

        $this->codalmacen = FALSE;
        if (isset($_REQUEST['codalmacen'])) {
            $this->codalmacen = $_REQUEST['codalmacen'];
        }

        $this->coddivisa = $this->empresa->coddivisa;
        if (isset($_REQUEST['coddivisa'])) {
            $this->coddivisa = $_REQUEST['coddivisa'];
        }

        $this->cliente = FALSE;
        if (isset($_REQUEST['codcliente'])) {
            if ($_REQUEST['codcliente'] != '') {
                $cli0 = new cliente();
                $this->cliente = $cli0->get($_REQUEST['codcliente']);
            }
        }

        $this->proveedor = FALSE;
        if (isset($_REQUEST['codproveedor'])) {
            if ($_REQUEST['codproveedor'] != '') {
                $prov0 = new proveedor();
                $this->proveedor = $prov0->get($_REQUEST['codproveedor']);
            }
        }
        
        $this->estado = FALSE;
        if (isset($_REQUEST['estado'])) {
            $this->estado = $_REQUEST['estado'];
        }
    }
    
    public function vencimiento_facturas()
    {
        $sql_aux = $this->sql_aux();
        $current_date = $this->empresa->var2str(\date('Y-m-d',strtotime($this->hasta)));
        $tabla = ($this->proveedor)?"facturasprov":"facturascli";
        $sql = "select codalmacen, ".
        " sum(case when (".$current_date." - vencimiento) < 30 then totaleuros else 0 end) as d30, ".
        " sum(case when (".$current_date." - vencimiento) > 30 and (".$current_date." - vencimiento) < 60 then totaleuros else 0 end) as d60, ".
        " sum(case when (".$current_date." - vencimiento) > 60 and (".$current_date." - vencimiento) < 90 then totaleuros else 0 end) as d90, ". 
        " sum(case when (".$current_date." - vencimiento) > 90 and (".$current_date." - vencimiento) < 120 then totaleuros else 0 end) as d120, ".
        " sum(case when (".$current_date." - vencimiento) > 120 then totaleuros else 0 end) as mas120 ".
        " from ".$tabla.
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
        $sql .= ($this->proveedor)?' AND codproveedor = '.$this->empresa->var2str($this->proveedor->codproveedor):'';
        $sql .= ($this->estado)?' AND pagada = '.$this->empresa->var2str($estado):'';
        return $sql;
    }
}
