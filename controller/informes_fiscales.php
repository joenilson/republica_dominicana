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
    public $resultados_ventas;
    public $resultados_compras;
    public $resultados_606;
    public $resultados_607;
    public $resultados_608;
    public $total_resultados_ventas;
    public $total_resultados_compras;
    public $total_resultados_606;
    public $total_resultados_607;
    public $total_resultados_608;
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
        $this->total_resultados_ventas = 0;
        $this->total_resultados_compras = 0;
        $this->total_resultados_606 = 0;
        $this->total_resultados_607 = 0;
        $this->total_resultados_608 = 0;
        $tiporeporte = \filter_input(INPUT_POST, 'tipo-reporte');
        if(!empty($tiporeporte)){
            $inicio = \filter_input(INPUT_POST, 'inicio');
            $fin = \filter_input(INPUT_POST, 'fin');
            $this->fecha_inicio = $inicio;
            $this->fecha_fin = $fin;
            $this->reporte = $tiporeporte;
            switch ($tiporeporte){
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
                default :
                    break;
            }
        }
    }
    
    public function ventas(){
        $facturas = new ncf_ventas();
        $this->resultados_ventas = $facturas->all_desde_hasta($this->empresa->id, $this->fecha_inicio, $this->fecha_fin);
        $this->total_resultados_ventas = count($this->resultados_ventas);
    }
    public function compras(){
        
    }
    public function dgii606(){
        
    }
    public function dgii607(){
        
    }
    public function dgii608(){
        
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
    }
}
