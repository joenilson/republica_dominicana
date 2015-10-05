<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * Description of ncf_rango
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_rango extends fs_model
{
    public $solicitud;
    public $codalmacen;
    public $serie;
    public $division;
    public $punto_emision;
    public $area_impresion;
    public $tipo_comprobante;
    public $secuencia_inicio;
    public $secuencia_fin;
    public $correlativo;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $estado;
    
    public function __construct($t = false) {
        parent::__construct('ncf_rango', 'plugins/republica_dominicana/');
        if($t)
        {
            $this->solicitud = $t['solicitud'];
            $this->codalmacen = $t['codalmacen'];
            $this->serie = $t['serie'];
            $this->division = $t['division'];
            $this->punto_emision = $t['punto_emision'];
            $this->area_impresion = $t['area_impresion'];
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->secuencia_inicio = $t['secuencia_inicio'];
            $this->secuencia_fin = $t['secuencia_fin'];
            $this->correlativo = $t['correlativo'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
            $this->estado = ($t['estado']);
        }
        else
        {
            $this->solicitud = null;
            $this->codalmacen = null;
            $this->serie = null;
            $this->division = null;
            $this->punto_emision = null;
            $this->area_impresion = null;
            $this->tipo_comprobante = null;
            $this->secuencia_inicio = null;
            $this->secuencia_fin = null;
            $this->correlativo = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = Date('d-m-Y H:i');
            $this->estado = false;
        }
    }
    
    protected function install() {
        /*
         * se puede insertar datos en formato SQL
         */
        return '';
    }
    
    public function exists() {
        if(is_null($this->solicitud) AND is_null($this->codalmacen) AND is_null($this->serie) AND is_null($this->division) AND is_null($this->punto_emision) AND is_null($this->area_impresion) AND is_null($this->tipo_comprobante))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM ncf_rango WHERE ".
                    "solicitud = ".$this->intval($this->solicitud)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "serie= ".$this->var2str($this->serie)." AND ".
                    "division= ".$this->var2str($this->division)." AND ".
                    "punto_emision = ".$this->var2str($this->punto_emision)." AND ".
                    "area_impresion = ".$this->var2str($this->area_impresion)." AND ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
        }
    }

    public function get($solicitud,$codalmacen,$serie,$division,$punto_emision,$area_impresion,$tipo_comprobante)
    {
        $data = $this->db->select("SELECT * FROM ncf_rango WHERE ".
                    "solicitud = ".$this->intval($solicitud)." AND ".
                    "codalmacen = ".$this->var2str($codalmacen)." AND ".
                    "serie= ".$this->var2str($serie)." AND ".
                    "division= ".$this->var2str($division)." AND ".
                    "punto_emision = ".$this->var2str($punto_emision)." AND ".
                    "area_impresion = ".$this->var2str($area_impresion)." AND ".
                    "tipo_comprobante = ".$this->var2str($tipo_comprobante).";");
        if($data)
        {
            return new ncf_rango($data[0]);
        }
        else
        {
            return false;
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE ncf_rango SET ".
                    "solicitud = ".$this->intval($this->solicitud).", ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "serie= ".$this->var2str($this->serie).", ".
                    "division= ".$this->var2str($this->division).", ".
                    "punto_emision = ".$this->var2str($this->punto_emision).", ".
                    "area_impresion = ".$this->var2str($this->area_impresion).", ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).", ".
                    "secuencia_inicio = ".$this->intval($this->secuencia_inicio).", ".
                    "secuencia_fin = ".$this->intval($this->secuencia_fin).", ".
                    "correlativo = ".$this->intval($this->correlativo).", ".
                    "estado = ".$this->str2bool($this->estado).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "solicitud = ".$this->intval($this->solicitud)." AND ".
                    "codalmacen = ".$this->var2str($this->codalmacen)." AND ".
                    "serie= ".$this->var2str($this->serie)." AND ".
                    "division= ".$this->var2str($this->division)." AND ".
                    "punto_emision = ".$this->var2str($this->punto_emision)." AND ".
                    "area_impresion = ".$this->var2str($this->area_impresion)." AND ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";";
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO ncf_rango (solicitud,  codalmacen, serie, division, punto_emision, area_impresion, tipo_comprobante, secuencia_inicio, secuencia_fin, correlativo, estado, usuario_creacion, fecha_creacion ) ".
                    "VALUES ".
                    "(".$this->intval($this->solicitud).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->serie).", ".
                    $this->var2str($this->division).", ".
                    $this->var2str($this->punto_emision).", ".
                    $this->var2str($this->area_impresion).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    $this->intval($this->secuencia_inicio).", ".
                    $this->intval($this->secuencia_fin).", ".
                    $this->var2str($this->correlativo).", ".
                    ($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).
                    ")";
            if($this->db->exec($sql))
            {
                $this->solicitud = $this->solicitud;
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() 
    {
        return $this->db->exec("DELETE FROM ncf_rango where solicitud = ".$this->var2str($this->solicitud).";");
    }
    
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_rango ORDER BY codalmacen,solicitud");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new ncf_rango($d);
            }
                
        }
        return $lista;
    }
    
    public function generate($codalmacen, $tipo_comprobante)
    {
        $lista = array();
        $data = $this->db->select("SELECT ".
        "solicitud, codalmacen, serie, division, punto_emision, area_impresion, tipo_comprobante, ".
        "secuencia_inicio, secuencia_fin, correlativo ".
        "WHERE ".
        "codalmacen = ".$this->var2str($codalmacen)." AND ".
        "tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND estado = true ;");
        
        return $this->ncf_number($data);
        
    }
    
    protected function ncf_number($data){
        $ncf_number = "";
        $solicitud = new ncf_rango($data);
        $rango = $solicitud->serie.$solicitud->division.$solicitud->punto_emision.$solicitud->area_impresion.$solicitud->tipo_comprobante;
        $correlativo = str_pad($solicitud->correlativo+1,8,'0',STR_PAD_LEFT);
        $ncf_number = $rango.$correlativo;
        return $ncf_number;
    }
}
