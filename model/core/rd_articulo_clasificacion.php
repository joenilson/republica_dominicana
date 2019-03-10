<?php

/*
 * Copyright (C) 2019 Joe Nilson <joenilson@gmail.com>
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
 * Description of rd_articulo_clasificacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class rd_articulo_clasificacion extends \fs_model
{
    /**
     *
     * @var integer
     */
    public $id;
    /**
     *
     * @var string
     */
    public $referencia;
    /**
     *
     * @var string
     */
    public $tipo_articulo;

    /**
     *
     * @var array
     */
    public $tipos = array(
        array('codigo'=>'01','descripcion'=>'Bien'),
        array('codigo'=>'02','descripcion'=>'Servicio'),
    );
    
    public function __construct($t = FALSE)
    {
        parent::__construct('rd_articulo_clasificacion', 'plugins/republica_dominicana/');
        if ($t) {
            $this->id = $t['id'];
            $this->referencia = $t['referencia'];
            $this->tipo_articulo = $t['tipo_articulo'];
        } else {
            $this->id = null;
            $this->referencia = null;
            $this->tipo_articulo = '';
        }
    }

    /**
     * 
     * @return string
     */
    protected function install()
    {
        return "";
    }

    /**
     * 
     * @return boolean|object
     */
    public function exists()
    {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($this->id).";");
        }
    }

    /**
     * 
     * @return boolean
     */
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ".$this->table_name." SET ".
                    " referencia = ".$this->var2str($this->referencia).
                    ", tipo_articulo = ".$this->var2str($this->tipo_articulo).
                    " WHERE id = ".$this->intval($this->id);
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ".$this->table_name." (referencia, tipo_articulo) VALUES ".
                "(".$this->var2str($this->referencia).", ".$this->var2str($this->tipo_articulo).");";
            if ($this->db->exec($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 
     * @return boolean
     */
    public function delete()
    {
        return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->id).";");
    }

    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY tipo_articulo, referencia;");

        if ($data) {
            foreach ($data as $d) {
                $item = new rd_articulo_clasificacion($d);
                $this->info_formapago($item);
                $lista[] = $item;
            }
        }

        return $lista;
    }

    /**
     * Get the info for a referencia param
     * @param string $referencia
     * @return \FacturaScripts\model\rd_articulo_clasificacion
     */
    public function get_referencia($referencia)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($referencia).";");
        $item = false;
        if($data) {
            $item = new rd_articulo_clasificacion($data[0]);
            $this->info_articulo($item);
        }
        return $item;
    }
    
    /**
     * Get the list of articulos for a given tipo_articulo
     * @param string $tipo_articulo
     * @return \FacturaScripts\model\rd_articulo_clasificacion
     */
    public function get_articulos_tipo($tipo_articulo)
    {
        $sql = "SELECT * FROM articulos ".
               " WHERE referencia IN (select referencia from ".$this->table_name." WHERE tipo_articulo = ".$this->var2str($tipo_articulo).") ".
               " ORDER BY referencia,bloqueado;";
        $data = $this->db->select($sql);
        $lista = [];
        if($data) {
            foreach($data as $d) {
                $item = new articulo($d);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    public function get_articulos_sin_clasificar()
    {
        $sql = "SELECT * FROM articulos WHERE referencia NOT IN (select referencia from ".$this->table_name.") order by referencia,bloqueado;";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $item = new articulo($d);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    private function info_articulo(&$data)
    {
        $articulo = new \articulo();
        $fp = $articulo->get($data->referencia);
        $data->descripcion_articulo = $fp->descripcion;
        $desc_tipo = array_search($data->tipo_articulo, $this->tipos);
        $data->descripcion_tipo = $desc_tipo;
    }
}
