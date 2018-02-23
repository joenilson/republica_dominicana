<?php

/*
 * Copyright (C) 2017 joenilson at gmail dot com
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
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/republica_dominicana/extras/rd_controller.php';
/**
 * Description of reportes_fiscales
 *
 * @author joenilson
 */
class informes_fiscales extends rd_controller
{
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
    public $resultados_ventas_agente;
    public $total_resultados_consolidado;
    public $total_resultados_ventas;
    public $total_resultados_compras;
    public $total_resultados_606;
    public $total_resultados_607;
    public $total_resultados_608;
    public $total_resultados_detalle_compras;
    public $total_resultados_detalle_ventas;
    public $total_resultados_resumen_ventas;
    public $total_resultados_ventas_agente;
    public $total_forma_pago;
    public $total_forma_pago_dgii;
    public $sumaVentas;
    public $sumaVentasPagadas;
    public $sumaCompras;
    public $sumaComprasPagadas;
    public $saldoConsolidado;
    public $saldoConsolidadoPagadas;
    public $totalDescuento;
    public $totalDocumentos;
    public $totalCantidad;
    public $totalNeto;
    public $totalItbis;
    public $totalMonto;
    public $archivoXLSX;
    public $writer;
    public $archivoXLSXPath;
    public $documentosDir;
    public $exportDir;
    public $publicPath;

    //Variables de Busqueda
    public $limit;
    public $offset;
    public $sort;
    public $order;
    public $search;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Informes Fiscales', 'informes', false, true, false);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->share_extensions();
        $this->almacenes = new almacen();
        $this->init_variables();
        $this->crear_carpetas_reportes();
        $codalmacen = $this->filter_request_array('codalmacen');
        $almacen_defecto = false;
        if (count($this->almacenes->all())===1) {
            $almacen_defecto = $this->empresa->codalmacen;
        }

        $this->codalmacen = ($codalmacen)?$codalmacen:$almacen_defecto;
        $this->almacenes_seleccionados = (is_array($this->codalmacen))?$this->codalmacen:array($this->codalmacen);

