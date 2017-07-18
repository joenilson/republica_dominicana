<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of functions
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
if(!function_exists('fs_tipos_id_fiscal'))
{
   /**
    * Devuelve la lista de identificadores fiscales.
    * @return type
    */
   function fs_tipos_id_fiscal()
   {
      return array(FS_CIFNIF,'Pasaporte','Cédula','RNC');
   }
}

/**
 * Se debe migrar aquí la funcionalidad de generar el ncf para insertarlo en 
 * el campo numero2
 * @return string
 */
function generar_numero2(){
    return '';
}

/**
 * 
 * @param type $cliente
 * @param type $tipo_comprobante
 * @param type $terminal
 * @param type $json
 * @return type array
 */
function generar_comprobante_fiscal($cliente,$tipo_comprobante,$codalmacen,$terminal=false, $json = false) {
    require_model('ncf_rango.php');
    $ncf_numero = array();
    $ncf_rango = new ncf_rango();
    if($terminal){
        $numero_ncf = $ncf_rango->generate_terminal($this->empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago, $terminal->area_impresion);
    }else{
        $numero_ncf = $ncf_rango->generate($this->empresa->id, $codalmacen, $tipo_comprobante, $cliente->codpago);
    }
    
    if ($numero_ncf['NCF'] !== 'NO_DISPONIBLE') {
        $ncf_numero = $numero_ncf['NCF'];
    }
    
    if($json){
        header('Content-Type: application/json');
        echo json_encode(array('ncf_numero' => $this->ncf_numero, 'tipo_comprobante' => $tipo_comprobante, 'terminal' => $this->terminal, 'cliente' => $this->cliente_s));
    }else{
        return $numero_ncf;
    }
}