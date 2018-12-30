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
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'plugins/republica_dominicana/extras/rd_controller.php';

/**
 * Description of buscador_rnc
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class buscador_rnc extends rd_controller
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
    public $offset;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Buscador de RNC', 'contabilidad', false, false, true);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->cargar_config();
        $this->grupo = new grupo_clientes();
        $this->grupos = $this->grupo->all();
        $this->pais = new pais();
        $this->serie = new serie();
        $this->resultados = false;
        $this->total_resultados = 0;

        $tipo = $this->filter_request('tipo');
        switch ($tipo) {
            case "buscar":
                $this->buscar();
                break;
            default:
                break;
        }
    }

    public function buscar()
    {
        $rnc = $this->filter_request('rnc');
        $nombre = $this->filter_request('nombre');
        $offset = $this->filter_request('offset');
        $this->rnc = $rnc;
        $this->nombre = $nombre;
        $patronBusqueda = (!empty($rnc)) ? 0 : 1;
        $paramValue = (!empty($rnc)) ? $rnc : strtoupper(trim($nombre));

        $wsdlConn = $this->wdslConnection();

        $this->total_resultados = ($patronBusqueda == 1)?$this->wdslSearchCount($wsdlConn, $paramValue):1;
        if($this->total_resultados) {
            $this->offset = (!empty($offset)) ? $offset : 0;
            $this->wdslSearch($wsdlConn, $patronBusqueda, $paramValue, ($this->offset+1), (($this->offset)+50));
        }
    }

    public function paginas()
    {
        $url = $this->url() . "&tipo=buscar"
            . "&rnc=" . $this->rnc
            . "&nombre=" . $this->nombre;

        return $this->fbase_paginas($url, $this->total_resultados, $this->offset);
    }

    public function wdslConnection()
    {
        $client =  new \SoapClient('http://www.dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?WSDL', array('soap_version' => SOAP_1_2));
        return $client;
    }

    public function wdslSearch($wsdlConn, $patronBusqueda = 0, $paramValue = '', $inicioFilas, $filaFilas)
    {
        $result = $wsdlConn->__soapCall('GetContribuyentes', array('GetContribuyentes' => array('patronBusqueda'=>$patronBusqueda,'value'=>$paramValue, 'inicioFilas'=>$inicioFilas, 'filaFilas'=>$filaFilas, 'IMEI'=>0)));
        $list = array();
        $getResult = explode("@@@", $result->GetContribuyentesResult);
        foreach($getResult as $line) {
            $item = json_decode($line);
            $this->buscarCliente($item);
            $list[] = $item;
        }

        $this->resultados = $list;

    }

    public function wdslSearchCount($wsdlConn, $paramValue = '')
    {
        $result = $wsdlConn->__soapCall('GetContribuyentesCount', array('GetContribuyentesCount' => array('value'=>$paramValue, 'IMEI'=>0)));
        return $result->GetContribuyentesCountResult;
    }

    public function buscarCliente(&$item)
    {
        $item->existe = false;
        $item->codcliente = '';
        $cli = new cliente();
        if ($cliente = $cli->get_by_cifnif($item->RGE_RUC)) {
            $item->existe = true;
            $item->codcliente = $cliente->codcliente;
        }
    }

    private function cargar_config()
    {
        $fsvar = new fs_var();
        $this->nuevocli_setup = $fsvar->array_get(
                array(
            'nuevocli_cifnif_req' => 0,
            'nuevocli_direccion' => 1,
            'nuevocli_direccion_req' => 0,
            'nuevocli_codpostal' => 1,
            'nuevocli_codpostal_req' => 0,
            'nuevocli_pais' => 0,
            'nuevocli_pais_req' => 0,
            'nuevocli_provincia' => 1,
            'nuevocli_provincia_req' => 0,
            'nuevocli_ciudad' => 1,
            'nuevocli_ciudad_req' => 0,
            'nuevocli_telefono1' => 0,
            'nuevocli_telefono1_req' => 0,
            'nuevocli_telefono2' => 0,
            'nuevocli_telefono2_req' => 0,
            'nuevocli_email' => 0,
            'nuevocli_email_req' => 0,
            'nuevocli_codgrupo' => '',
            ), FALSE
        );
    }
}
