<?php
/*
 * Copyright (C) 2018 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Lesser General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\model;
/**
 * Description of ncf_tipo
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_tipo extends \fs_model
{
    public $tipo_comprobante;
    public $descripcion;
    public $estado;
    public $clase_movimiento;
    public $ventas;
    public $compras;
    public $contribuyente;
    public $array_comprobantes = array(
        array ('tipo' => '01', 'descripcion' => 'FACTURA DE CREDITO FISCAL', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>'X'),
        array ('tipo' => '02', 'descripcion' => 'FACTURA DE CONSUMO', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'', 'contribuyente'=>'X'),
        array ('tipo' => '03', 'descripcion' => 'NOTA DE DEBITO', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>''),
        array ('tipo' => '04', 'descripcion' => 'NOTA DE CREDITO', 'clase_movimiento'=>'resta', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>''),
        array ('tipo' => '11', 'descripcion' => 'COMPROBANTE DE COMPRAS', 'clase_movimiento'=>'suma', 'ventas'=>'', 'compras'=>'X', 'contribuyente'=>'X'),
        array ('tipo' => '12', 'descripcion' => 'REGISTRO UNICO DE INGRESOS', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'', 'contribuyente'=>''),
        array ('tipo' => '13', 'descripcion' => 'COMPROBANTE PARA GASTOS MENORES', 'clase_movimiento'=>'suma', 'ventas'=>'', 'compras'=>'X', 'contribuyente'=>''),
        array ('tipo' => '14', 'descripcion' => 'COMPROBANTE DE REGIMENES ESPECIALES', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>'X'),
        array ('tipo' => '15', 'descripcion' => 'COMPROBANTE GUBERNAMENTAL', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>'X'),
        array ('tipo' => '16', 'descripcion' => 'COMPROBANTE PARA EXPORTACIONES', 'clase_movimiento'=>'suma', 'ventas'=>'X', 'compras'=>'X', 'contribuyente'=>'X'),
        array ('tipo' => '17', 'descripcion' => 'COMPROBANTE PARA PAGOS AL EXTERIOR', 'clase_movimiento'=>'suma', 'ventas'=>'', 'compras'=>'X', 'contribuyente'=>'X'),
    );
    public function __construct($t = false)
    {
        parent::__construct('ncf_tipo', 'plugins/republica_dominicana/');
        if ($t) {
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
            $this->clase_movimiento = $t['clase_movimiento'];
            $this->ventas = $t['ventas'];
            $this->compras = $t['compras'];
            $this->contribuyente = $t['contribuyente'];
        } else {
            $this->tipo_comprobante = null;
            $this->descripcion = '';
            $this->estado = false;
            $this->clase_movimiento = null;
            $this->ventas = null;
            $this->compras = null;
            $this->contribuyente = null;
        }
    }
    
    protected function install()
    {
        return "INSERT INTO ncf_tipo (tipo_comprobante, descripcion, estado, clase_movimiento, ventas, compras, contribuyente ) VALUES ".
            "('01','FACTURA DE CREDITO FISCAL',TRUE, 'suma','X','X','X'),".
            "('02','FACTURA DE CONSUMO',TRUE, 'suma','X',null,'X'),".
            "('03','NOTA DE DEBITO',true, 'suma','X','X',null),".
            "('04','NOTA DE CREDITO',TRUE, 'resta','X','X',null),".
            "('11','COMPROBANTE DE COMPRAS',TRUE, 'suma',null,'X','X'),".
            "('12','REGISTRO UNICO DE INGRESOS',TRUE, 'suma','X',null,null),".
            "('13','COMPROBANTE PARA GASTOS MENORES',TRUE, 'suma',null,'X',null),".
            "('14','COMPROBANTE DE REGIMENES ESPECIALES',TRUE, 'suma','X','X','X'),".
            "('15','COMPROBANTE GUBERNAMENTAL',TRUE, 'suma','X','X','X'),".
            "('16','COMPROBANTE PARA EXPORTACIONES',TRUE, 'suma','X','X','X'),".
            "('17','COMPROBANTE PARA PAGOS AL EXTERIOR',TRUE, 'suma',null, 'X','X');";
    }
    
    public function exists()
    {
        $existe = $this->get($this->tipo_comprobante);
        if (!$existe) {
            return false;
        } else {
            return $this->get($this->tipo_comprobante);
        }
    }
    
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ncf_tipo SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "clase_movimiento = ".$this->var2str($this->clase_movimiento).", ".
                    "ventas = ".$this->var2str($this->ventas).", ".
                    "compras = ".$this->var2str($this->compras).", ".
                    "contribuyente = ".$this->var2str($this->contribuyente).", ".
                    "estado = ".$this->var2str($this->estado)." "
                    ." WHERE tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";";
            
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ncf_tipo (tipo_comprobante, descripcion, estado, clase_movimiento, ventas, compras, contribuyente) VALUES ".
                    "(".$this->var2str($this->tipo_comprobante).", "
                    .$this->var2str($this->descripcion).", "
                    .$this->var2str($this->estado).", "
                    .$this->var2str($this->clase_movimiento).", "
                    .$this->var2str($this->ventas).", "
                    .$this->var2str($this->compras).", "
                    .$this->var2str($this->contribuyente).");";
            if ($this->db->exec($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    public function delete()
    {
        $sql = "DELETE FROM ".$this->table_name." WHERE tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";";
        return $this->db->exec($sql);
    }
    
    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_tipo ORDER BY tipo_comprobante");
        
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_tipo($d);
            }
        }
        
        return $lista;
    }
    
    public function cliente()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_tipo WHERE contribuyente = 'X' and ventas = 'X' ORDER BY tipo_comprobante,descripcion");
        
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_tipo($d);
            }
        }
        
        return $lista;
    }
    
    public function proveedor()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_tipo WHERE contribuyente = 'X' and compras = 'X' ORDER BY tipo_comprobante,descripcion");
        
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_tipo($d);
            }
        }
        
        return $lista;
    }
    
    public function get($tipo_comprobante)
    {
        $data = $this->db->select("SELECT * FROM ncf_tipo WHERE tipo_comprobante = ".$this->var2str($tipo_comprobante).";");
        if ($data) {
            return new ncf_tipo($data[0]);
        } else {
            return false;
        }
    }
    
    public function get_descripcion($codigo)
    {
        $data = $this->db->select("SELECT descripcion FROM ".$this->table_name." WHERE tipo_comprobante = ".$this->var2str($codigo).";");

        return $data[0]['descripcion'];
    }
    
    public function restore_names()
    {
        $sqlClean = "DELETE FROM ".$this->table_name." WHERE tipo_comprobante=''";
        $this->db->exec($sqlClean);
        $counter = 0;
        foreach($this->array_comprobantes as $comprobante) {
            $this->restore_name($comprobante, $counter);
        }
        return $counter;
        
    }
    
    public function restore_name($comprobante, &$counter)
    {
        if($this->get($comprobante['tipo'])) {
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($comprobante['descripcion']).", "." ESTADO = TRUE ".", ".
                    " clase_movimiento = ".$this->var2str($comprobante['clase_movimiento']).", ".
                    " ventas = ".$this->var2str($comprobante['ventas']).", ".
                    " compras = ".$this->var2str($comprobante['compras']).", ".
                    " contribuyente = ".$this->var2str($comprobante['contribuyente'])." ". 
                    " WHERE tipo_comprobante = ".$this->var2str($comprobante['tipo']);
        } else {
            $sql = "INSERT INTO ".$this->table_name
                ." (tipo_comprobante, descripcion, estado, clase_movimiento, ventas, compras, contribuyente ) VALUES ".
                "('".$comprobante['tipo']."','".
                    $comprobante['descripcion']."',TRUE, '".
                    $comprobante['clase_movimiento']."','".
                    $comprobante['ventas']."','".
                    $comprobante['compras']."','".
                    $comprobante['contribuyente']."');";
        }
        if($this->db->exec($sql)) {
            $counter ++;
         }
    }
}
