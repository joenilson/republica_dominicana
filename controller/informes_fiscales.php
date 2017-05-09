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
require_model('almacen.php');
require_model('proveedor.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('ncf_ventas.php');
require_model('ncf_tipo.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
/**
 * Description of reportes_fiscales
 *
 * @author darkniisan
 */
class informes_fiscales extends fs_controller {
    public $almacen;
    public $almacenes;
    public $almacenes_seleccionados;
    public $codalmacen;
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
    public $resultados_detalle_compras;
    public $resultados_resumen_ventas;
    public $total_resultados_consolidado;
    public $total_resultados_ventas;
    public $total_resultados_compras;
    public $total_resultados_606;
    public $total_resultados_607;
    public $total_resultados_608;
    public $total_resultados_detalle_compras;
    public $total_resultados_detalle_ventas;
    public $total_resultados_resumen_ventas;
    public $sumaVentas;
    public $sumaVentasPagadas;
    public $sumaCompras;
    public $sumaComprasPagadas;
    public $saldoConsolidado;
    public $saldoConsolidadoPagadas;
    
    public $archivoXLSX;
    public $archivoXLSXPath;
    public $documentosDir;
    public $exportDir;
    public $publicPath;    
    public $tItbis;
    public $tMonto;
    
    public function __construct() {
        parent::__construct(__CLASS__, 'Informes Fiscales', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->share_extensions();
        $this->almacenes = new almacen();
        $this->fecha_inicio = \date('01-m-Y');
        $this->fecha_fin = \date('d-m-Y');
        $this->reporte = '';
        $this->resultados_ventas = '';
        $this->resultados_compras = '';
        $this->resultados_606 = '';
        $this->resultados_607 = '';
        $this->resultados_608 = '';
        $this->resultados_detalle_compras = '';
        $this->resultados_detalle_ventas = '';
        $this->resultados_resumen_ventas = '';
        $this->total_resultados_consolidado = 0;
        $this->total_resultados_ventas = 0;
        $this->total_resultados_compras = 0;
        $this->total_resultados_606 = 0;
        $this->total_resultados_607 = 0;
        $this->total_resultados_608 = 0;
        $this->total_resultados_detalle_compras = 0;
        $this->total_resultados_detalle_ventas = 0;
        $this->total_resultados_resumen_ventas = 0;
        
        //Verificamos que exista la carpeta de documentos
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->exportDir = $this->documentosDir . DIRECTORY_SEPARATOR . "informes_rd";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'informes_rd';

        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir);
        }
        
        
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if(!$this->user->admin){
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
        }
        
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        $this->almacenes_seleccionados = (is_array($this->codalmacen))?$this->codalmacen:array($this->codalmacen);
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
                case 'detalle-compras':
                    $this->detalle_compras();
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
                case 'resumen-ventas':
                    $this->resumen_ventas();
                    break;
                default :
                    break;
            }
        }
    }

    /**
     * Reporte consolidado de Ventas y Compras
     */
    public function consolidado(){
        $this->sumaVentas = 0;
        $this->sumaCompras = 0;
        $this->saldoConsolidado = 0;
        $this->sumaVentasPagadas = 0;
        $this->sumaComprasPagadas = 0;
        $this->saldoConsolidadoPagadas = 0;
        $facturas_ventas = new ncf_ventas();
        $nueva_lista_ventas = array();
        foreach($this->almacenes_seleccionados as $cod)
        {
            $lista_facturas_ventas = $facturas_ventas->all_desde_hasta($this->empresa->id, \date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            foreach($lista_facturas_ventas as $linea){
                $nueva_linea = new StdClass();
                $nueva_linea->tipo = "Venta";
                $nueva_linea->codalmacen = $linea->codalmacen;
                $nueva_linea->fecha = $linea->fecha;
                $nueva_linea->nombre = $linea->nombrecliente;
                $nueva_linea->condicion = $linea->condicion;
                $nueva_linea->pagada = ($linea->pagada== 't')?"Si":"No";
                $nueva_linea->ncf = $linea->ncf;
                $nueva_linea->totaliva = $linea->totaliva;
                $nueva_linea->neto = $linea->neto;
                $nueva_linea->total = $linea->total;
                $this->sumaVentas += $linea->total;
                if($linea->pagada){
                    $this->sumaVentasPagadas += $linea->total;
                }
                array_push($nueva_lista_ventas, $nueva_linea);
            }
        }
        $this->total_resultados_ingresos = count($nueva_lista_ventas);
        
        $nueva_lista_compras = array();
        foreach($this->almacenes_seleccionados as $cod)
        {
            $lista_facturas_compras = $this->facturas_proveedor(\date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            foreach($lista_facturas_compras as $linea){
                $nueva_linea = new StdClass();
                $nueva_linea->tipo = "Compra";
                $nueva_linea->codalmacen = $linea->codalmacen;
                $nueva_linea->fecha = $linea->fecha;
                $nueva_linea->nombre = $linea->nombre;
                $nueva_linea->condicion = (!$linea->anulada)?"Activo":"Anulado";
                $nueva_linea->pagada = ($linea->pagada== 't')?"Si":"No";
                $nueva_linea->ncf = $linea->numproveedor;
                $nueva_linea->totaliva = $linea->totaliva;
                $nueva_linea->neto = $linea->neto;
                $nueva_linea->total = $linea->total;
                $this->sumaCompras += $linea->total;
                if($linea->pagada){
                    $this->sumaComprasPagadas += $linea->total;
                }
                array_push($nueva_lista_compras, $nueva_linea);
            }
        }
        $this->resultados_consolidado = array_merge($nueva_lista_ventas, $nueva_lista_compras);
        $this->total_resultados_egresos = count($lista_facturas_compras);
        $this->total_resultados_consolidado = $this->total_resultados_ingresos + $this->total_resultados_egresos;
        $this->saldoConsolidado = $this->sumaVentas - $this->sumaCompras;
        $this->saldoConsolidadoPagadas = $this->sumaVentasPagadas - $this->sumaComprasPagadas;
        
        $this->generar_excel(
            array('Tipo','Almacén','Fecha','Cliente/Proveedor','Condición','Pagada','NCF','ITBIS','Neto','Total'), 
            $this->resultados_consolidado, 
            array('','','','','','','','','',''), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'irght'),array('halign'=>'irght'),array('halign'=>'irght')), 
            FALSE
        );
    }

    public function detalle_compras(){
        $lista = array();
        $sql = "SELECT  codalmacen, fecha, numproveedor, f.idfactura, referencia, descripcion, cantidad, pvpunitario as precio, pvptotal as monto ".
        " FROM facturasprov as f".
        " JOIN lineasfacturasprov as fl on (f.idfactura = fl.idfactura)".
        " WHERE fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
        " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
        " AND anulada = FALSE".
        " AND codalmacen IN ('".implode("','",$this->almacenes_seleccionados)."') ".
        " ORDER BY codalmacen,fecha,numproveedor";
        $data = $this->db->select($sql);
        $totalCantidad = 0;
        $totalMonto = 0;
        if($data){
            foreach ($data as $d){
                $linea = new stdClass();
                $linea->codalmacen = $d['codalmacen'];
                $linea->fecha = $d['fecha'];
                $linea->ncf = $d['numproveedor'];
                $linea->documento = $d['idfactura'];
                $linea->referencia = $d['referencia'];
                $linea->descripcion = $d['descripcion'];
                $linea->cantidad = $d['cantidad'];
                $linea->precio = $d['precio'];
                $linea->monto = $d['monto'];
                $lista[] = $linea;
                $totalCantidad += $d['cantidad'];
                $totalMonto += $d['monto'];
            }
        }
        $this->resultados_detalle_compras = $lista;
        $this->total_resultados_detalle_compras = count($lista);
        $this->generar_excel(
            array('Almacén','Fecha','NCF','Documento','Referencia','Descripción','Cantidad','Precio','Monto'), 
            $this->resultados_detalle_compras, 
            array('Total','','','','','',$totalCantidad,'',$totalMonto), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')), 
            FALSE
        );
    }
    
    public function detalle_ventas(){
        $lista = array();
        $sql = "SELECT  codalmacen, fecha,ncf, documento, referencia, descripcion, cantidad, pvpunitario as precio, pvptotal as monto ".
        " FROM ncf_ventas".
        " JOIN lineasfacturascli on (documento = idfactura)".
        " WHERE idempresa = ".$this->empresa->id.
        " AND fecha between '".\date("Y-m-d", strtotime($this->fecha_inicio)).
        "' AND '".\date("Y-m-d", strtotime($this->fecha_fin)).
        "' AND estado = true".
        " AND codalmacen IN ('".implode("','",$this->almacenes_seleccionados)."') ".
        " ORDER BY codalmacen,fecha,ncf";
        $data = $this->db->select($sql);
        $totalCantidad = 0;
        $totalMonto = 0;
        if($data){
            foreach ($data as $d){
                $linea = new stdClass();
                $linea->codalmacen = $d['codalmacen'];
                $linea->fecha = $d['fecha'];
                $linea->ncf = $d['ncf'];
                $linea->documento = $d['documento'];
                $linea->referencia = $d['referencia'];
                $linea->descripcion = $d['descripcion'];
                $linea->cantidad = $d['cantidad'];
                $linea->precio = $d['precio'];
                $linea->monto = $d['monto'];
                $lista[] = $linea;
                $totalCantidad += $d['cantidad'];
                $totalMonto += $d['monto'];
            }
        }
        $this->resultados_detalle_ventas = $lista;
        $this->total_resultados_detalle_ventas = count($lista);
        $this->generar_excel(
            array('Almacén','Fecha','NCF','Documento','Referencia','Descripción','Cantidad','Precio','Monto'), 
            $this->resultados_detalle_ventas, 
            array('Total','','','','','',$totalCantidad,'',$totalMonto), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')), 
            FALSE
        );
    }

    public function resumen_ventas(){
        $lista = array();
        $sql = "SELECT codalmacen, ncf_tipo.tipo_comprobante as tipo_comprobante, ncf_tipo.descripcion as tc_descripcion, ".
        "referencia, lineasfacturascli.descripcion as descripcion, sum(cantidad) as cantidad, sum(pvptotal) as monto ".
        " from ncf_ventas ".
        " join lineasfacturascli on (documento = idfactura) ".
        " join ncf_tipo on (ncf_ventas.tipo_comprobante = ncf_tipo.tipo_comprobante) ".
        " where idempresa = ".$this->empresa->id." AND fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
        " and ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin)))." and ncf_ventas.estado = TRUE ".
        " AND codalmacen IN ('".implode("','",$this->almacenes_seleccionados)."') ".
        " group by codalmacen,ncf_tipo.tipo_comprobante,ncf_tipo.descripcion, referencia, lineasfacturascli.descripcion ".
        " order by codalmacen,ncf_tipo.tipo_comprobante";
        $data = $this->db->select($sql);
        $totalCantidad = 0;
        $totalMonto = 0;
        if($data){
            foreach ($data as $d){
                $linea = new stdClass();
                $linea->codalmacen = $d['codalmacen'];
                $linea->tipo_comprobante = $d['tipo_comprobante'];
                $linea->tc_descripcion = $d['tc_descripcion'];
                $linea->referencia = $d['referencia'];
                $linea->descripcion = $d['descripcion'];
                $linea->cantidad = $d['cantidad'];
                $linea->monto = $d['monto'];
                $totalCantidad += $d['cantidad'];
                $totalMonto += $d['monto'];
                $lista[] = $linea;
            }
        }
        $this->resultados_resumen_ventas = $lista;
        $this->total_resultados_resumen_ventas = count($lista);
        $this->generar_excel(
            array('Almacén','Tipo NCF','Descripción NCF','Referencia','Descripción Artículo','Cantidad','monto'), 
            $lista, 
            array('Total','','','','',$totalCantidad,$totalMonto), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right')), 
            FALSE
        );
    }

    public function ventas(){
        $this->resultados_ventas = array();
        $facturas = new ncf_ventas();
        $totalNeto = 0;
        $totalItbis =0;
        $totalMonto =0;
        foreach($this->almacenes_seleccionados as $cod)
        {
            $lista = array();
            $datos = $facturas->all_desde_hasta($this->empresa->id, \date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            if($datos)
            {
                foreach($datos as $linea)
                {
                    $item = new stdClass();
                    $item->fecha = $linea->fecha;
                    $item->codalmacen = $linea->codalmacen;
                    $item->nombrecliente = $linea->nombrecliente;
                    $item->cifnif = $linea->cifnif;
                    $item->ncf = $linea->ncf;
                    $item->tipo_comprobante = $linea->tipo_comprobante;
                    $item->neto = $linea->neto;
                    $item->totaliva = $linea->totaliva;
                    $item->total = $linea->total;
                    $item->condicion = $linea->condicion;
                    $item->estado = $linea->estado;
                    $totalNeto += $linea->neto;
                    $totalItbis += $linea->totaliva;
                    $totalMonto += $linea->total;
                    $lista[] = $item;
                }
            }
            $this->resultados_ventas = array_merge($this->resultados_ventas, $lista);
        }
        $this->total_resultados_ventas = count($this->resultados_ventas);
        $this->generar_excel(
            array('Fecha','Almacén','Cliente','RNC','NCF','Tipo','Base Imp.','Itbis','Total','Condicion','Estado'), 
            $this->resultados_ventas, 
            array('Total','','','','','',$totalNeto,$totalItbis,$totalMonto,'',''), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')), 
            FALSE
        );
    }
    
    public function compras(){
        $this->resultados_compras = array();
        $lista = array();
        $totalNeto = 0;
        $totalItbis =0;
        $totalMonto =0;
        foreach($this->almacenes_seleccionados as $cod)
        {
            $datos = $this->facturas_proveedor(\date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            if($datos)
            {
                foreach($datos as $linea)
                {
                    $item = new stdClass();
                    $item->fecha = $linea->fecha;
                    $item->codalmacen = $linea->codalmacen;
                    $item->nombre = $linea->nombre;
                    $item->cifnif = $linea->cifnif;
                    $item->idfactura = $linea->idfactura;
                    $item->numproveedor = $linea->numproveedor;
                    $item->neto = $linea->neto;
                    $item->totaliva = $linea->totaliva;
                    $item->total = $linea->total;
                    $item->condicion = (!$linea->anulada)?"Activo":"Anulado";
                    $item->estado = $linea->anulada;
                    $totalNeto += $linea->neto;
                    $totalItbis += $linea->totaliva;
                    $totalMonto += $linea->total;
                    $lista[] = $item;
                }
            }
            $this->resultados_compras = array_merge($this->resultados_compras, $lista);
        }
        $this->total_resultados_compras = count($this->resultados_compras);
        $this->generar_excel(
            array('Fecha','Almacén','Proveedor','RNC','Factura','NCF','Base Imp.','Itbis','Total','Condicion','Anulada'), 
            $this->resultados_compras, 
            array('Total','','','','','',$totalNeto,$totalItbis,$totalMonto,'',''), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')), 
            FALSE
        );
    }
    
    public function dgii606(){
        $this->resultados_606 = array();
        $lista = array();
        foreach($this->almacenes_seleccionados as $cod)
        {
            $datos_reporte = $this->facturas_proveedor(\date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            if($datos_reporte)
            {
                foreach($datos_reporte as $d)
                {
                    $prov = new proveedor();
                    $proveedor = $prov->get($d->codproveedor);
                    $factura_modifica = ($d->idfacturarect)?$this->factura_modifica_proveedor($d->idfacturarect):false;
                    $item = new stdClass();
                    $item->cifnif = $d->cifnif;
                    $item->tipo_id = ((strlen($d->cifnif)==9) OR (strlen($d->cifnif)==11))?1:3;
                    $item->descripcion = ($proveedor->acreedor)?"Servicios":"Bienes y Servicios";
                    $item->numproveedor = $d->numproveedor;
                    $item->numproveedor_rect = ($factura_modifica)?$factura_modifica[0]['numproveedor']:'';
                    $item->periodo = substr($d->fecha,6,4).substr($d->fecha,3,2);
                    $item->dia = substr($d->fecha,0,2);
                    $item->periodo_pago = '';
                    $item->dia_pago = '';
                    if($d->get_asiento_pago()){
                        $asiento_pago = $d->get_asiento_pago();
                        $item->periodo_pago = substr($asiento_pago->fecha,6,4).substr($asiento_pago->fecha,3,2);
                        $item->dia_pago = substr($asiento_pago->fecha,0,2);
                    }
                    $item->totaliva = $d->totaliva;
                    $item->totalirpf = $d->totalirpf;
                    $item->neto = $d->neto;
                    $item->retencionneto = '';
                    $lista[] = $item;
                }
            }
            $this->resultados_606 = array_merge($this->resultados_606, $lista);
        }
        $this->total_resultados_606 = count($this->resultados_606);
        $this->generar_excel(
            array('RNC/Cédula','Tipo Id','Tipo Bienes o Servicios Comprados','NCF','NCF Modifica','Fecha AAAAMM','Fecha DD','Fecha Pago AAAAMM','Fecha Pago DD','ITBIS Facturado','ITBIS Retenido','Monto Facturado','Retencion Renta'), 
            $this->resultados_606, 
            FALSE, 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')),
            FALSE
        );
    }
    
    public function dgii607()
    {
        $this->resultados_607 = array();
        $listado = array();
        $facturas = new ncf_ventas();
        $this->tItbis=0;
        $this->tMonto=0;
        foreach($this->almacenes_seleccionados as $cod)
        {
            
            $datos_reporte = $facturas->all_activo_desde_hasta($this->empresa->id, \date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            if($datos_reporte)
            {
                foreach($datos_reporte as $data)
                {
                    $item = new stdClass();
                    $item->cifnif = ((strlen($data->cifnif)==9) OR (strlen($data->cifnif)==11))?$data->cifnif:'';
                    $item->tipo_id = ((strlen($data->cifnif)==9) OR (strlen($data->cifnif)==11))?1:3;
                    $item->ncf = $data->ncf;
                    $item->ncf_modifica = $data->ncf_modifica;
                    $item->fecha = $data->fecha;
                    $item->totaliva = $data->totaliva;
                    $item->neto = $data->neto;
                    $item->condicion = ($data->estado)?"Activo":"Anulado";
                    $this->tItbis+=$item->totaliva;
                    $this->tMonto+=$item->neto;
                    $listado[] = $item;
                }
            }
            $this->resultados_607 = array_merge($this->resultados_607, $listado);
        }
        
        $this->total_resultados_607 = count($this->resultados_607);
        
        $this->generar_excel(
            array('RNC/Cédula','Tipo Id','NCF','NCF Modifica','Fecha','ITBIS Facturado','Monto Facturado','Estado'), 
            $this->resultados_607, 
            array('Total',$this->total_resultados_607.' Documentos','','','','','',''), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left')),
            FALSE
        );
    }
    
    public function dgii608(){
        $this->resultados_608 = array();
        $facturas = new ncf_ventas();
        $lista = array();
        foreach($this->almacenes_seleccionados as $cod)
        {
            $datos_reporte = $facturas->all_anulado_desde_hasta($this->empresa->id, \date("Y-m-d", strtotime($this->fecha_inicio)), \date("Y-m-d", strtotime($this->fecha_fin)), $cod);
            if($datos_reporte)
            {
                foreach($datos_reporte as $d)
                {
                    $item = new stdClass();
                    $item->ncf = $d->ncf;
                    $item->fecha = $d->fecha;
                    $item->motivo = $d->motivo;
                    $item->condicion = $d->condicion;
                    $lista[] = $item;
                }
            }
            $this->resultados_608 = array_merge($this->resultados_608, $lista);
        }
        $this->total_resultados_608 = count($this->resultados_608);
        $this->generar_excel(
            array('NCF','Fecha','Motivo','Estado'), 
            $this->resultados_608, 
            array('Total',$this->total_resultados_608.' Documentos','',''), 
            FALSE, 
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left')),
            FALSE
        );
    }
    
    public function facturas_proveedor($desde,$hasta,$codalmacen)
    {
        $lista = array();
        $sql = "SELECT * FROM facturasprov ".
            " WHERE codalmacen = ".$this->empresa->var2str($codalmacen).
            " AND fecha between ".$this->empresa->var2str($desde)." AND ".$this->empresa->var2str($hasta).
            " ORDER BY fecha, idfactura;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new factura_proveedor($d);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    public function factura_modifica_proveedor($idfactura)
    {
        $sql = "SELECT idfactura,numproveedor from facturasprov WHERE idfactura = ".$this->empresa->intval($idfactura);
        return $this->db->select($sql);
    }
    
    /**
     * 
     * @param type $cabecera
     * @param type $datos
     * @param type $pie
     * @param type $estilo_cab
     * @param type $estilo_datos
     * @param type $estilo_pie
     */
    public function generar_excel($cabecera,$datos,$pie,$estilo_cab,$estilo_datos,$estilo_pie)
    {
        //Revisamos que no haya un archivo ya cargado
        $archivo = str_replace("-","_",$this->reporte);
        $this->archivoXLSX = $this->exportDir . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        $this->archivoXLSXPath = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->archivoXLSX)) {
            unlink($this->archivoXLSX);
        }
        //Variables para cada parte del excel
        $estilo_cabecera = ($estilo_cab)?$estilo_cab:array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $estilo_cuerpo = ($estilo_datos)?$estilo_datos:array( array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'));
        $estilo_footer = ($estilo_pie)?$estilo_pie:array('border'=>'left,right,top,bottom','font-style'=>'bold','color'=>'#FFFFFF','fill'=>'#000000');
        //Inicializamos la clase
        $this->writer = new XLSXWriter();
        //Creamos la hoja con todos los clientes organizados por ruta
        $nombre_hoja = ucfirst(str_replace('-',' ',$this->reporte));
        $this->writer->writeSheetHeader($nombre_hoja, array(), true);
        //Agregamos la linea de Título
        $this->writer->writeSheetRow($nombre_hoja,$cabecera,$estilo_cabecera);
        //Agregamos cada linea en forma de Array
        foreach($datos as $linea){
            $this->writer->writeSheetRow($nombre_hoja, (array) $linea, $estilo_cuerpo);
        }
        if($pie)
        {
            $this->writer->writeSheetRow($nombre_hoja, (array) $pie, $estilo_footer);
        }
        //Escribimos
        $this->writer->writeToFile($this->archivoXLSXPath);
    }

    private function share_extensions() {
        $extensiones1 = array(
            array(
                'name' => 'informes_fiscales_js9',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js10',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_css11',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js13',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/validator.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_css12',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-table.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js14',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js15',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/bootstrap-table-es-MX.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js16',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-export.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js17',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js18',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js_btm',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js19',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js181',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.xtras/fileSaver/FileSaver.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js182',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.xtras/jsPDF/jspdf.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js183',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.xtras/jsPDF/zAutoTable/jspdf.plugin.autotable.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js184',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.xtras/js-xlsx/xlsx.core.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js185',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/tableExport.xtras/html2canvas/html2canvas.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'informes_fiscales_js12',
                'page_from' => __CLASS__,
                'page_to' => 'informes_fiscales',
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootbox.min.js" type="text/javascript"></script>',
                'params' => ''
            ),

        );

        foreach($extensiones1 as $ext){
            $fext = new fs_extension($ext);
            if(!$fext->delete()){
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
        
        $extensiones = array(
            array(
                'name' => '001_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/validator.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/locale/bootstrap-table-es-MX.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '006_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '007_informes_fiscales_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_informes_fiscales_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),
            
            array(
                'name' => '002_informes_fiscales_css',
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
    
    /**
     * @url http://snippets.khromov.se/convert-comma-separated-values-to-array-in-php/
     * @param $string - Input string to convert to array
     * @param string $separator - Separator to separate by (default: ,)
     *
     * @return array
     */
    private function comma_separated_to_array($string, $separator = ',') {
        //Explode on comma
        $vals = explode($separator, $string);

        //Trim whitespace
        foreach ($vals as $key => $val) {
            $vals[$key] = trim($val);
        }
        //Return empty array if no items found
        //http://php.net/manual/en/function.explode.php#114273
        return array_diff($vals, array(""));
    }    
    
}
