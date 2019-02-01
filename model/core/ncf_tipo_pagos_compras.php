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
 * Description of ncf_tipo_pagos_compras
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_tipo_pagos_compras extends \fs_model
{
    public $codigo;
    public $descripcion;
    public $estado;
    public $array_tipos = array(
        array ('codigo' => '1', 'descripcion' => 'Efectivo'),
        array ('codigo' => '2', 'descripcion' => 'Cheques/Transferencias/Depósito'),
        array ('codigo' => '3', 'descripcion' => 'Tarjeta Débito/Crédito'),
        array ('codigo' => '4', 'descripcion' => 'Compra a crédito'),
        array ('codigo' => '5', 'descripcion' => 'Permuta'),
        array ('codigo' => '6', 'descripcion' => 'Notas de crédito'),
        array ('codigo' => '7', 'descripcion' => 'Mixto')
    );
    
    public function __construct($t = false)
    {
        parent::__construct('ncf_tipo_pagos_compras', 'plugins/republica_dominicana/');
        if ($t) {
            $this->codigo = $t['codigo'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
        } else {
            $this->codigo = null;
            $this->descripcion = '';
            $this->estado = false;
        }
    }

    protected function install()
    {
        return "INSERT INTO ".$this->table_name." (codigo, descripcion, estado) VALUES ".
            "('1','Efectivo',true),
            ('2','Cheques/Transferencias/Depósito',true),
            ('3','Tarjeta Débito/Crédito',true),
            ('4','Compra a crédito',true),
            ('5','Permuta',true),
            ('6','Notas de crédito',true),
            ('7','Mixto',true);";
    }

    public function exists()
    {
        if (is_null($this->codigo)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($this->codigo).";");
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ".$this->table_name." SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "estado = ".$this->var2str($this->estado)." WHERE codigo = ".$this->var2str($this->codigo).";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ".$this->table_name." (codigo, descripcion, estado) VALUES ".
                    "(".$this->var2str($this->codigo).", ".$this->var2str($this->descripcion).", ".$this->var2str($this->estado).");";
            if ($this->db->exec($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        return $this->db->exec("UPDATE ".$this->table_name." SET estado = ".$this->var2str($this->estado)." WHERE codigo = ".$this->var2str($this->codigo).";");
    }

    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codigo,descripcion");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_tipo_pagos($d);
            }
        }

        return $lista;
    }
    
    public function all_activos()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE estado = TRUE ORDER BY codigo,descripcion");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_tipo_pagos($d);
            }
        }

        return $lista;
    }

    public function get($codigo)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo).";");

        return new ncf_tipo_pagos($data[0]);
    }
    
    public function get_descripcion($codigo)
    {
        $data = $this->db->select("SELECT descripcion FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo).";");

        return $data[0]['descripcion'];
    }
        
    public function restore_names()
    {
        $sqlClean = "DELETE FROM ".$this->table_name." WHERE codigo=''";
        $this->db->exec($sqlClean);
        $counter = 0;
        foreach($this->array_tipos as $tipo) {
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($tipo['descripcion']).
                    " WHERE codigo = ".$this->var2str($tipo['codigo']);
            if($this->db->exec($sql)) {
               $counter ++; 
            }
        }
        return $counter;  
    }
}
