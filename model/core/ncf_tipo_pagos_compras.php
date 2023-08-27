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
        array ('codigo' => '01', 'descripcion' => 'EFECTIVO'),
        array ('codigo' => '02', 'descripcion' => 'CHEQUES/TRANSFERENCIAS/DEPOSITO'),
        array ('codigo' => '03', 'descripcion' => 'TARJETA CRÉDITO/DÉBITO'),
        array ('codigo' => '04', 'descripcion' => 'COMPRA A CREDITO'),
        array ('codigo' => '05', 'descripcion' => 'PERMUTA'),
        array ('codigo' => '06', 'descripcion' => 'NOTA DE CREDITO'),
        array ('codigo' => '07', 'descripcion' => 'MIXTO')
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
            "('01','EFECTIVO',true),
            ('02','CHEQUES/TRANSFERENCIAS/DEPOSITO',true),
            ('03','TARJETA CRÉDITO/DÉBITO',true),
            ('04','COMPRA A CREDITO',true),
            ('05','PERMUTA',true),
            ('06','NOTA DE CREDITO',true),
            ('07','MIXTO',true);";
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
        $sqlClean = "DELETE FROM ".$this->table_name;
        $this->db->exec($sqlClean);
        $counter = 0;
        foreach ($this->array_tipos as $tipo) {
            $this->agregarRegistro($tipo);
            $counter ++;
        }
        return $counter;
    }
    
    public function agregarRegistro($tipo)
    {
        $ntpc = new ncf_tipo_pagos_compras();
        $ntpc->codigo = $tipo['codigo'];
        $ntpc->descripcion = $tipo['descripcion'];
        $ntpc->estado = true;
        $ntpc->save();
    }
}
