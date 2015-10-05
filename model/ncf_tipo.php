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
 * Description of ncf_tipo
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_tipo extends fs_model
{
    public $tipo_comprobante;
    public $descripcion;
    public $estado;
           
    public function __construct($t = false) {
        parent::__construct('ncf_tipo','plugins/ncf/');
        if($t)
        {
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
        }
        else
        {
            $this->tipo_comprobante = null;
            $this->descripcion = '';
            $this->estado = false;
        }
    }
    
    protected function install() {
        return "INSERT INTO ncf_tipo (tipo_comprobante, descripcion, estado) VALUES ".
            "('01','FACTURAS QUE GENERAN CREDITOS Y/O SUSTENTAN GASTOS Y COSTOS',true),".
            "('02','FACTURAS A CONSUMIDORES FINALES SIN VALOR DE CREDITO FISCAL',true),".
            "('03','NOTAS DE DEBITO',true),('04','NOTAS DE CREDITO',true),".
            "('11','REGISTROS DE PROVEEDORES INFORMALES',true),".
            "('12','REGISTRO UNICO DE INGRESOS',true),('13','REGISTRO DE GASTOS MENORES',true),".
            "('14','REGISTRO DE OPERACIONES PARA EMPRESAS ACOGIDAS A REGIMENES ESPECIALES DE TRIBUTACION',true),".
            "('15','COMPROBANTES GUBERNAMENTALES',true);";
    }
    
    public function exists() {
        if(is_null($this->tipo_comprobante))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM ncf_tipo WHERE tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
        }
    }
    
    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE ncf_tipo SET ".
                    "descripcion = ".$this->var2str($this->descripcion.", ".
                    "estado = ".$this->str2bool($this->estado)." WHERE tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
            
            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO ncf_tipo (tipo_comprobante, descripcion, estado) VALUES ".
                    "(".$this->var2str($this->tipo_comprobante).", ".$this->var2str($this->descripcion).", ".$this->var2str($this->estado).");";
            if($this->db->exec($sql))
            {
                $this->solicitud = $this->db->lastval();
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete() {
        return $this->db->exec("UPDATE ncf_tipo SET estado = ".$this->var2str($this->estado)." WHERE tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
    }
    
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_tipo ORDER BY tipo_comprobante,descripcion");
        
        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new ncf_tipo($d);
            }
                
        }
        
        return $lista;
    }
    
}
