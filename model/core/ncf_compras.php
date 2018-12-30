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
 * Description of ncf_compras
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_compras extends \fs_model
{
    public $idempresa;
    public $codalmacen;
    public $entidad;
    public $cifnif;
    public $documento;
    public $documento_modifica;
    public $fecha;
    public $tipo_comprobante;
    public $tipo_compra;
    public $total_bienes;
    public $total_servicios;
    public $ncf;
    public $ncf_modifica;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $estado;
    public $motivo;

    public $ncf_tipo;
    public $ncf_tipo_compras;
    public $factura_proveedor;
    public function __construct($data = false)
    {
        parent::__construct('ncf_compras', 'plugins/republica_dominicana/');
        
        if ($data) {
            $this->idempresa = $data['idempresa'];
            $this->codalmacen = $data['codalmacen'];
            $this->entidad = $data['entidad'];
            $this->cifnif = $data['cifnif'];
            $this->documento = $data['documento'];
            $this->documento_modifica = $data['documento_modifica'];
            $this->fecha = $data['fecha'];
            $this->tipo_comprobante = $data['tipo_comprobante'];
            $this->tipo_compra = $data['tipo_compra'];
            $this->ncf = $data['ncf'];
            $this->ncf_modifica = $data['ncf_modifica'];
            $this->usuario_creacion = $data['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($data['fecha_creacion']));
            $this->usuario_modificacion = $data['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
            $this->estado = $this->str2bool($data['estado']);
            $this->motivo = $data['motivo'];
            $this->total_servicios = $data['total_servicios'];
            $this->total_bienes = $data['total_bienes'];
        } else {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->entidad = null;
            $this->cifnif = null;
            $this->documento = null;
            $this->documento_modifica = null;
            $this->fecha = Date('d-m-Y');
            $this->tipo_comprobante = null;
            $this->tipo_compra = null;
            $this->ncf = null;
            $this->ncf_modifica = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i:s');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
            $this->estado = true;
            $this->motivo = null;
            $this->total_servicios = 0;
            $this->total_bienes = 0;
        }

        $this->ncf_tipo = new \ncf_tipo();
        $this->ncf_tipo_compras = new \ncf_tipo_compras();
        $this->factura_proveedor = new \factura_proveedor();
    }

    protected function install()
    {
        return "";
    }

    public function exists()
    {
        if (is_null($this->idempresa) and is_null($this->ncf) and is_null($this->entidad)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ncf = ".$this->var2str($this->ncf).";");
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ".$this->table_name." SET ".
            "cifnif = ".$this->var2str($this->cifnif).", ".
            "documento = ".$this->intval($this->documento).", ".
            "documento_modifica = ".$this->var2str($this->documento_modifica).", ".
            "fecha = ".$this->var2str($this->fecha).", ".
            "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).", ".
            "tipo_compra = ".$this->var2str($this->tipo_compra).", ".
            "total_bienes = ".$this->var2str($this->total_bienes).", ".
            "total_servicios = ".$this->var2str($this->total_servicios).", ".
            "ncf_modifica = ".$this->var2str($this->ncf_modifica).", ".
            "estado = ".$this->var2str($this->estado).", ".
            "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
            "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
            "WHERE ".
            "ncf = ".$this->var2str($this->ncf). " AND ".
            "entidad = ".$this->var2str($this->entidad). " AND ".
            "idempresa = ".$this->intval($this->idempresa). " AND ".
            "codalmacen = ".$this->var2str($this->codalmacen). "; ";
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ".$this->table_name." (idempresa, codalmacen, entidad, cifnif, documento, documento_modifica, fecha, tipo_comprobante, tipo_compra, total_bienes, total_servicios, ncf, ncf_modifica, estado, usuario_creacion, fecha_creacion ) VALUES ".
                "(".
                $this->intval($this->idempresa).", ".
                $this->var2str($this->codalmacen).", ".
                $this->var2str($this->entidad).", ".
                $this->var2str($this->cifnif).", ".
                $this->intval($this->documento).", ".
                $this->var2str($this->documento_modifica).", ".
                $this->var2str($this->fecha).", ".
                $this->var2str($this->tipo_comprobante).", ".
                $this->var2str($this->tipo_compra).", ".
                $this->var2str($this->total_bienes).", ".
                $this->var2str($this->total_servicios).", ".
                $this->var2str($this->ncf).", ".
                $this->var2str($this->ncf_modifica).", ".
                $this->var2str($this->estado).", ".
                $this->var2str($this->usuario_creacion).", ".
                $this->var2str($this->fecha_creacion).");";
            if ($this->db->exec($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function anular()
    {
        $sql = "UPDATE ".$this->table_name." SET ".
                "estado = false, motivo = ".$this->var2str($this->motivo).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                "WHERE ".
                "ncf = ".$this->var2str($this->ncf). " AND ".
                "entidad = ".$this->var2str($this->entidad). " AND ".
                "idempresa = ".$this->intval($this->idempresa). " AND ".
                "codalmacen = ".$this->var2str($this->codalmacen). "; ";
        if ($this->db->exec($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function corregir_fecha()
    {
        $sql = "UPDATE ".$this->table_name." SET ".
                "fecha = ".$this->var2str($this->fecha).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                "WHERE ".
                "ncf = ".$this->var2str($this->ncf). " AND ".
                "entidad = ".$this->var2str($this->entidad). " AND ".
                "idempresa = ".$this->intval($this->idempresa). " AND ".
                "codalmacen = ".$this->var2str($this->codalmacen). "; ";
        if ($this->db->exec($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idempresa = ".$this->intval($this->idempresa)." AND ncf = ".$this->var2str($this->ncf)." AND fecha = ".$this->var2str($this->fecha).";");
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_compras($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }

        return $lista;
    }

    /**
     * Return NCF information
     * @param integer $idempresa
     * @param string $ncf
     * @return \ncf_compras
     */
    public function get($idempresa, $entidad, $ncf)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "entidad = ".$this->var2str($entidad)." AND ".
                "ncf = ".$this->var2str($ncf)." ".
                "ORDER BY idempresa, entidad, ncf");
        if ($data) {
            $datos = new ncf_compras($data[0]);
            $this->info_factura($datos);
        }
        return $datos;
    }

    /**
     * Return ncf information by factura
     * 
     * @param integer $idempresa 
     * @param integer $documento idfactura 
     * @param string  $entidad   codproveedor 
     * 
     * @return \ncf_compras
     */
    public function get_ncf($idempresa, $documento, $entidad)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "documento = ".$this->intval($documento)." AND ".
                "entidad = ".$this->var2str($entidad).";");
        $result = false;
        if ($data) {
            $datos = new ncf_compras($data[0]);
            $this->info_factura($datos);
        }
        return $datos;
    }

    public function get_tipo_old($idempresa, $tipo_comprobante, $codalmacen, $area_impresion)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "codalmacen = ".$this->var2str($codalmacen)." AND ".
                "tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND ".
                "area_impresion = ".$this->var2str($area_impresion)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_compras($d);
            }
        }

        return $lista;
    }

    /**
     * Obtener tipo de comprobante fiscal
     * @param integer $idempresa
     * @param string $tipo_comprobante
     * @param string $codalmacen
     * @return \ncf_compras
     */
    public function getTipo($idempresa, $tipo_comprobante, $codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "codalmacen = ".$this->var2str($codalmacen)." AND ".
                "tipo_comprobante = ".$this->var2str($tipo_comprobante)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_compras($d);
            }
        }

        return $lista;
    }

    public function get_ultimo_documento($idempresa, $tipo_comprobante, $codalmacen)
    {
        $lista = array();
        $sql = "SELECT * from ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "codalmacen = ".$this->var2str($codalmacen)." AND ".
                "tipo_comprobante = ".$this->var2str($tipo_comprobante)." ".
                "ORDER BY idempresa, fecha DESC LIMIT 1";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_compras($d);
            }
        }
        return $lista;
    }

    public function info_factura(&$datos)
    {
        $otros_datos = $this->factura_proveedor->get($datos->documento);
        $datos->neto = (!empty($otros_datos))?$otros_datos->neto:0;
        $datos->totaliva = (!empty($otros_datos))?$otros_datos->totaliva:0;
        $datos->total = (!empty($otros_datos))?$otros_datos->total:0;
        $datos->tipo_descripcion = $this->ncf_tipo->get($datos->tipo_comprobante);
        $datos->descripcion_compra = $this->ncf_tipo_compras->get_descripcion($datos->tipo_compra);
        $datos->condicion = ($datos->estado)?"Activo":"Anulado";
        $datos->cifnif_len = strlen($datos->cifnif);
        $datos->cifnif_tipo = ($datos->cifnif_len == 9)?1:2;
        $datos->fecha_dgii = str_replace("-", "", $datos->fecha);
        $datos->nombre = (!empty($otros_datos))?$otros_datos->nombre:"PROVEEDOR NO EXISTE";
    }

    public function all_desde_hasta($idempresa, $fecha_inicio, $fecha_fin, $codalmacen='')
    {
        $lista = array();
        $extra='';
        if ($codalmacen !='') {
            $extra .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." ".
                "ORDER BY idempresa, fecha, ncf";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_compras($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }

        return $lista;
    }

    public function all_desde_hasta_limit($idempresa, $fecha_inicio, $fecha_fin, $codalmacen='', $offset=0, $limit=FS_ITEM_LIMIT)
    {
        $lista = array();
        $extra='';
        if ($codalmacen !='') {
            $extra .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." ".
                "ORDER BY idempresa, fecha, ncf";
        $data = $this->db->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_compras($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }

        return $lista;
    }

    public function all_activo_desde_hasta($idempresa, $fecha_inicio, $fecha_fin, $codalmacen='')
    {
        $lista = array();
        $extra='';
        if ($codalmacen !='') {
            $extra .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".
            " idempresa = ".$this->intval($idempresa)." AND ".
            " fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." AND estado = TRUE ".
            " ORDER BY idempresa, fecha, ncf;";
        $data = $this->db->select($sql);

        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_compras($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }

        return $lista;
    }

    public function all_anulado_desde_hasta($idempresa, $fecha_inicio, $fecha_fin, $codalmacen='')
    {
        $lista = array();
        $extra='';
        if ($codalmacen !='') {
            $extra .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $data = $this->db->select("SELECT * FROM ".$this->table_name." ".
                " WHERE idempresa = ".$this->intval($idempresa)." AND ".
                " fecha BETWEEN ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." and estado = false ".
                " ORDER BY idempresa, fecha, ncf");
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_compras($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }
        return $lista;
    }
}
