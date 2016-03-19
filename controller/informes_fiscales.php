<?php

/*
 * Copyright (C) 2015 darkniisan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
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
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('ncf_ventas.php');
require_model('ncf_tipo.php');
/**
 * Description of reportes_fiscales
 *
 * @author darkniisan
 */
class informes_fiscales extends fs_controller {
    public $fecha_inicio;
    public $fecha_fin;
    public $reporte;
    public $resultados_consolidado;
    public $resultados_ventas;
    public $resultados_compras;
    public $resultados_606;
    public $resultados_607;
    public $resultados_608;
    public $resultados_detalle_ventas;
    public $total_resultados_consolidado;
    public $total_resultados_ventas;
    public $total_resultados_compras;
    public $total_resultados_606;
    public $total_resultados_607;
    public $total_resultados_608;
    public $total_resultados_detalle_ventas;
    public $sumaVentas;
    public $sumaVentasPagadas;
    public $sumaCompras;
    public $sumaComprasPagadas;
    public $saldoConsolidado;
    public $saldoConsolidadoPagadas;
    public function __construct() {
        parent::__construct(__CLASS__, 'Informes Fiscales', 'informes', FALSE, TRUE, FALSE);
    }
    protected function private_core() {
        $this->share_extensions();
        $this->fecha_inicio = \date('01-m-Y');
        $this->fecha_fin = \date('t-m-Y');
        $this->reporte = '';
        $this->resultados_ventas = '';
        $this->resultados_compras = '';
        $this->resultados_606 = '';
        $this->resultados_607 = '';
        $this->resultados_608 = '';
        $this->resultados_detalle_ventas = '';
        $this->total_resultados_consolidado = 0;
        $this->total_resultados_ventas = 0;
        $this->total_resultados_compras = 0;
        $this->total_resultados_606 = 0;
        $this->total_resultados_607 = 0;
        $this->total_resultados_608 = 0;
        $this->total_resultados_detalle_ventas = 0;
        $tiporeporte = \filter_input(INPUT_POST, 'tipo-reporte');
        if(!empty($tiporeporte)){
            $inicio = \filter_input(INPUT_POST, 'inicio');
            $fin = \filter_input(INPUT_POST, 'fin');
            $this->fecha_inicio = $inicio;
            $this->fecha_fin = $fin;
            $this->reporte = $tiporeporte;
            switch ($tiporeporte){
                case 'reporte-consolidado':
                    $this->consolidado();
                    break;
                case 'reporte-ventas':
                    $this->ventas();
                    break;
                case 'reporte-compras':
                    $this->compras();
                    break;
                case 'reporte-606':
                    $this->dgii606();
                    break;
                case 'reporte-607':
                    $this->dgii607();
                    break;
                case 'reporte-608':
                    $this->dgii608();
                    break;
                case 'detalle-ventas':
                    $this->detalle_ventas();
                    break;
                default :
                    break;
            }
        }
    }
    
    public function consolidado(){
        $this->sumaVentas = 0;
        $this->sumaCompras = 0;
        $this->saldoConsolidado = 0;
        $this->sumaVentasPagadas = 0;
        $this->sumaComprasPagadas = 0;
        $this->saldoConsolidadoPagadas = 0;
        $facturas_ventas = new ncf_ventas();
        $lista_facturas_ventas = $facturas_ventas->all_desde_hasta($this->empresa->id, $this->fecha_inicio, $this->fecha_fin);
        $nueva_lista_ventas = array();
        foreach($lista_facturas_ventas as $linea){
            $nueva_linea = clone $linea;
            $nueva_linea->tipo = "Venta";
            $nueva_linea->nombre = $linea->nombrecliente;
            $nueva_linea->condicion = $linea->condicion;
            $nueva_linea->pagada = ($linea->pagada== 't')?"Si":"No";
            $this->sumaVentas += $linea->total;
            if($linea->pagada){
                $this->sumaVentasPagadas += $linea->total;
            }
            $nueva_lista_ventas[] = $nueva_linea;
        }
        $this->total_resultados_ingresos = count($nueva_lista_ventas);
        $facturas_compras = new factura_proveedor();
        $lista_facturas_compras = $facturas_compras->all_desde($this->fecha_inicio, $this->fecha_fin);
        $nueva_lista_compras = array();
        foreach($lista_facturas_compras as $linea){
            $nueva_linea->tipo = "Compra";
            $nueva_linea->nombre = $linea->nombre;
            $nueva_linea->condicion = (!$linea->anulada)?"Activo":"Anulado";
            $nueva_linea->pagada = ($linea->pagada== 't')?"Si":"No";
            $nueva_linea->ncf = $linea->numproveedor;
            $nueva_linea->tipo_comprobante = substr($linea->numproveedor,9,2);
            $this->sumaCompras += $linea->total;
            if($linea->pagada){
                $this->sumaComprasPagadas += $linea->total;
            }
            $nueva_lista_compras[] = $nueva_linea;
        }
        $this->resultados_consolidado = array_merge($nueva_lista_ventas, $nueva_lista_compras);
        $this->total_resultados_egresos = count($lista_facturas_compras);
        $this->total_resultados_consolidado = $this->total_resultados_ingresos + $this->total_resultados_egresos;
        $this->saldoConsolidado = $this->sumaVentas - $this->sumaCompras;
        $this->saldoConsolidadoPagadas = $this->sumaVentasPagadas - $this->sumaComprasPagadas;
    }
    
