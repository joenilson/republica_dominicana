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
 * Description of ncf_ventas
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_ventas extends \fs_model
{
    public $idempresa;
    public $codalmacen;
    public $entidad;
    public $cifnif;
    public $documento;
    public $documento_modifica;
    public $fecha;
    public $fecha_vencimiento;
    public $tipo_comprobante;
    public $tipo_ingreso;
    public $tipo_pago;
    public $area_impresion;
    public $ncf;
    public $ncf_modifica;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $estado;
    public $motivo;

    public $ncf_tipo;
    public $ncf_tipo_ingresos;
    public $factura_cliente;
    public function __construct($data = false)
    {
        parent::__construct('ncf_ventas', 'plugins/republica_dominicana/');
        
        if ($data) {
            $this->idempresa = $data['idempresa'];
            $this->codalmacen = $data['codalmacen'];
            $this->entidad = $data['entidad'];
            $this->cifnif = $data['cifnif'];
            $this->documento = $data['documento'];
            $this->documento_modifica = $data['documento_modifica'];
            $this->fecha = $data['fecha'];
            $this->fecha_vencimiento = $data['fecha_vencimiento'];
            $this->tipo_comprobante = $data['tipo_comprobante'];
            $this->tipo_ingreso = $data['tipo_ingreso'];
            $this->tipo_pago = $data['tipo_pago'];
            $this->area_impresion = $data['area_impresion'];
            $this->ncf = $data['ncf'];
            $this->ncf_modifica = $data['ncf_modifica'];
            $this->usuario_creacion = $data['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($data['fecha_creacion']));
            $this->usuario_modificacion = $data['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
            $this->estado = $this->str2bool($data['estado']);
            $this->motivo = $data['motivo'];
        } else {
            $this->idempresa = null;
            $this->codalmacen = null;
            $this->entidad = null;
            $this->cifnif = null;
            $this->documento = null;
            $this->documento_modifica = null;
            $this->fecha = \date('d-m-Y');
            $this->fecha_vencimiento = null;
            $this->tipo_comprobante = null;
            $this->tipo_ingreso = '1';
            $this->tipo_pago = '17';
            $this->area_impresion = null;
            $this->ncf = null;
            $this->ncf_modifica = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = \date('d-m-Y H:i:s');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = null;
            $this->estado = true;
            $this->motivo = null;
        }

        $this->ncf_tipo = new ncf_tipo();
    }

    protected function install()
    {
        return "";
    }

    public function exists()
    {
        if (is_null($this->idempresa) and is_null($this->ncf)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                    "idempresa = ".$this->intval($this->idempresa)." AND ".
                "ncf = ".$this->var2str($this->ncf).";");
        }
    }

    public function save()
    {
        if (!$this->exists()) {
            $sql = "INSERT INTO ncf_ventas (idempresa, codalmacen, entidad, cifnif, documento, documento_modifica, fecha, fecha_vencimiento, tipo_comprobante, tipo_ingreso, tipo_pago, area_impresion, ncf, ncf_modifica, estado, usuario_creacion, fecha_creacion ) VALUES ".
                    "(".
                    $this->intval($this->idempresa).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->entidad).", ".
                    $this->var2str($this->cifnif).", ".
                    $this->intval($this->documento).", ".
                    $this->var2str($this->documento_modifica).", ".
                    $this->var2str($this->fecha).", ".
                    $this->var2str($this->fecha_vencimiento).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    $this->var2str($this->tipo_ingreso).", ".
                    $this->var2str($this->tipo_pago).", ".
                    $this->var2str($this->area_impresion).", ".
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
        $sql = "UPDATE ncf_ventas SET ".
                "estado = false, motivo = ".$this->var2str($this->motivo).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                "WHERE ".
                "ncf = ".$this->var2str($this->ncf). " AND ".
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
        $sql = "UPDATE ncf_ventas SET ".
                "fecha = ".$this->var2str($this->fecha).", ".
                "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                "WHERE ".
                "ncf = ".$this->var2str($this->ncf). " AND ".
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
        return $this->db->exec("DELETE FROM ncf_ventas WHERE idempresa = ".$this->intval($this->idempresa)." AND ncf = ".$this->var2str($this->ncf)." AND fecha = ".$this->var2str($this->fecha).";");
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_ventas($d);
            }
        }

        return $lista;
    }

    /**
     * Return NCF information
     * @param integer $idempresa
     * @param string $ncf
     * @return \ncf_ventas
     */
    public function get($idempresa, $ncf)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "ncf = ".$this->var2str($ncf)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_ventas($d);
            }
        }

        return $lista;
    }

    /**
     * Return ncf information by factura
     * 
     * @param integer $idempresa 
     * @param integer $documento idfactura 
     * @param string  $entidad   codcliente 
     * 
     * @return \ncf_ventas
     */
    public function get_ncf($idempresa, $documento, $entidad)
    {
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "documento = ".$this->intval($documento)." AND ".
                "entidad = ".$this->var2str($entidad).";");
        $result = false;
        if ($data) {
            $result = $data[0];
        }
        return new ncf_ventas($result);
    }

    public function get_tipo_old($idempresa, $tipo_comprobante, $codalmacen, $area_impresion)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "codalmacen = ".$this->var2str($codalmacen)." AND ".
                "tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND ".
                "area_impresion = ".$this->var2str($area_impresion)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_ventas($d);
            }
        }

        return $lista;
    }

    /**
     * Obtener tipo de comprobante fiscal
     * @param integer $idempresa
     * @param string $tipo_comprobante
     * @param string $codalmacen
     * @return \ncf_ventas
     */
    public function getTipo($idempresa, $tipo_comprobante, $codalmacen)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "codalmacen = ".$this->var2str($codalmacen)." AND ".
                "tipo_comprobante = ".$this->var2str($tipo_comprobante)." ".
                "ORDER BY idempresa, ncf, fecha");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_ventas($d);
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
                $lista[] = new ncf_ventas($d);
            }
        }
        return $lista;
    }

    public function info_factura_old($idfactura)
    {
        $factura_cliente = new factura_cliente();
        $datos_adicionales = $factura_cliente->get($idfactura);
        return $datos_adicionales;
    }

    public function info_factura(&$datos)
    {
        $factura_cliente = new factura_cliente();
        $ncf_tipo = new ncf_tipo();
        $ncf_tipo_ingreso = new ncf_tipo_ingresos();
        $otros_datos = $factura_cliente->get($datos->documento);
        $datos->pagada = (!empty($otros_datos))?$otros_datos->pagada:false;
        $datos->neto = (!empty($otros_datos))?$otros_datos->neto:0;
        $datos->totaliva = (!empty($otros_datos))?$otros_datos->totaliva:0;
        $datos->total = (!empty($otros_datos))?$otros_datos->total:0;
        $datos->tipo_descripcion = $ncf_tipo->get($datos->tipo_comprobante);
        $datos->condicion = ($datos->estado)?"Activo":"Anulado";
        $datos->cifnif_len = strlen($datos->cifnif);
        $datos->cifnif_tipo = ($datos->cifnif_len == 9)?1:2;
        $datos->nombrecliente = (!empty($otros_datos))?$otros_datos->nombrecliente:"CLIENTE NO EXISTE";
        $datos->fecha_dgii = str_replace("-", "", $datos->fecha);
        $datos->descripcion_tipo_ingreso = $ncf_tipo_ingreso->get_descripcion($datos->tipo_ingreso);
    }

    public function all_desde_hasta($idempresa, $fecha_inicio, $fecha_fin, $codalmacen='')
    {
        $lista = array();
        $extra='';
        if ($codalmacen !='') {
            $extra .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        $sql = "SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." ".
                "ORDER BY idempresa, fecha, ncf";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_ventas($d);
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
        $sql = "SELECT * FROM ncf_ventas WHERE ".
                "idempresa = ".$this->intval($idempresa)." AND ".
                "fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." ".
                "ORDER BY idempresa, fecha, ncf";
        $data = $this->db->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_ventas($d);
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
        $sql = "SELECT * FROM ncf_ventas WHERE ".
            " idempresa = ".$this->intval($idempresa)." AND ".
            " fecha between ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." AND estado = TRUE ".
            " ORDER BY idempresa, fecha, ncf;";
        $data = $this->db->select($sql);

        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_ventas($d);
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
        $data = $this->db->select("SELECT * FROM ncf_ventas ".
                " WHERE idempresa = ".$this->intval($idempresa)." AND ".
                " fecha BETWEEN ".$this->var2str($fecha_inicio)." AND ".$this->var2str($fecha_fin).$extra." and estado = false ".
                " ORDER BY idempresa, fecha, ncf");
        if ($data) {
            foreach ($data as $d) {
                $datos = new ncf_ventas($d);
                $this->info_factura($datos);
                $lista[] = $datos;
            }
        }
        return $lista;
    }
}
