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
require_once 'plugins/republica_dominicana/vendor/rospdf/pdf-php/src/Cezpdf.php';
require_once 'extras/xlsxwriter.class.php';

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
    public $fileXLSX;
    public $filePDF;
    public $archivo = 'Estado_Cuenta';
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Estado de Cuenta Clientes', 'informes', false, true, false);
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
            $this->sort = 'codalmacen, fecha, idfactura ';
            $this->order = 'ASC';
            $this->crearXLSX();
            $this->crearPDF();
        }
    }

    protected function init_filters()
    {
        $cli0 = new cliente();
        $this->hasta = $this->confirmarValor($this->filter_request('hasta'),\date('d-m-Y'));
        $this->codserie = $this->confirmarValor($this->filter_request('codserie'),false);
        $this->codpago = $this->confirmarValor($this->filter_request('codpago'),false);
        $this->codagente = $this->confirmarValor($this->filter_request('codagente'),false);
        $this->codalmacen = $this->confirmarValor($this->filter_request('codalmacen'),false);
        $this->coddivisa = $this->confirmarValor($this->filter_request('coddivisa'),$this->empresa->coddivisa);
        $this->cliente = ($this->filter_request('codcliente') && $this->filter_request('codcliente') != '')?$cli0->get($this->filter_request('codcliente')):false;
        $this->current_date = $this->empresa->var2str(\date('Y-m-d', strtotime($this->hasta)));
    }

    public function vencimiento_facturas()
    {
        list($funcion, $separador) = $this->funcionIntervalo();
        $sql = "select codalmacen, ".
        " sum(case when ".$funcion.$this->current_date.$separador."vencimiento) <= 30 then total else 0 end) as d30, ".
        " sum(case when ".$funcion.$this->current_date.$separador."vencimiento) > 30 and ".$funcion.$this->current_date.$separador."vencimiento) <= 60 then total else 0 end) as d60, ".
        " sum(case when ".$funcion.$this->current_date.$separador."vencimiento) > 60 and ".$funcion.$this->current_date.$separador."vencimiento) <= 90 then total else 0 end) as d90, ".
        " sum(case when ".$funcion.$this->current_date.$separador."vencimiento) > 90 and ".$funcion.$this->current_date.$separador."vencimiento) <= 120 then total else 0 end) as d120, ".
        " sum(case when ".$funcion.$this->current_date.$separador."vencimiento) > 120 then total else 0 end) as mas120 ".
        " from facturascli "." where anulada = false and pagada = false and idfacturarect IS NULL ".$this->sql_aux." group by codalmacen;";
        $data = $this->db->select($sql);
        $this->resultados = array();
        if (!empty($data)) {
            $totalDeuda = 0;
            foreach ($data as $d) {
                $this->comprobar_deuda($d, $totalDeuda);
            }
        }
    }

    public function comprobar_deuda($datos, &$totalDeuda)
    {
        $totalDeuda += $datos['d30']+$datos['d60']+$datos['d90']+$datos['d120']+$datos['mas120'];
        if($totalDeuda){
            $item = new stdClass();
            $item->codalmacen = $datos['codalmacen'];
            $item->nombre_almacen = $this->almacen->get($datos['codalmacen'])->nombre;
            $item->d30 = $datos['d30'];
            $item->d30_pcj = round(($datos['d30']/$totalDeuda)*100, 0);
            $item->d60 = $datos['d60'];
            $item->d60_pcj = round(($datos['d60']/$totalDeuda)*100, 0);
            $item->d90 = $datos['d90'];
            $item->d90_pcj = round(($datos['d90']/$totalDeuda)*100, 0);
            $item->d120 = $datos['d120'];
            $item->d120_pcj = round(($datos['d120']/$totalDeuda)*100, 0);
            $item->mas120 = $datos['mas120'];
            $item->mas120_pcj = round(($datos['mas120']/$totalDeuda)*100, 0);
            $item->totaldeuda = $totalDeuda;
            $this->resultados[] = $item;
        }
    }

    public function tabla_de_datos()
    {
        $data = array();
        $this->template = false;
        $dias = \filter_input(INPUT_POST, 'dias');
        $offset = \filter_input(INPUT_POST, 'offset');
        $sort = \filter_input(INPUT_POST, 'sort');
        $order = \filter_input(INPUT_POST, 'order');
        $limit = \filter_input(INPUT_POST, 'limit');
        $this->limit = ($limit)?$limit:FS_ITEM_LIMIT;
        $this->sort = ($sort and $sort!='undefined')?$sort:'codalmacen, fecha, idfactura ';
        $this->order = ($order and $order!='undefined')?$order:'ASC';
        $datos = $this->listado_facturas($dias, $offset);
        $resultados = $datos['resultados'];
        $total_informacion = $datos['total'];
        header('Content-Type: application/json');
        $data['rows'] = $resultados;
        $data['total'] = $total_informacion;
        echo json_encode($data);
    }

    public function listado_facturas($dias, $offset = 0)
    {
        list($funcion, $separador) = $this->funcionIntervalo();
        $intervalo = $this->intervalo_tiempo($dias);
        $sql = "SELECT codalmacen, idfactura, codigo, numero2, nombrecliente, fecha, vencimiento, coddivisa, total, pagada, ".$funcion.$this->current_date.$separador."vencimiento) as atraso ".
            " FROM facturascli ".
            " WHERE anulada = false and pagada = false and idfacturarect IS NULL ".$intervalo.$this->sql_aux.
            " ORDER BY ".$this->sort.' '.$this->order;
        if($offset){
            $data = $this->db->select_limit($sql, $this->limit, $offset);
        }else{
            $data = $this->db->select($sql);
        }
        $sql_total = "SELECT count(*) as total".
            " FROM facturascli ".
            " WHERE anulada = false and pagada = false and idfacturarect IS NULL ".$intervalo.$this->sql_aux.";";
        $data_total = $this->db->select($sql_total);
        return array('resultados'=>$data,'total'=>$data_total[0]['total']);
    }

    public function intervalo_tiempo($dias)
    {
        list($funcion, $separador) = $this->funcionIntervalo();
        $intervalo = '';
        switch ($dias) {
            case 30:
                $intervalo = " AND ".$funcion.$this->current_date.$separador."vencimiento) <= 30 and ".$funcion.$this->current_date.$separador."vencimiento) > 0";
                break;
            case 60:
                $intervalo = " AND ".$funcion.$this->current_date.$separador."vencimiento) > 30 and ".$funcion.$this->current_date.$separador."vencimiento) <= 60";
                break;
            case 90:
                $intervalo = " AND ".$funcion.$this->current_date.$separador."vencimiento) > 60 and ".$funcion.$this->current_date.$separador."vencimiento) <= 90";
                break;
            case 120:
                $intervalo = " AND ".$funcion.$this->current_date.$separador."vencimiento) > 90 and ".$funcion.$this->current_date.$separador."vencimiento) <= 120";
                break;
            case 121:
                $intervalo = " AND ".$funcion.$this->current_date.$separador."vencimiento) > 120";
                break;
        }
        return $intervalo;
    }

    public function funcionIntervalo()
    {
        $funcion =  "datediff(";
        $separador = " , ";
        if(FS_DB_TYPE === 'POSTGRESQL'){
            $funcion = "(";
            $separador = " - ";
        }
        return array($funcion, $separador);
    }

    public function sql_aux()
    {
        $estado = ($this->estado == 'pagada')?true:false;
        $sql = '';
        $sql .= ($this->hasta)?' AND fecha <= '.$this->empresa->var2str(\date('Y-m-d', strtotime($this->hasta))):'';
        $sql .= ($this->codserie)?' AND codserie = '.$this->empresa->var2str($this->codserie):'';
        $sql .= ($this->codpago)?' AND codpago = '.$this->empresa->var2str($this->codpago):'';
        $sql .= ($this->codagente)?' AND codagente = '.$this->empresa->var2str($this->codagente):'';
        $sql .= ($this->codalmacen)?' AND codalmacen = '.$this->empresa->var2str($this->codalmacen):'';
        $sql .= ($this->coddivisa)?' AND coddivisa = '.$this->empresa->var2str($this->coddivisa):'';
        $sql .= ($this->cliente)?' AND codcliente = '.$this->empresa->var2str($this->cliente->codcliente):'';
        $sql .= ($this->estado)?' AND pagada = '.$this->empresa->var2str($estado):'';
        $this->sql_aux = $sql;
    }

    private function share_extensions()
    {
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

        foreach ($extensiones as $ext) {
            $fext = new fs_extension($ext);
            if (!$fext->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }

    public function crearPDF()
    {
        $this->carpetasPlugin();
        $pdf_doc = new Cezpdf('letter', 'portrait');
        $pdf_doc->ezStartPageNumbers(590,25,8,'right','{PAGENUM} de {TOTALPAGENUM}',1);
        $this->archivoPDF = $this->exportDir . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".pdf";
        $this->archivoPDFPath = $this->publicPath . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".pdf";
        if (file_exists($this->archivoPDF)) {
            unlink($this->archivoPDF);
        }
        $pdf_doc->selectFont(__DIR__ . "/ezpdf/fonts/Helvetica.afm");
        $pdf_doc->selectFont('Helvetica');
        $pdf_doc->ezSetMargins (25,25,25,25);
        $logo = '';
        if(file_exists(FS_PATH.FS_MYDOCS.'images/logo.png')){
            $logo = FS_PATH.FS_MYDOCS.'images/logo.png';
        }elseif(file_exists(FS_PATH.FS_MYDOCS.'images/logo.jpg')){
            $logo = FS_PATH.FS_MYDOCS.'images/logo.jpg';
        }
        if($logo){
            $pdf_doc->ezImage($logo, 0, 80, 'none', 'left');
        }
        $pdf_doc->addText(110,745,9,'<b>'.$this->empresa->nombre.'</b>',0, 'left');
        $pdf_doc->addText(110,730,9,'<b>'.$this->empresa->direccion.'</b>',0, 'left');
        $pdf_doc->addText(580,745,9,'<b>'.'Estado de Cuenta'.'</b>',0, 'right');
        $pdf_doc->addText(580,730,9,'<b>'.'Al: '.'</b>'.\date('d-m-Y'),0, 'right');

        //$pdf_doc->ezStream();
        $this->guardarPDF($this->archivoPDF, $pdf_doc);
        $this->filePDF = $this->archivoPDFPath;
    }

    /**
     * Vuelca el documento PDF en la salida estándar.
     * @param string $filename
     */
    public function mostrarPDF($filename = 'doc.pdf')
    {
        $this->pdf->ezStream(array('Content-Disposition' => $filename));
    }

    /**
     * Guarda el documento PDF en el archivo $filename
     * @param string $filename
     * @return boolean
     */
    public function guardarPDF($filename, $pdf_doc)
    {
        if ($filename) {
            if (file_exists($filename)) {
                unlink($filename);
            }

            $file = fopen($filename, 'a');
            if ($file) {
                fwrite($file, $pdf_doc->ezOutput());
                fclose($file);
                return TRUE;
            }

            return TRUE;
        }

        return FALSE;
    }


    public function crearXLSX()
    {
        $this->carpetasPlugin();
        $this->archivoXLSX = $this->exportDir . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".xlsx";
        $this->archivoXLSXPath = $this->publicPath . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->archivoXLSX)) {
            unlink($this->archivoXLSX);
        }
        $style_header = array('border'=>'left,right,top,bottom','font'=>'Arial','font-size'=>10,'font-style'=>'bold');
        $header = array('Almacén'=>'string','Cliente'=>'string','Factura'=>'string', ucfirst(FS_NUMERO2)=>'string',
            'Importe'=>'price','Fecha de Emisión'=>'date','Fecha de Vencimiento'=>'date','Días Atraso'=>'integer');
        $headerText = array('codalmacen'=>'Almacén','nombrecliente'=>'Cliente','codigo'=>'Factura','numero2'=>ucfirst(FS_NUMERO2),'total'=>'Importe','fecha'=>'Fecha de Emisión','vencimiento'=>'Fecha de Vencimiento','atraso'=>'Días Atraso');
        $writer = new XLSXWriter();
        foreach($this->vencimientos as $dias){
            $hoja_nombre = ($dias!==121)?'Facturas a '.$dias.' dias':'Facturas a mas de 120 dias';
            $writer->writeSheetRow($hoja_nombre, $headerText, $style_header);
            $writer->writeSheetHeader($hoja_nombre, $header, true);
            //$writer->writeSheetRow($hoja_nombre, $headerText, $style_header);
            $datos = $this->listado_facturas($dias);
            $this->agregarDatosXLSX($writer, $hoja_nombre, $datos['resultados'], $headerText);
        }
        $writer->writeToFile($this->archivoXLSXPath);
        $this->fileXLSX = $this->archivoXLSXPath;
    }

    public function agregarDatosXLSX(&$writer, $hoja_nombre, $datos, $indice)
    {
        $style_footer = array('border'=>'left,right,top,bottom','font'=>'Arial','font-size'=>10,'font-style'=>'bold','color'=>'#fff','fill'=>'#000');
        $total_importe = 0;
        if($datos){
            $total_documentos = count($datos);
            foreach($datos as $linea){
                $data = $this->prepararDatosXLSX($linea, $indice, $total_importe);
                $writer->writeSheetRow($hoja_nombre, $data);
            }
            $writer->writeSheetRow($hoja_nombre, array('','','',$total_documentos.' Documentos',$total_importe,'','',''), $style_footer);
        }
    }

    public function prepararDatosXLSX($linea, $indice, &$total_importe)
    {
        //var_dump($linea);
        $item = array();
        foreach($indice as $idx=>$desc){
            $item[] = $linea[$idx];
            if($idx == 'total'){
                $total_importe += $linea['total'];
            }
        }
        return $item;
    }
}