        $this->verificar_tipo_reporte();
    }

    public function verificar_tipo_reporte()
    {
        $tiporeporte = \filter_input(INPUT_POST, 'tipo-reporte');
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
        if (!empty($tiporeporte)) {
            $this->reporte = $tiporeporte;
            $this->mostrar_reporte();
        }
        if (\filter_input(INPUT_GET, 'tabla_reporte')) {
            $this->datos_reporte(\filter_input(INPUT_GET, 'tabla_reporte'), true);
        }
    }

    private function mostrar_reporte()
    {
        switch ($this->reporte) {
            case 'reporte-consolidado':
                $this->consolidado();
                break;
            case 'reporte-ventas':
                $this->ventas();
                break;
            case 'detalle-ventas-agente':
                $this->ventas_agente();
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
            default:
                break;
        }
    }

    private function init_variables()
    {
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
        $this->resultados_ventas_agente = '';
        $this->total_resultados_consolidado = 0;
        $this->total_resultados_ventas = 0;
        $this->total_resultados_compras = 0;
        $this->total_resultados_606 = 0;
        $this->total_resultados_607 = 0;
        $this->total_resultados_608 = 0;
        $this->total_resultados_detalle_compras = 0;
        $this->total_resultados_detalle_ventas = 0;
        $this->total_resultados_resumen_ventas = 0;
        $this->total_resultados_ventas_agente = 0;
    }

    private function crear_carpetas_reportes()
    {
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
    }

    public function datos_reporte($reporte, $json = false)
    {
        $resultados = array();
        $total_informacion = 0;
        $almacenes = implode("','", $this->almacenes_seleccionados);
        switch ($reporte) {
            case "reporte-consolidado":
                list($resultados, $total_informacion) = $this->reporteConsolidado($almacenes, $json);
                break;
            case "reporte-ventas":
                list($resultados, $total_informacion) = $this->reporteVentas($almacenes, $json);
                break;
            case "detalle-ventas-agente":
                list($resultados, $total_informacion) = $this->reporteVentasAgente($almacenes, $json);
                break;
            case "detalle-ventas":
                list($resultados, $total_informacion) = $this->reporteDetalleVentas($almacenes, $json);
                break;
            case "resumen-ventas":
                list($resultados, $total_informacion) = $this->reporteResumenVentas($almacenes, $json);
                break;
            case "reporte-compras":
                list($resultados, $total_informacion) = $this->reporteCompras($almacenes, $json);
                break;
            case "detalle-compras":
                list($resultados, $total_informacion) = $this->reporteDetalleCompras($almacenes, $json);
                break;
            case "reporte-606":
                list($resultados, $total_informacion) = $this->reporte606($almacenes, $json);
                break;
            case "reporte-607":
                list($resultados, $total_informacion) = $this->reporte607($almacenes, $json);
                break;
            case "reporte-608":
                list($resultados, $total_informacion) = $this->reporte608($almacenes, $json);
                break;
            default:
                break;
        }
        if ($json) {
            $data = array();
            $this->template = false;
            header('Content-Type: application/json');
            $data['rows'] = $resultados;
            $data['total'] = $total_informacion;
            echo json_encode($data);
        } else {
            return $resultados;
        }
    }

    public function reporteConsolidado($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_consolidado = "( ".
            " SELECT 'Venta' as tipo, nv.codalmacen,nv.fecha,f.nombrecliente as nombre, ".
            " CASE WHEN f.anulada THEN 'Anulado' ELSE 'Activo' END as condicion, ".
            " CASE WHEN f.pagada THEN 'Si' ELSE 'No' END as pagada, ".
            " ncf, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.totaliva END as totaliva, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.neto END as neto, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.total END as total ,".
            " codpago ".
            " FROM ncf_ventas as nv ".
            " JOIN facturascli as f ON (f.idfactura = nv.documento) ".
            " WHERE idempresa = ".$this->empresa->intval($this->empresa->id)." AND nv.fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin)))." AND nv.codalmacen IN ('".$almacenes."') ".
            " ) ".
            " UNION ALL ".
            " ( ".
            " SELECT 'Compra' as tipo, codalmacen, fecha, nombre, ".
            " CASE WHEN anulada THEN 'Anulado' ELSE 'Activo' END as condicion, ".
            " CASE WHEN pagada THEN 'Si' ELSE 'No' END as pagada, ".
            " numproveedor as ncf, ".
            " CASE WHEN anulada = TRUE THEN 0 else totaliva END as totaliva, ".
            " CASE WHEN anulada = TRUE THEN 0 else neto END as neto, ".
            " CASE WHEN anulada = TRUE THEN 0 else total END as total, ".
            " codpago ".
            " FROM facturasprov ".
            " WHERE fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin)))." AND codalmacen IN ('".$almacenes."') ".
            " ) ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_consolidado." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT t1.tipo,sum(CASE WHEN pagada = 'Si' THEN total ELSE 0 END) as total_pagada,sum(total) as total_general FROM (".
                $sql_consolidado.") as t1 GROUP BY t1.tipo;";
        $data_resumen = $this->db->select($sql_resumen);
        
        $sql_forma_pago = "SELECT codpago, sum(total) as total ".
        " FROM (".
        $sql_consolidado.") as t1 WHERE tipo = 'Venta' group by codpago ORDER BY codpago;";
        $data_forma_pago = $this->db->select($sql_forma_pago);
        $this->total_forma_pago = array();
        if($data_forma_pago) {
            foreach($data_forma_pago as $fpago){
                $this->total_forma_pago[] = (object) $fpago;
            }
        }
        
        if ($json) {
            $resultados = $this->db->select_limit($sql_consolidado, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_consolidado);
        }
        $total_informacion+=$data_cantidad[0]['total'];
        foreach ($data_resumen as $linea) {
            if ($linea['tipo']=='Venta') {
                $this->sumaVentasPagadas += $linea['total_pagada'];
                $this->sumaVentas += $linea['total_general'];
            } elseif ($linea['tipo']=='Compra') {
                $this->sumaComprasPagadas += $linea['total_pagada'];
                $this->sumaCompras += $linea['total_general'];
            }
        }
        
        return array($resultados, $total_informacion);
    }

    /**
     * Reporte consolidado de Ventas y Compras
     */
    public function consolidado()
    {
        $this->sumaVentas = 0;
        $this->sumaCompras = 0;
        $this->saldoConsolidado = 0;
        $this->sumaVentasPagadas = 0;
        $this->sumaComprasPagadas = 0;
        $this->saldoConsolidadoPagadas = 0;
        $this->resultados_consolidado = $this->datos_reporte($this->reporte);
        $this->saldoConsolidado = $this->sumaVentas - $this->sumaCompras;
        $this->saldoConsolidadoPagadas = $this->sumaVentasPagadas - $this->sumaComprasPagadas;

        $this->generar_excel(
            array('Tipo','Almacén','Fecha','Cliente/Proveedor','Condición','Pagada','NCF','ITBIS','Neto','Total','F. Pago'),
            $this->resultados_consolidado,
            array('','','','','','','','','','',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')),
            false
        );
    }

    public function reporteDetalleCompras($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_dcompras = "SELECT  codalmacen, fecha, numproveedor as ncf, f.idfactura as documento, referencia, descripcion, cantidad, pvpunitario as precio, pvptotal as monto ".
            " FROM facturasprov as f".
            " JOIN lineasfacturasprov as fl on (f.idfactura = fl.idfactura)".
            " WHERE fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND anulada = FALSE ".
            " AND codalmacen IN ('".$almacenes."') ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_dcompras." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(cantidad) as cantidad, ".
                " sum(monto) as total FROM (".
                $sql_dcompras.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_dcompras, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_dcompras);
            $this->totalCantidad += $data_resumen[0]['cantidad'];
            $this->totalMonto += $data_resumen[0]['total'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function detalle_compras()
    {
        $this->totalCantidad = 0;
        $this->totalMonto = 0;
        $this->resultados_detalle_compras = $this->datos_reporte($this->reporte);
        $this->total_resultados_detalle_compras = 0;
        $this->generar_excel(
            array('Almacén','Fecha','NCF','Documento','Referencia','Descripción','Cantidad','Precio','Monto'),
            $this->resultados_detalle_compras,
            array('Total','','','','','',$this->totalCantidad, '', $this->totalMonto),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')),
            false
        );
    }

    public function reporteDetalleVentas($almacenes,$json)
    {
        $total_informacion = 0;
        $sql_detalle = "SELECT codalmacen, fecha, ncf, documento, referencia, descripcion, cantidad, ".
            "pvpunitario as precio,((pvpunitario*cantidad)*(dtopor/100)) as descuento, pvptotal as monto ".
            " FROM ncf_ventas ".
            " JOIN lineasfacturascli on (documento = idfactura)".
            " WHERE idempresa = ".$this->empresa->intval($this->empresa->id).
            " AND fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND estado = TRUE ".
            " AND codalmacen IN ('".$almacenes."') ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_detalle." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(cantidad) as total_cantidad, ".
                "sum(monto) as total_monto FROM (".
                $sql_detalle.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_detalle, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_detalle);
            $this->totalCantidad += $data_resumen[0]['total_cantidad'];
            $this->totalMonto += $data_resumen[0]['total_monto'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function detalle_ventas()
    {
        $this->totalCantidad = 0;
        $this->totalMonto = 0;
        $this->resultados_detalle_ventas = $this->datos_reporte($this->reporte);
        $this->total_resultados_detalle_ventas = 0;
        $this->generar_excel(
            array('Almacén','Fecha','NCF','Documento','Referencia','Descripción','Cantidad','Precio','Descuento','Monto'),
            $this->resultados_detalle_ventas,
            array('Total','','','','','',$this->totalCantidad,'', '',$this->totalMonto),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')),
            false
        );
    }

    public function reporteResumenVentas($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_rventas = "SELECT codalmacen, ncf_tipo.tipo_comprobante as tipo_comprobante, ncf_tipo.descripcion as tc_descripcion, ".
            "referencia, lineasfacturascli.descripcion as descripcion, sum(cantidad) as cantidad, sum(pvptotal) as monto ".
            " from ncf_ventas ".
            " join lineasfacturascli on (documento = idfactura) ".
            " join ncf_tipo on (ncf_ventas.tipo_comprobante = ncf_tipo.tipo_comprobante) ".
            " where idempresa = ".$this->empresa->intval($this->empresa->id).
            " AND fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND ncf_ventas.estado = TRUE ".
            " AND codalmacen IN ('".$almacenes."') ".
            " GROUP BY codalmacen,ncf_tipo.tipo_comprobante,ncf_tipo.descripcion, referencia, lineasfacturascli.descripcion ".
            " ORDER BY codalmacen,ncf_tipo.tipo_comprobante";
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_rventas." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(cantidad) as total_cantidad, ".
                "sum(monto) as total_monto FROM (".
                $sql_rventas.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_rventas, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_rventas);
            $this->totalCantidad += $data_resumen[0]['total_cantidad'];
            $this->totalMonto += $data_resumen[0]['total_monto'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function resumen_ventas()
    {
        $this->totalCantidad = 0;
        $this->totalMonto = 0;
        $this->resultados_resumen_ventas = $this->datos_reporte($this->reporte);
        $this->total_resultados_resumen_ventas = 0;
        $this->generar_excel(
            array('Almacén','Tipo NCF','Descripción NCF','Referencia','Descripción Artículo','Cantidad','monto'),
            $this->resultados_resumen_ventas,
            array('Total','','','','',$this->totalCantidad,$this->totalMonto),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right')),
            false
        );
    }

    public function reporteVentas($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_ventas = "SELECT nv.fecha,nv.codalmacen,f.nombrecliente,nv.cifnif,ncf,ncf_modifica,tipo_comprobante, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.neto END as neto, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.totaliva END as totaliva, ".
            " CASE WHEN f.anulada = TRUE THEN 0 ELSE f.total END as total, ".
            " CASE WHEN f.anulada THEN 'Anulado' ELSE 'Activo' END as condicion, ".
            " nv.estado ".
            //" CASE WHEN length(nv.cifnif)=9 THEN 1 ELSE 2 END as cifnif_tipo ".
            " FROM ncf_ventas as nv ".
            " JOIN facturascli as f ON (f.idfactura = nv.documento) ".
            " WHERE idempresa = ".$this->empresa->intval($this->empresa->id)." AND ".
            " nv.fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND nv.codalmacen IN ('".$almacenes."') ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_ventas." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(CASE WHEN t1.condicion = 'Activo' then neto else 0 end) as neto, ".
                "sum(CASE WHEN t1.condicion = 'Activo' then totaliva else 0 end) as totaliva,".
                "sum(CASE WHEN t1.condicion = 'Activo' then total else 0 end) as total FROM (".
                $sql_ventas.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_ventas, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_ventas);
            $this->totalNeto += $data_resumen[0]['neto'];
            $this->totalItbis += $data_resumen[0]['totaliva'];
            $this->totalMonto += $data_resumen[0]['total'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function ventas()
    {
        $this->resultados_ventas = array();
        $this->totalCantidad = 0;
        $this->totalDescuento = 0;
        $this->totalMonto = 0;
        $this->resultados_ventas = $this->datos_reporte($this->reporte);
        $this->total_resultados_ventas = 0;
        $this->generar_excel(
            array('Fecha','Almacén','Cliente','RNC','NCF','NCF Modifica','Tipo','Base Imp.','Itbis','Total','Condicion','Estado'),
            $this->resultados_ventas,
            array('Total','','','','','','',$this->totalNeto,$this->totalItbis,$this->totalMonto,'',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')),
            false
        );
    }

    public function reporteVentasAgente($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_ventas_agente = "SELECT fecha,f.codalmacen, f.codagente, concat(a.nombre,' ',a.apellidos,' ',a.segundo_apellido) as nombre_vendedor, ".
            " g.nombre as grupo_cliente, f.nombrecliente, f.cifnif, ".
            " numero2 as ncf, f.idfactura, f.idfacturarect as afecta_a, referencia, lf.descripcion, ".
            " cantidad, pvpunitario as precio,((pvpunitario*cantidad)*(dtopor/100)) as descuento, pvptotal as monto, ".
            " fp.descripcion as forma_pago, ".
            " case when idfacturarect is null then 'VENTA' else 'DEVOLUCION' end as tipo ".
            " FROM facturascli as f ".
            " JOIN lineasfacturascli as lf on (f.idfactura = lf.idfactura) ".
            " JOIN formaspago as fp on (f.codpago = fp.codpago) ".
            " JOIN agentes as a on (f.codagente = a.codagente) ".
            " JOIN clientes as c on (f.codcliente = c.codcliente) ".
            " LEFT JOIN gruposclientes as g on (c.codgrupo = g.codgrupo) ".
            " WHERE fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND anulada = false AND f.codalmacen IN ('".$almacenes."') ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_ventas_agente." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(cantidad) as total_cantidad, sum(descuento) as total_descuento, ".
                "sum(monto) as total_monto FROM (".
                $sql_ventas_agente.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_ventas_agente, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_ventas_agente);
            $this->totalCantidad += $data_resumen[0]['total_cantidad'];
            $this->totalDescuento += $data_resumen[0]['total_descuento'];
            $this->totalMonto += $data_resumen[0]['total_monto'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function ventas_agente()
    {
        $this->resultados_ventas = array();
        $this->totalCantidad = 0;
        $this->totaldescuento = 0;
        $this->totalMonto = 0;
        $this->resultados_ventas_agente = $this->datos_reporte($this->reporte);
        $this->total_resultados_ventas = 0;
        $this->generar_excel(
            array('Fecha','Almacén','Cod Vend.','Nombre Vendedor','Grupo de Cliente','Cliente','RNC','NCF','Id Fact','Afecta A','Articulo','Descripcion','Cantidad','Precio','Descuento','Monto','Forma Pago','Tipo'),
            $this->resultados_ventas_agente,
            array('Total','','','','','','','','','','',$this->totalCantidad,'',$this->totalDescuento,$this->totalMonto,'',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')),
            false
        );
    }

    public function reporteCompras($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_compras = " SELECT fecha, codalmacen, nombre, cifnif, ".
            " idfactura, numproveedor as ncf, ".
            " CASE WHEN anulada = TRUE THEN 0 else totaliva END as totaliva, ".
            " CASE WHEN anulada = TRUE THEN 0 else neto END as neto, ".
            " CASE WHEN anulada = TRUE THEN 0 else total END as total, ".
            " CASE WHEN anulada THEN 'Anulado' ELSE 'Activo' END as condicion, ".
            " CASE WHEN anulada THEN 'Si' ELSE 'No' END as estado ".
            " FROM facturasprov ".
            " WHERE fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND codalmacen IN ('".$almacenes."') AND anulada = FALSE".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_compras." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(neto) as neto, sum(totaliva) as totaliva, ".
                " sum(total) as total FROM (".
                $sql_compras.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_compras, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_compras);
            $this->totalNeto += $data_resumen[0]['neto'];
            $this->totalItbis += $data_resumen[0]['totaliva'];
            $this->totalMonto += $data_resumen[0]['total'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function compras()
    {
        $this->resultados_compras = array();
        $this->totalNeto = 0;
        $this->totalItbis = 0;
        $this->totalMonto = 0;
        $this->resultados_compras = $this->datos_reporte($this->reporte);
        $this->total_resultados_compras = 0;
        $this->generar_excel(
            array('Fecha','Almacén','Proveedor','RNC','Factura','NCF','Base Imp.','Itbis','Total','Condicion','Anulada'),
            $this->resultados_compras,
            array('Total','','','','','',$this->totalNeto,$this->totalItbis,$this->totalMonto,'',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')),
            false
        );
    }

    public function reporte606($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_606 = " SELECT fp.cifnif, ".
            " CASE WHEN length(fp.cifnif)=9 THEN 1 WHEN length(fp.cifnif)=11 THEN 1 ELSE 3 END as tipo_id, ".
            " CASE WHEN acreedor = TRUE THEN 'Servicios' ELSE 'Bienes y Servicios' END as descripcion, ".
            " fp.numproveedor as ncf, fp2.numproveedor as ncf_modifica, ".
            " concat(extract(year from fp.fecha),lpad(CAST(extract(month from fp.fecha) as char),2,'0')) as fechaym, ".
            " lpad(CAST(extract(day from fp.fecha) as char),2,'0') as fechadd, ".
            " concat(extract(year from ca.fecha),lpad(CAST(extract(month from ca.fecha) as char),2,'0')) as fechapym, ".
            " lpad(CAST(extract(day from ca.fecha) as char),2,'0') as fechapdd, ".
            " fp.totaliva, 0 as totalivaretenido, fp.neto, 0 as totalnetoretenido ".
            " FROM facturasprov as fp ".
            " JOIN proveedores as p on (fp.codproveedor = p.codproveedor) ".
            " left join facturasprov as fp2 ON (fp.idfacturarect = fp2.idfactura) ".
            " left join co_asientos as ca on (fp.idasientop = ca.idasiento AND fp.codejercicio = ca.codejercicio) ".
            " WHERE fp.fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND fp.codalmacen IN ('".$almacenes."') AND fp.anulada = FALSE".
            " ORDER BY fp.codalmacen,fp.".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_606." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(totaliva) as totaliva, ".
                " sum(neto) as totalneto FROM (".
                $sql_606.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        if ($json) {
            $resultados = $this->db->select_limit($sql_606, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_606);
            $this->totalDocumentos += $data_cantidad[0]['total'];
            $this->totalItbis += $data_resumen[0]['totaliva'];
            $this->totalNeto += $data_resumen[0]['totalneto'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function dgii606()
    {
        $this->totalDocumentos = 0;
        $this->totalNeto = 0;
        $this->totalItbis = 0;
        $this->resultados_606 = array();
        $this->resultados_606 = $this->datos_reporte($this->reporte);
        $this->total_resultados_606 = 0;
        $this->generar_excel(
            array('RNC/Cédula','Tipo Id','Tipo Bienes o Servicios Comprados','NCF','NCF Modifica','Fecha AAAAMM','Fecha DD','Fecha Pago AAAAMM','Fecha Pago DD','ITBIS Facturado','ITBIS Retenido','Monto Facturado','Retencion Renta'),
            $this->resultados_606,
            false,
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'right')),
            false
        );
    }

    public function reporte607($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_607 = "SELECT ".
            " CASE WHEN length(nv.cifnif)=9 THEN nv.cifnif WHEN length(nv.cifnif)=11 THEN nv.cifnif ELSE NULL END as cifnif, ".
            " CASE WHEN length(nv.cifnif)=9 THEN 1 WHEN length(nv.cifnif)=11 THEN 1 ELSE 3 END as tipo_id, ".
            " ncf, ncf_modifica, ".
            " concat(extract(year from nv.fecha),lpad(CAST(extract(month from nv.fecha) as char),2,'0'),lpad(CAST(extract(day from nv.fecha) as char),2,'0')) as fecha, ".
            " CASE WHEN anulada = TRUE THEN 0 else totaliva END as totaliva, ".
            " CASE WHEN anulada = TRUE THEN 0 else neto END as neto, ".
            " CASE WHEN estado THEN 'Activo' ELSE 'Anulado' END as estado, ".
            " codpago ".
            " FROM ncf_ventas as nv JOIN facturascli as f on (f.idfactura = nv.documento)".
            " WHERE idempresa = ".$this->empresa->intval($this->empresa->id)." AND ".
            " nv.fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND nv.codalmacen IN ('".$almacenes."') ".
            " ORDER BY nv.codalmacen,nv.".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_607." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        $sql_resumen = "SELECT sum(totaliva) as totaliva, ".
                " sum(neto) as totalneto FROM (".
                $sql_607.") as t1;";
        $data_resumen = $this->db->select($sql_resumen);
        $sql_forma_pago = "SELECT codpago, sum(totaliva) as totaliva, ".
                " sum(neto) as totalneto FROM (".
                $sql_607.") as t1 group by codpago ORDER BY codpago;";
        $data_forma_pago = $this->db->select($sql_forma_pago);
        $this->total_forma_pago_dgii = array();
        if($data_forma_pago) {
            foreach($data_forma_pago as $fpago){
                $this->total_forma_pago_dgii[] = (object) $fpago;
            }
        }
        if ($json) {
            $resultados = $this->db->select_limit($sql_607, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_607);
            $this->totalDocumentos += $data_cantidad[0]['total'];
            $this->totalItbis += $data_resumen[0]['totaliva'];
            $this->totalNeto += $data_resumen[0]['totalneto'];
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function dgii607()
    {
        $this->totalDocumentos = 0;
        $this->totalNeto = 0;
        $this->totalItbis = 0;
        $this->resultados_607 = array();
        $this->resultados_607 = $this->datos_reporte($this->reporte);
        $this->total_resultados_607 = 0;
        $this->generar_excel(
            array('RNC/Cédula','Tipo Id','NCF','NCF Modifica','Fecha','ITBIS Facturado','Monto Facturado','Estado','F. Pago'),
            $this->resultados_607,
            array('Total',count($this->resultados_607).' Documentos','','','','','','',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'right'),array('halign'=>'left'),array('halign'=>'left')),
            false
        );
    }

    public function reporte608($almacenes, $json)
    {
        $total_informacion = 0;
        $sql_608 = "SELECT ncf, ".
            " concat(extract(year from nv.fecha),lpad(CAST(extract(month from nv.fecha) as char),2,'0'),lpad(CAST(extract(day from nv.fecha) as char),2,'0')) as fecha,".
            " motivo,".
            " CASE WHEN estado THEN 'Activo' ELSE 'Anulado' END as estado  FROM ncf_ventas as nv ".
            " WHERE idempresa = ".$this->empresa->intval($this->empresa->id)." AND ".
            " fecha between ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_inicio))).
            " AND ".$this->empresa->var2str(\date("Y-m-d", strtotime($this->fecha_fin))).
            " AND codalmacen IN ('".$almacenes."') and estado = false ".
            " ORDER BY codalmacen,".$this->sort." ".$this->order;
        $sql_cantidad = "SELECT count(*) as total FROM ( ".$sql_608." ) as t1;";
        $data_cantidad = $this->db->select($sql_cantidad);
        if ($json) {
            $resultados = $this->db->select_limit($sql_608, $this->limit, $this->offset);
        } else {
            $resultados = $this->db->select($sql_608);
        }
        $total_informacion+=$data_cantidad[0]['total'];
        return array($resultados, $total_informacion);
    }

    public function dgii608()
    {
        $this->totalDocumentos = 0;
        $this->resultados_608 = array();
        $this->resultados_608 = $this->datos_reporte($this->reporte);
        $this->total_resultados_608 = 0;
        $this->generar_excel(
            array('NCF','Fecha','Motivo','Estado'),
            $this->resultados_608,
            array('Total',count($this->resultados_608).' Documentos','',''),
            false,
            array(array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left')),
            false
        );
    }

    public function facturas_proveedor($desde, $hasta, $codalmacen)
    {
        $lista = array();
        $sql = "SELECT * FROM facturasprov ".
            " WHERE codalmacen = ".$this->empresa->var2str($codalmacen).
            " AND fecha between ".$this->empresa->var2str($desde)." AND ".$this->empresa->var2str($hasta).
            " ORDER BY fecha, idfactura;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
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
    public function generar_excel($cabecera, $datos, $pie, $estilo_cab, $estilo_datos, $estilo_pie)
    {
        if ($datos) {
            //Revisamos que no haya un archivo ya cargado
            $archivo = str_replace("-", "_", $this->reporte);
            $this->archivoXLSX = $this->exportDir . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
            $this->archivoXLSXPath = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
            if (file_exists($this->archivoXLSX)) {
                unlink($this->archivoXLSX);
            }
            //Variables para cada parte del excel
            $estilo_cabecera = ($estilo_cab)?$estilo_cab:array('border'=>'left,right,top,bottom','font-style'=>'bold');
            $estilo_cuerpo = ($estilo_datos)?$estilo_datos:array('halign'=>'none');
            $estilo_footer = ($estilo_pie)?$estilo_pie:array('border'=>'left,right,top,bottom','font-style'=>'bold','color'=>'#FFFFFF','fill'=>'#000000');
            //Inicializamos la clase
            $this->writer = new XLSXWriter();
            $this->writer->setAuthor('FacturaScripts '.\date('Y-m-d H:i:s'));
            $nombre_hoja = ucfirst(str_replace('-', ' ', $this->reporte));
            $this->writer->writeSheetHeader($nombre_hoja, array(), true);
            $this->writer->writeSheetRow($nombre_hoja, $cabecera, $estilo_cabecera);
            foreach ($datos as $linea) {
                $this->writer->writeSheetRow($nombre_hoja, (array) $linea, $estilo_cuerpo);
            }
            if (!empty($pie)) {
                $this->writer->writeSheetRow($nombre_hoja, (array) $pie, $estilo_footer);
            }
            $this->writer->writeToFile($this->archivoXLSXPath);
        }
    }

    private function share_extensions()
    {
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

        foreach ($extensiones as $ext) {
            $fext = new fs_extension($ext);
            if (!$fext->save()) {
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
    private function comma_separated_to_array($string, $separator = ',')
    {
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
