<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson@gmail.com>
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
 * Description of ncf_tipo_anulacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_tipo_anulacion extends fs_model
{
    public $codigo;
    public $descripcion;
    public $estado;

    public function __construct($t = false) {
        parent::__construct('ncf_tipo_anulacion','plugins/republica_dominicana/');
        if($t)
        {
            $this->codigo = $t['codigo'];
            $this->descripcion = $t['descripcion'];
            $this->estado = $this->str2bool($t['estado']);
        }
        else
        {
            $this->codigo = null;
            $this->descripcion = '';
            $this->estado = false;
        }
    }

    protected function install() {
        return "INSERT INTO ncf_tipo_anulacion (codigo, descripcion, estado) VALUES ".
            "('01','Deterioro de Factura Pre-Imprensa',true),
            ('02','Errores de Impresión (Factura Pre-Impresa)',true),
            ('03','Impresión defectuosa',true),
            ('04','Duplicidad de Factura',true),
            ('05','Corrección de la Información',true),
            ('06','Cambio de Productos',true),
            ('07','Devolución de Productos',true),
            ('08','Omisión de Productos',true);";
    }

    public function exists() {
        if(is_null($this->codigo))
        {
            return false;
        }
        else
        {
            return $this->db->select("SELECT * FROM ncf_tipo_anulacion WHERE codigo = ".$this->var2str($this->codigo).";");
        }
    }

    public function save() {
        if ($this->exists())
        {
            $sql = "UPDATE ncf_tipo_anulacion SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "estado = ".$this->var2str($this->estado)." WHERE codigo = ".$this->var2str($this->codigo).";";

            return $this->db->exec($sql);
        }
        else
        {
            $sql = "INSERT INTO ncf_tipo_anulacion (codigo, descripcion, estado) VALUES ".
                    "(".$this->var2str($this->codigo).", ".$this->var2str($this->descripcion).", ".$this->var2str($this->estado).");";
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
        return $this->db->exec("UPDATE ncf_tipo_anulacion SET estado = ".$this->var2str($this->estado)." WHERE codigo = ".$this->var2str($this->codigo).";");
    }

    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_tipo_anulacion ORDER BY codigo,descripcion");

        if($data)
        {
            foreach($data as $d)
            {
                $lista[] = new ncf_tipo_anulacion($d);
            }

        }

        return $lista;
    }

    public function get($codigo)
    {
        $data = $this->db->select("SELECT * FROM ncf_tipo_anulacion WHERE codigo = ".$this->var2str($codigo).";");

        return new ncf_tipo_anulacion($data[0]);
    }

}