    public function detalle_ventas(){
        $lista = array();
        $sql = "
        select fecha,ncf, documento, referencia, descripcion, cantidad, pvpunitario as precio, pvptotal as monto
        from ncf_ventas 
        join lineasfacturascli on (documento = idfactura)
        where idempresa = ".$this->empresa->id." AND fecha between '".$this->fecha_inicio."' and '".$this->fecha_fin."' and estado = true order by fecha,ncf";
        $data = $this->db->select($sql);
        if($data){
            foreach ($data as $d){
                $linea = new stdClass();
                $linea->fecha = $d['fecha'];
                $linea->ncf = $d['ncf'];
                $linea->documento = $d['documento'];
                $linea->referencia = $d['referencia'];
                $linea->descripcion = $d['descripcion'];
                $linea->cantidad = $d['cantidad'];
                $linea->precio = $d['precio'];
                $linea->monto = $d['monto'];
                $lista[] = $linea;
            }
        }
        $this->resultados_detalle_ventas = $lista;
        $this->total_resultados_detalle_ventas = count($lista);
    }
    
    public function ventas(){
        $facturas = new ncf_ventas();
        $this->resultados_ventas = $facturas->all_desde_hasta($this->empresa->id, $this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_ventas = count($this->resultados_ventas);
    }
    public function compras(){
        $facturas = new factura_proveedor();
        $this->resultados_compras = $facturas->all_desde($this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_compras = count($this->resultados_compras);
    }
    public function dgii606(){
        $facturas = new factura_proveedor();
        $this->resultados_606 = $facturas->all_desde($this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_606 = count($this->resultados_606);
    }
    public function dgii607(){
        $facturas = new ncf_ventas();
        $this->resultados_607 = $facturas->all_activo_desde_hasta($this->empresa->id, $this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_607 = count($this->resultados_607);
    }
    public function dgii608(){
        $facturas = new ncf_ventas();
        $this->resultados_608 = $facturas->all_anulado_desde_hasta($this->empresa->id, $this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_608 = count($this->resultados_608);
    }
    
    private function share_extensions() {
       
        $fsext1 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css5',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            )
        );
        $fsext1->delete();
        
        $fsext2 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css6',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext2->delete();
        
        $fsext3 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css7',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext3->delete();
        
        $fsext4 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js9',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext4->save();
        
        $fsext5 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js10',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext5->delete();
        
        $fsext6 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css11',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            )
        );
        $fsext6->save();
        
        $fsext7 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js12',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootbox.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext7->save();
        
        $fsext8 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js13',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/validator.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext8->save();
        
        $fsext9 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css12',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-table.min.css"/>',
                'params' => ''
            )
        );
        $fsext9->save();
        
        $fsext10 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js14',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext10->save();
        
        $fsext11 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js15',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/bootstrap-table-es-MX.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext11->save();
        
        $fsext12 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js16',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-export.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext12->save();
        
        $fsext13 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js17',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext13->save();
        
        $fsext14 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js18',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext14->save();
        
        $fsext15 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js19',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.jquery.plugin.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext15->save();
        
        $fsext16 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js181',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/jspdf.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext16->save();
        
        $fsext17 = new fs_extension(
            array(
                'name' => 'informes_fiscales_js182',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/jspdf.plugin.autotable.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext17->save();
        
        $fsext18 = new fs_extension(
            array(
                'name' => 'informes_fiscales_css20',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/font-awesome/css/font-awesome.min.css"/>',
                'params' => ''
            )
        );
      $fsext18->delete();
    }
}
