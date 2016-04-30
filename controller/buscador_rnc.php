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
    public function __construct($name = '', $title = 'home', $folder = '', $admin = FALSE, $shmenu = TRUE, $important = FALSE) {
        parent::__construct(__CLASS__, 'Buscador de RNC', 'contabilidad', FALSE, FALSE, TRUE);
    }

    protected function private_core() {
        $this->resultados = false;
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
        $valor_a_buscar = filter_input(INPUT_POST, 'rnc');

        $post = array(
            '__EVENTTARGET'=>"",
            '__EVENTARGUMENT'=>"",
            '__LASTFOCUS'>"",
            '__VIEWSTATE'=>"/wEPDwUKMTY4ODczNzk2OA9kFgICAQ9kFgQCAQ8QZGQWAWZkAg0PZBYCAgMPPCsACwBkZHTpAYYQQIXs/JET7TFTjBqu3SYU",
            '__EVENTVALIDATION'=>"/wEWBgKl57TuAgKT04WJBAKM04WJBAKDvK/nCAKjwtmSBALGtP74CtBj1Z9nVylTy4C9Okzc2CBMDFcB",
            'rbtnlTipoBusqueda' => '0',
            'txtRncCed' => $valor_a_buscar,
            'btnBuscaRncCed'=> 'Buscar'
        );

        $query = http_build_query($post);

        $h = curl_init();
        //curl_setopt($h, CURLOPT_PROXYPORT, 3128);
        //curl_setopt($h, CURLOPT_PROXYTYPE, 'HTTP');
        //curl_setopt($h, CURLOPT_PROXY, '192.168.3.84');
        curl_setopt($h, CURLOPT_URL, 'http://www.dgii.gov.do/app/WebApps/Consultas/rnc/RncWeb.aspx');
        curl_setopt($h, CURLOPT_POST, true);
        curl_setopt($h, CURLOPT_POSTFIELDS, $query);
        curl_setopt($h, CURLOPT_HEADER, false);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($h);
        curl_close($h);
        $html = file_get_html($result);
        $this->resultados = $html->find('div[id=pnlResultadoRuc]');
    }

    public function guardar(){

    }
}
