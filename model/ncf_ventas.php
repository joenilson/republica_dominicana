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
 * Description of ncf_ventas
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_ventas extends fs_model {
    public $idempresa;
    public $codalmacen;
    public $entidad;
    public $cifnif;
    public $documento;
    public $documento_modifica;
    public $fecha;
    public $tipo_comprobante;
    public $ncf;
    public $ncf_modifica;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    
    public function __construct($t = false) {
        parent::__construct('ncf_ventas','plugins/republica_dominicana/');
        if($t)
        {
            $this->idempresa = $t['idempresa'];
            $this->codalmacen = $t['codalmacen'];
            $this->entidad = $t['entidad'];
            $this->cifnif = $t['cifnif'];
            $this->documento = $t['documento'];
            $this->documento_modifica = $t['documento_modifica'];
            $this->fecha = $t['fecha'];
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->ncf = $t['ncf'];
            $this->ncf_modifica = $t['ncf_modifica'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i');
        }
        else
        {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->entidad = null;
            $this->cifnif = null;
            $this->documento = null;
            $this->documento_modifica = null;
            $this->fecha = Date('d-m-Y');
            $this->tipo_comprobante = null;
            $this->ncf = null;
            $this->ncf_modifica = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
        }
    }
    
    protected function install() {
        return "";
    }
    
    public function exists() {
        if(is_null($this->idempresa) AND is_null($this->ncf))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ncf = ".$this->var2str($this->ncf).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            if(!is_null($this->ncf_modifica)){
                $sql = "UPDATE ncf_ventas SET ".
                        "documento_modifica = ".$this->var2str($this->documento).", ".
                        "ncf_modifica = ".$this->var2str($this->ncf_modifica).", ".
                        "cifnif = ".$this->var2str($this->cifnif).
                        " WHERE ".
                        "idempresa = ".$this->intval($this->idempresa)." AND ".
                        "ncf = ".$this->var2str($this->ncf)." AND ".
                        "documento = ".$this->var2str($this->documento)." AND ".
                        "fecha = ".$this->var2str($this->fecha).";";

                return $this->db->exec($sql);
            }else{
                return false;
            }
        }
        else
        {
            $sql = "INSERT INTO ncf_ventas (idempresa, codalmacen, entidad, cifnif, documento, fecha, tipo_comprobante, ncf, ncf_modifica, usuario_creacion, fecha_creacion ) VALUES ".
                    "(".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->entidad).", ".
                    $this->var2str($this->cifnif).", ".
                    $this->var2str($this->documento).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    $this->var2str($this->ncf).", ".
                    $this->var2str($this->ncf_modifica).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if($this->db->exec($sql))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        return $this->db->exec("DELETE FROM ncf_ventas WHERE idempresa = ".$this->intval($this->idempresa)." ncf = ".$this->var2str($this->ncf)." AND fecha = ".$this->var2str($this->fecha).";");
    }
    
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas ORDER BY idempresa, ncf, fecha");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new ncf_entidad_tipo($d);
            }
                
        }
        
        return $lista;
    }
    
    public function get($idempresa, $ncf)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa).", ".
                "ncf = ".$this->var2str($ncf)." ".
                "ORDER BY idempresa, ncf, fecha");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new ncf_ventas($d);
            }
                
        }
        
        return $lista;
    }
}
