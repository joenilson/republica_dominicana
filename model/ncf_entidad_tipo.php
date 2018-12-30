<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
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

/**
 * Description of ncf_entidad_tipo
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_entidad_tipo extends fs_model
{
    /**
     *
     * @var integer
     */
    public $idempresa;
    /**
     *
     * @var varchar(6)
     */
    public $entidad;
    /**
     *
     * @var varchar(2)
     */
    public $tipo_entidad;
    /**
     *
     * @var varchar(2)
     */
    public $tipo_comprobante;
    /**
     *
     * @var boolean
     */
    public $estado;
    /**
     *
     * @var varchar(10)
     */
    public $usuario_creacion;
    /**
     *
     * @var string
     */
    public $fecha_creacion;
    /**
     *
     * @var varchar(10)
     */
    public $usuario_modificacion;
    /**
     *
     * @var string
     */
    public $fecha_modificacion;

    public function __construct($t = false)
    {
        parent::__construct('ncf_entidad_tipo', 'plugins/republica_dominicana/');
        if ($t) {
            $this->idempresa = $t['idempresa'];
            $this->entidad = $t['entidad'];
            $this->tipo_entidad = $t['tipo_entidad'];
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
            $this->estado = $this->str2bool($t['estado']);
        } else {
            $this->idempresa = null;
            $this->entidad = null;
            $this->tipo_entidad = null;
            $this->tipo_comprobante = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i:s');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
            $this->estado = false;
        }
    }

    protected function install()
    {
        return "";
    }

    public function exists()
    {
        if (is_null($this->idempresa) and is_null($this->entidad) and is_null($this->tipo_entidad)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ncf_entidad_tipo WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "entidad = ".$this->var2str($this->entidad)." AND ".
                    "tipo_entidad = ".$this->var2str($this->tipo_entidad).
                    ";");
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ncf_entidad_tipo SET ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion).", ".
                    "estado = ".$this->var2str($this->estado).
                    " WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                    "entidad = ".$this->var2str($this->entidad)." AND ".
                    "tipo_entidad = ".$this->var2str($this->tipo_entidad).";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ncf_entidad_tipo (idempresa, entidad, tipo_entidad,tipo_comprobante, estado, usuario_creacion, fecha_creacion ) VALUES ".
                    "(".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->entidad).", ".
                    $this->var2str($this->tipo_entidad).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).");";
            if ($this->db->exec($sql)) {
                $this->entidad = $this->entidad;
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        return $this->db->exec("UPDATE ncf_entidad_tipo SET estado = ".$this->var2str($this->estado)." WHERE ".
                "idempresa = ".$this->intval($this->idempresa)." AND ".
                "entidad = ".$this->var2str($this->entidad)." AND ".
                "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).";");
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_entidad_tipo where idempresa = ".$this->intval($idempresa)." ORDER BY tipo_comprobante,tipo_entidad,entidad");
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_entidad_tipo($d);
            }
        }

        return $lista;
    }

    public function get($idempresa, $entidad, $tipo_entidad)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_entidad_tipo WHERE tipo_entidad = ".$this->var2str($tipo_entidad)." AND ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "entidad = ".$this->var2str($entidad)." ORDER BY tipo_comprobante,tipo_entidad,entidad");

        if ($data) {
            return new ncf_entidad_tipo($data[0]);
        } else {
            return false;
        }
    }

    public function cliente($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_entidad_tipo WHERE tipo_entidad = 'CLI'  AND idempresa= ".$this->intval($idempresa).
                " ORDER BY tipo_comprobante,entidad");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_entidad_tipo($d);
            }
        }

        return $lista;
    }

    public function proveedor($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_entidad_tipo WHERE tipo_entidad = 'PRO' AND idempresa= ".$this->intval($idempresa).
                " ORDER BY tipo_comprobante,entidad");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_entidad_tipo($d);
            }
        }

        return $lista;
    }
}
