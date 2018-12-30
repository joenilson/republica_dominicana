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
 * Description of ncf_tipo_ingresos
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_tipo_ingresos extends \fs_model
{
    public $codigo;
    public $descripcion;
    public $estado;

    public function __construct($t = false)
    {
        parent::__construct('ncf_tipo_ingresos', 'plugins/republica_dominicana/');
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
            "('1','Ingresos por operaciones (No financieros)',true),
            ('2','Ingresos Financieros',true),
            ('3','Ingresos Extraordinarios',true),
            ('4','Ingresos por Arrendamientos',true),
            ('5','Ingresos por Venta de Activo Depreciable',true),
            ('6','Otros Ingresos',true);";
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
                $lista[] = new ncf_tipo_compras($d);
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
                $lista[] = new ncf_tipo_compras($d);
            }
        }

        return $lista;
    }

    public function get($codigo)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo).";");

        return new ncf_tipo_compras($data[0]);
    }
    
    public function get_descripcion($codigo)
    {
        $data = $this->db->select("SELECT descripcion FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo).";");

        return $data[0]['descripcion'];
    }
}
