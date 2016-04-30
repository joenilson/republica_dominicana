<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
require_once 'plugins/republica_dominicana/extras/simple_html_dom.php';
/**
 * Description of buscador_rnc
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class buscador_rnc extends fs_controller{
    public $dgii_web;
    public $resultados;
    public $total_resultados;
    public $cabecera;
    public $total_cabecera;
    public $detalle;
    public $rnc;
    public $nombre;

    public function __construct($name = '', $title = 'home', $folder = '', $admin = FALSE, $shmenu = TRUE, $important = FALSE) {
        parent::__construct(__CLASS__, 'Buscador de RNC', 'contabilidad', FALSE, FALSE, TRUE);
    }

    protected function private_core() {
        $this->resultados = false;
        $this->total_resultados = 0;
        $tipo = filter_input(INPUT_POST, 'tipo');
        if(!empty($tipo)){
            switch ($tipo){
                case "buscar":
                    $this->buscar();
                    break;
                case "guardar":
                    $this->guardar();
                    break;
                default:
                    break;
            }
        }


    }

    public function buscar(){
        $rnc = filter_input(INPUT_POST, 'rnc');
        $nombre = filter_input(INPUT_POST, 'nombre');
        $tipoBusqueda = (!empty($rnc))?0:1;
        $valor_a_buscar = (!empty($rnc))?$rnc:strtoupper(trim($nombre));
        $this->rnc = $rnc;
        $this->nombre = $nombre;
        $campo = (!empty($rnc))?'txtRncCed':'txtRazonSocial';
        $boton = (!empty($rnc))?'btnBuscaRncCed':'btnBuscaRazonSocial';
        $post = array(
            '__EVENTTARGET'=>"",
            '__EVENTARGUMENT'=>"",
            '__LASTFOCUS'>"",
            '__VIEWSTATE'=>($tipoBusqueda==0)?"/wEPDwUKMTY4ODczNzk2OA9kFgICAQ9kFgQCAQ8QZGQWAWZkAg0PDxYCHgdWaXNpYmxlZ2QWBAIBDw8WAh4EVGV4dGVkZAIDDzwrAAsBAA8WCh4IRGF0YUtleXMWAB4LXyFJdGVtQ291bnQCAR4JUGFnZUNvdW50AgEeFV8hRGF0YVNvdXJjZUl0ZW1Db3VudAIBHwBnZBYCZg9kFgICAQ9kFgxmDw8WAh8BBQkxMzAwMTI0MDdkZAIBDw8WAh8BBSRJTkRVU1RSSUFTIFNBTiBNSUdVRUwgREVMIENBUklCRSBTIEFkZAICDw8WAh8BBSBJTkRVU1RSSUFTIFNBTiBNSUdVRUwgREVMIENBUklCRWRkAgMPDxYCHwEFD0RFUy4gRlJPTlRFUkEgIGRkAgQPDxYCHwEFBk5PUk1BTGRkAgUPDxYCHwEFBkFDVElWT2RkZCvF5/rOh+O96q82YrDFN/M4w6Bp":"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",
            '__EVENTVALIDATION'=>($tipoBusqueda==0)?"/wEWBgK4mJwzApPThYkEAozThYkEAoO8r+cIAqPC2ZIEAsa0/vgKz3FdauzfSiPnd2H2zkLd4PSz+sE=":"/wEWBgLWqfCIAgKT04WJBAKM04WJBAKDvK/nCALrxcShDQKTzMDhBMWBP644j+YvKIDTiad19QWb7ofy",
            'rbtnlTipoBusqueda' => $tipoBusqueda,
            $campo => $valor_a_buscar,
            $boton => 'Buscar'
        );
        $query = http_build_query($post);

        $h = curl_init();
        curl_setopt($h, CURLOPT_URL, 'http://www.dgii.gov.do/app/WebApps/Consultas/rnc/RncWeb.aspx');
        curl_setopt($h, CURLOPT_POST, true);
        curl_setopt($h, CURLOPT_POSTFIELDS, $query);
        curl_setopt($h, CURLOPT_HEADER, false);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($h);
        curl_close($h);
        $html = str_get_html($result);
        $vacio = trim($html->getElementById('#lblMsg',0)->plaintext);
        if($vacio){
           $this->resultados = $html->getElementById('#lblMsg',0)->plaintext;
        }else{
            $cabeceras = array();
            $detalles = array();
            foreach($html->find('.tabla_titulo') as $lista){
               foreach($lista->find('td') as $item){
                  $cabeceras[]=$item->plaintext;
               }
            }
            $this->cabecera = $cabeceras;
            $lista_interna = 0;
            foreach($html->find('.GridItemStyle') as $lista){

               foreach($lista->find('td') as $item){
                  $detalles[$lista_interna][]=$item->plaintext;
               }
               $lista_interna++;
            }
            foreach($html->find('.bg_celdas_alt') as $lista){

               foreach($lista->find('td') as $item){
                  $detalles[$lista_interna][]=$item->plaintext;
               }
               $lista_interna++;
            }
            $this->detalle = $detalles;
            $this->total_cabecera = count($cabeceras);
            $this->total_resultados = count($this->detalle);
        }
    }

    public function guardar(){

    }
}
