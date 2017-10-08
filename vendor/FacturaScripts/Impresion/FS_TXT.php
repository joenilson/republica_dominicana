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

namespace FacturaScripts\Impresion;

/**
 * Description of FS_TXT
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class FS_TXT {
    public $fp;
    public $empresa;
    public $clipro;
    public $documento;
    public $page_size;
    public $lineas;
    public $orientacion;
    protected $NewPageGroup;   // variable indicating whether a new group was requested
    protected $PageGroups;     // variable containing the number of pages of the groups
    protected $CurrPageGroup;
    /**
     *
     * @param array $opciones
     */
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'LETTER', $file = 'documento.txt')
    {
        $file_name = $opciones['tmp'].$file;
        $this->page_size = $size;
        $this->lineas = $opciones['page_lines'];
        $this->orientacion = ($opciones['page_orientation'])?$opciones['page_orientation']:'P';
        $this->fp = fopen($file_name, 'w') or die('CANNOT_OPEN_FILE: '.$file_name);
    }

    public function file_header($empresa,$clipro=false,$documento=false)
    {
        //Dibujamos el esqueleto del documento
        fputs($this->fp, sprintf("%s%30s%s\n\r",'+',str_repeat('-', 30),'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->nombre,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->cifnif,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->direccion,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+','','+'));
        fputs($this->fp, sprintf("%s%30s%s\n\r",'+',str_repeat('-', 30),'+'));
    }

    public function file_contents()
    {

    }

    public function file_footer()
    {

    }

    public function file_close()
    {
        fclose($this->fp);
    }

        public function addCabecera(){

    }

    public function addEmpresaInfo(){

    }

    public function addDocumentoInfo(){

    }

    public function addCabeceraInfo(){

    }

    public function AddCabeceraLineas(){

    }

    public function addDetalleLineas(){

    }

    public function addTotalesLineas(){

    }

    public function Footer(){

    }

    public function addObservaciones(){

    }

    public function addFirmas(){

    }

    public function cerrarArchivo(){

    }

    public function Output(){

    }

    // create a new page group; call this before calling AddPage()
    public function StartPageGroup()
    {
        $this->NewPageGroup = true;
    }

    // current page in the group
    public function GroupPageNo()
    {
        return $this->PageGroups[$this->CurrPageGroup];
    }

    // alias of the current page group -- will be replaced by the total number of pages in this group
    public function PageGroupAlias()
    {
        return $this->CurrPageGroup;
    }

    public function _beginpage($orientation, $format, $rotation)
    {
        parent::_beginpage($orientation, $format, $rotation);
        if($this->NewPageGroup)
        {
            // start a new group
            $n = sizeof($this->PageGroups)+1;
            $alias = "{nb$n}";
            $this->PageGroups[$alias] = 1;
            $this->CurrPageGroup = $alias;
            $this->NewPageGroup = false;
        }
        elseif($this->CurrPageGroup)
            $this->PageGroups[$this->CurrPageGroup]++;
    }

    public function _putpages()
    {
        $nb = $this->page;
        if (!empty($this->PageGroups))
        {
            // do page number replacement
            foreach ($this->PageGroups as $k => $v)
            {
                for ($n = 1; $n <= $nb; $n++)
                {
                    $this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
                }
            }
        }
        parent::_putpages();
    }
}
