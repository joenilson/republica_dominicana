<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts;
require_once 'plugins/distribucion/vendors/FacturaScripts/Impresion/FS_PDF.php';
require_once 'plugins/distribucion/vendors/FacturaScripts/Impresion/FS_TXT.php';
require_once 'plugins/distribucion/vendors/FacturaScripts/Impresion/FS_XLSX.php';
use FacturaScripts\Impresion\FS_TXT;
use FacturaScripts\Impresion\FS_PDF;
use FacturaScripts\Impresion\FS_XLSX;
/**
 * Description of PrintingManager
 * Clase para controlar desde un solo sitio la generación de documentos
 * para imprimir de FS
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class PrintingManager {
    public $file;
    public $type;
    public $tmp_dir;
    public $fs_txt;
    public $fs_pdf;
    public $font;
    public $page_size;
    public $page_lines;
    public $page_units = 'mm';
    public $page_orientation;
    public $linea_espacio;
    public $fileHandler;
    public function __construct(array $info) {
        $this->file = (isset($info['file']))?$info['file']:'doc.pdf';
        $this->type = (isset($info['type']))?$info['type']:'pdf';
        $this->font = (isset($info['font']))?$info['font']:'Arial';
        $this->page_size = (isset($info['page_size']))?$info['page_size']:'letter';
        $this->page_lines = (isset($info['page_lines']))?$info['page_lines']:27;
        $this->linea_espacio = (isset($info['linea_espacio']))?$info['linea_espacio']:5;
        $this->page_orientation = (isset($info['page_orientation']))?$info['page_orientation']:'P';
        $this->tmp_dir = sys_get_temp_dir();
    }

    public function crearArchivo()
    {
        $opciones['tmp'] = $this->tmp_dir;
        $opciones['file'] = $this->file;
        $opciones['page_size'] = $this->page_size;
        $opciones['page_lines'] = $this->page_lines;
        $opciones['page_orientation'] = $this->page_orientation;
        if($this->type == 'pdf')
        {
            $this->fileHandler = new FS_PDF($this->page_orientation, $this->page_units, $this->page_size, $this->tmp_dir.DIRECTORY_SEPARATOR.$this->file);
        }
        elseif($this->type == 'txt')
        {
            $this->fileHandler = new FS_TXT($this->page_orientation, $this->page_units, $this->page_size, $this->tmp_dir.DIRECTORY_SEPARATOR.$this->file);
        }
        elseif($this->type == 'xlsx')
        {
            $this->fileHandler = new FS_XLSX($this->tmp_dir.DIRECTORY_SEPARATOR.$this->file);
        }
        if(!$this->fileHandler) {
            return false;
        }
    }

    public function agregarCabecera(\empresa $empresa, $documento, $cabecera){
        $this->fileHandler->fs_font = $this->font;
        $this->fileHandler->fs_espacio = $this->linea_espacio;
        $this->fileHandler->empresa = $empresa;
        $this->fileHandler->cabecera_info = $cabecera;
        $this->fileHandler->addCabecera();
        $this->fileHandler->documento_nombre = $documento->nombre;
        $this->fileHandler->documento_numero = $documento->numero;
        $this->fileHandler->documento_cabecera_lineas = $documento->cabecera_lineas;
        $this->fileHandler->addEmpresaInfo();
        $this->fileHandler->addDocumentoInfo();
        $this->fileHandler->addCabeceraInfo();
        $this->fileHandler->addCabeceraLineas();
    }

    public function agregarLineas(array $lineas, $separador = false){
        $this->fileHandler->addDetalleLineas($lineas, $separador);
    }

    public function agregarObservaciones($observaciones){
        $this->fileHandler->addObservaciones($observaciones);
    }

    public function agregarTotalesLineas($totales){
        $this->fileHandler->addTotalesLineas($totales);
    }

    public function agregarFirmas(array $firmas){
        $this->fileHandler->addFirmas($firmas);
    }

    public function mostrarDocumento(){
        $this->fileHandler->Output();
    }
}
