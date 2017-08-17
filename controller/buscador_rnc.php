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
class buscador_rnc extends fs_controller
{
    public $dgii_web;
    public $resultados;
    public $total_resultados;
    public $cabecera;
    public $total_cabecera;
    public $detalle;
    public $rnc;
    public $nombre;
    public $viewstate;
    public $eventvalidation;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Buscador de RNC', 'contabilidad', false, false, true);
    }

    protected function private_core()
    {
        $this->resultados = false;
        $this->total_resultados = 0;

        $tipo = filter_input(INPUT_POST, 'tipo');
        switch ($tipo) {
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

    //Pedimos que nos den el VIEWSTATE y el EVENTVALIDATION a la página de busqueda
    public function autorizacion()
    {
        $h = curl_init();
        curl_setopt($h, CURLOPT_URL, 'http://www.dgii.gov.do/app/WebApps/Consultas/rnc/RncWeb.aspx');
        curl_setopt($h, CURLOPT_HEADER, false);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($h);
        curl_close($h);
        $html = str_get_html($result);
        $this->viewstate = $html->getElementById('#__VIEWSTATE', 0)->value;
        $this->eventvalidation = $html->getElementById('#__EVENTVALIDATION', 0)->value;
    }

    //Si la busqueda no es por RNC y en su lugar es por nombre actualizamos viewstate y eventvalidation
    public function actualizacion_autorizacion($tipoBusqueda)
    {
        $post = array(
            '__EVENTTARGET' => 'rbtnlTipoBusqueda$1',
            '__EVENTARGUMENT' => "",
            '__LASTFOCUS' => "",
            '__VIEWSTATE' => $this->viewstate,
            '__EVENTVALIDATION' => $this->eventvalidation,
            'rbtnlTipoBusqueda' => $tipoBusqueda,
            'txtRncCed' => ''
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
        $this->viewstate = $html->getElementById('#__VIEWSTATE', 0)->value;
        $this->eventvalidation = $html->getElementById('#__EVENTVALIDATION', 0)->value;
    }

    public function buscar()
    {
        $this->autorizacion();
        $rnc = filter_input(INPUT_POST, 'rnc');
        $nombre = filter_input(INPUT_POST, 'nombre');
        $tipoBusqueda = (!empty($rnc)) ? 0 : 1;
        $valor_a_buscar = (!empty($rnc)) ? $rnc : strtoupper(trim($nombre));
        $this->rnc = $rnc;
        $this->nombre = $nombre;
        $campo = (!empty($rnc)) ? 'txtRncCed' : 'txtRazonSocial';
        $boton = (!empty($rnc)) ? 'btnBuscaRncCed' : 'btnBuscaRazonSocial';
        if ($tipoBusqueda == 1) {
            $this->actualizacion_autorizacion($tipoBusqueda);
        }
        $post = array(
            '__EVENTTARGET' => "",
            '__EVENTARGUMENT' => "",
            '__LASTFOCUS' => "",
            '__VIEWSTATE' => $this->viewstate,
            '__EVENTVALIDATION' => $this->eventvalidation,
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
        $vacio = trim($html->getElementById('#lblMsg', 0)->plaintext);
        if ($vacio) {
            $this->resultados = $html->getElementById('#lblMsg', 0)->plaintext;
        } else {
            $cabeceras = array();
            $detalles = array();
            foreach ($html->find('.tabla_titulo') as $lista) {
                $cabeceras = $this->loop_lista($lista);
            }
            $this->cabecera = $cabeceras;
            $lista_interna = 0;
            foreach ($html->find('.GridItemStyle') as $lista) {
                $detalles[$lista_interna] = $this->loop_lista($lista);
                $lista_interna++;
            }
            foreach ($html->find('.bg_celdas_alt') as $lista) {
                $detalles[$lista_interna] = $this->loop_lista($lista);
                $lista_interna++;
            }
            $this->detalle = $detalles;
            $this->total_cabecera = count($cabeceras);
            $this->total_resultados = count($this->detalle);
        }
    }
    
    private function loop_lista($lista)
    {
        $array = array();
        foreach ($lista->find('td') as $item) {
            $array[] = $item->plaintext;
        }
        return $array;
    }

    public function guardar()
    {
    }
}
