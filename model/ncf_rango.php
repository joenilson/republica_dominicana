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
 * Description of ncf_rango
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_rango extends fs_model
{
    public $id;
    public $idempresa;
    public $solicitud;
    public $codalmacen;
    public $serie;
    public $division;
    public $punto_emision;
    public $area_impresion;
    public $tipo_comprobante;
    public $secuencia_inicio;
    public $secuencia_fin;
    public $correlativo;
    public $usuario_creacion;
    public $fecha_creacion;
    public $usuario_modificacion;
    public $fecha_modificacion;
    public $estado;
    public $contado;

    public function __construct($t = false)
    {
        parent::__construct('ncf_rango', 'plugins/republica_dominicana/');
        if ($t) {
            $this->id = $t['id'];
            $this->idempresa = $t['idempresa'];
            $this->solicitud = $t['solicitud'];
            $this->codalmacen = $t['codalmacen'];
            $this->serie = $t['serie'];
            $this->division = $t['division'];
            $this->punto_emision = $t['punto_emision'];
            $this->area_impresion = $t['area_impresion'];
            $this->tipo_comprobante = $t['tipo_comprobante'];
            $this->secuencia_inicio = $t['secuencia_inicio'];
            $this->secuencia_fin = $t['secuencia_fin'];
            $this->correlativo = $t['correlativo'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->fecha_creacion = Date('d-m-Y H:i:s', strtotime($t['fecha_creacion']));
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
            $this->estado = $this->str2bool($t['estado']);
            $this->contado = $this->str2bool($t['contado']);
        } else {
            $this->id = null;
            $this->idempresa = null;
            $this->solicitud = null;
            $this->codalmacen = null;
            $this->serie = null;
            $this->division = null;
            $this->punto_emision = null;
            $this->area_impresion = null;
            $this->tipo_comprobante = null;
            $this->secuencia_inicio = null;
            $this->secuencia_fin = null;
            $this->correlativo = null;
            $this->usuario_creacion = null;
            $this->fecha_creacion = Date('d-m-Y H:i:s');
            $this->usuario_modificacion = null;
            $this->fecha_modificacion = Date('d-m-Y H:i:s');
            $this->estado = false;
            $this->contado = false;
        }
    }

    protected function install()
    {
        /*
         * se puede insertar datos en formato SQL
         */
        return '';
    }

    public function exists()
    {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->get_by_id($this->idempresa, $this->id);
        }
    }

    public function get($idempresa, $solicitud, $codalmacen, $serie, $division, $punto_emision, $area_impresion, $tipo_comprobante)
    {
        $data = $this->db->select("SELECT * FROM ncf_rango WHERE ".
                    "idempresa = ".$this->intval($idempresa)." AND ".
                    "solicitud = ".$this->intval($solicitud)." AND ".
                    "codalmacen = ".$this->var2str($codalmacen)." AND ".
                    "serie= ".$this->var2str($serie)." AND ".
                    "division= ".$this->var2str($division)." AND ".
                    "punto_emision = ".$this->var2str($punto_emision)." AND ".
                    "area_impresion = ".$this->var2str($area_impresion)." AND ".
                    "tipo_comprobante = ".$this->var2str($tipo_comprobante).";");
        if ($data) {
            return new ncf_rango($data[0]);
        } else {
            return false;
        }
    }

    public function get_by_id($idempresa, $id)
    {
        $data = $this->db->select("SELECT * FROM ncf_rango WHERE ".
                    "idempresa = ".$this->intval($idempresa)." AND ".
                    "id = ".$this->intval($id).";");
        if ($data) {
            return new ncf_rango($data[0]);
        } else {
            return false;
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE ncf_rango SET ".
                    "solicitud = ".$this->intval($this->solicitud).", ".
                    "codalmacen = ".$this->var2str($this->codalmacen).", ".
                    "serie= ".$this->var2str($this->serie).", ".
                    "division= ".$this->var2str($this->division).", ".
                    "punto_emision = ".$this->var2str($this->punto_emision).", ".
                    "area_impresion = ".$this->var2str($this->area_impresion).", ".
                    "tipo_comprobante = ".$this->var2str($this->tipo_comprobante).", ".
                    "secuencia_inicio = ".$this->intval($this->secuencia_inicio).", ".
                    "secuencia_fin = ".$this->intval($this->secuencia_fin).", ".
                    "correlativo = ".$this->intval($this->correlativo).", ".
                    "estado = ".$this->var2str($this->estado).", ".
                    "contado = ".$this->var2str($this->contado).", ".
                    "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
                    "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
                    "WHERE ".
                    "id = ".$this->intval($this->id)." AND ".
                    "idempresa = ".$this->intval($this->idempresa).";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO ncf_rango (idempresa, solicitud,  codalmacen, serie, division, punto_emision, area_impresion, tipo_comprobante, secuencia_inicio, secuencia_fin, correlativo, estado, contado, usuario_creacion, fecha_creacion ) ".
                    "VALUES ".
                    "(".
                    $this->intval($this->idempresa).", ".
                    $this->intval($this->solicitud).", ".
                    $this->var2str($this->codalmacen).", ".
                    $this->var2str($this->serie).", ".
                    $this->var2str($this->division).", ".
                    $this->var2str($this->punto_emision).", ".
                    $this->var2str($this->area_impresion).", ".
                    $this->var2str($this->tipo_comprobante).", ".
                    $this->intval($this->secuencia_inicio).", ".
                    $this->intval($this->secuencia_fin).", ".
                    $this->var2str($this->correlativo).", ".
                    $this->var2str($this->estado).", ".
                    $this->var2str($this->contado).", ".
                    $this->var2str($this->usuario_creacion).", ".
                    $this->var2str($this->fecha_creacion).
                    ")";

            if ($this->db->exec($sql)) {
                $this->solicitud = $this->solicitud;
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM ncf_rango WHERE ".
            "id = ".$this->intval($this->id)." AND ".
            "idempresa = ".$this->intval($this->idempresa).";");
    }

    public function all($idempresa)
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ncf_rango WHERE idempresa = ".$this->intval($idempresa)." ORDER BY codalmacen,tipo_comprobante, division, solicitud");

        if ($data) {
            foreach ($data as $d) {
                $lista[] = new ncf_rango($d);
            }
        }
        return $lista;
    }

    /**
     * Genera el NCF para ventas_facturas o nueva_venta
     * @param type $idempresa
     * @param type $codalmacen
     * @param type $tipo_comprobante
     * @param type $codpago
     * @return type array
     */
    public function generate($idempresa, $codalmacen, $tipo_comprobante, $codpago)
    {
        $ncf = array('NCF'=>'NO_DISPONIBLE');
        $contado = ($codpago == 'CONT')?"TRUE":"FALSE";
        $data = $this->db->select("SELECT ".
        " * ".
        " FROM ".$this->table_name.
        " WHERE ".
        " idempresa = ".$this->intval($idempresa)." AND ".
        " codalmacen = ".$this->var2str($codalmacen)." AND ".
        " contado = ".$contado." AND ".
        " tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND estado = true ;");
        if ($data) {
            $ncf = $this->ncf_number($data[0]);
        } else {
            $data2 = $this->db->select("SELECT ".
                " * ".
                " FROM ".$this->table_name.
                " WHERE ".
                " idempresa = ".$this->intval($idempresa)." AND ".
                " codalmacen = ".$this->var2str($codalmacen)." AND ".
                " contado != ".$contado." AND ".
                " tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND estado = true ;");
            if ($data2) {
                $ncf = $this->ncf_number($data2[0]);
            }
        }
        return $ncf;
    }

    /**
     * Genera el NCF para un TPV
     * @param type $idempresa
     * @param type $codalmacen
     * @param type $tipo_comprobante
     * @param type $codpago
     * @param type $area_impresion
     * @return type array
     */
    public function generate_terminal($idempresa, $codalmacen, $tipo_comprobante, $codpago, $area_impresion)
    {
        $ncf = array('NCF'=>'NO_DISPONIBLE');
        $contado = ($codpago == 'CONT')?"TRUE":"FALSE";
        $data = $this->db->select("SELECT ".
        "* ".
        "FROM ncf_rango ".
        "WHERE ".
        "idempresa = ".$this->intval($idempresa)." AND ".
        "codalmacen = ".$this->var2str($codalmacen)." AND ".
        "area_impresion = ".$this->var2str(str_pad($area_impresion, 3, '0', STR_PAD_LEFT))." AND ".
        "contado = ".$contado." AND ".
        "tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND estado = true ;");

        if ($data) {
            $ncf = $this->ncf_number($data[0]);
        } else {
            $data2 = $this->db->select("SELECT ".
                " * ".
                " FROM ".$this->table_name.
                " WHERE ".
                " idempresa = ".$this->intval($idempresa)." AND ".
                " codalmacen = ".$this->var2str($codalmacen)." AND ".
                " area_impresion = ".$this->var2str(str_pad($area_impresion, 3, '0', STR_PAD_LEFT))." AND ".
                " contado != ".$contado." AND ".
                " tipo_comprobante = ".$this->var2str($tipo_comprobante)." AND estado = true ;");
            if ($data2) {
                $ncf = $this->ncf_number($data2[0]);
            }
        }
        return $ncf;
    }

    public function get_by_almacen($idempresa, $almacen)
    {
        $sql = "SELECT * FROM ".$this->table_name." WHERE idempresa = ".$this->intval($idempresa)." AND codalmacen = ".$this->var2str($almacen).";";
        $data = $this->db->exec($sql);
        if ($data) {
            $lista = array();
            foreach ($data as $d) {
                $lista[] = new ncf_rango($d);
            }
            return $lista;
        } else {
            return false;
        }
    }

    protected function ncf_number($data)
    {
        $solicitud = new ncf_rango($data);
        $rango = $solicitud->serie.$solicitud->division.$solicitud->punto_emision.$solicitud->area_impresion.$solicitud->tipo_comprobante;
        $correlativo = str_pad($solicitud->correlativo, 8, '0', STR_PAD_LEFT);
        $ncf_number = ($correlativo == $solicitud->secuencia_fin)?"NO_DISPONIBLE":$rango.$correlativo;
        return array('NCF'=>$ncf_number,'SOLICITUD'=>$solicitud->solicitud);
    }

    public function update($idempresa, $codalmacen, $solicitud, $ncf, $usuario)
    {
        $corr_old = $this->get_correlativo($idempresa, $codalmacen, $ncf);
        $corr_new = \substr($ncf, 11, 18)+1;
        if($corr_new > $corr_old){
            $corr_new = $corr_old;
        }
        $sql = "UPDATE ".$this->table_name." SET ".

            "correlativo = ".$this->intval($corr_new).", ".
            "usuario_modificacion = ".$this->var2str($usuario).", ".
            "fecha_modificacion = ".$this->var2str(Date('d-m-Y H:i:s'))." ".
            "WHERE ".
            "idempresa = ".$this->intval($idempresa)." AND ".
            "solicitud = ".$this->intval($solicitud)." AND ".
            "codalmacen = ".$this->var2str($codalmacen)." AND ".
            "serie= ".$this->var2str(\substr($ncf, 0, 1))." AND ".
            "division= ".$this->var2str(\substr($ncf, 1, 2))." AND ".
            "punto_emision = ".$this->var2str(\substr($ncf, 3, 3))." AND ".
            "area_impresion = ".$this->var2str(\substr($ncf, 6, 3))." AND ".
            "tipo_comprobante = ".$this->var2str(\substr($ncf, 9, 2)).";";

        return $this->db->exec($sql);
    }

    public function get_solicitud($idempresa, $codalmacen, $ncf)
    {
        $solicitud = 0;
        $sql = "SELECT solicitud FROM ".$this->table_name.
            " WHERE ".
            "idempresa = ".$this->intval($idempresa)." AND ".
            "codalmacen = ".$this->var2str($codalmacen)." AND ".
            "serie = ".$this->var2str(\substr($ncf, 0, 1))." AND ".
            "division = ".$this->var2str(\substr($ncf, 1, 2))." AND ".
            "punto_emision = ".$this->var2str(\substr($ncf, 3, 3))." AND ".
            "area_impresion = ".$this->var2str(\substr($ncf, 6, 3))." AND ".
            "tipo_comprobante = ".$this->var2str(\substr($ncf, 9, 2)).";";
        $data = $this->db->select($sql);
        if($data){
            $solicitud = $data[0]['solicitud'];
        }
        return $solicitud;
    }
    
    public function get_correlativo($idempresa, $codalmacen, $ncf)
    {
        $sql = "SELECT correlativo FROM ".$this->table_name.
            " WHERE ".
            "idempresa = ".$this->intval($idempresa)." AND ".
            "codalmacen = ".$this->var2str($codalmacen)." AND ".
            "serie = ".$this->var2str(\substr($ncf, 0, 1))." AND ".
            "division = ".$this->var2str(\substr($ncf, 1, 2))." AND ".
            "punto_emision = ".$this->var2str(\substr($ncf, 3, 3))." AND ".
            "area_impresion = ".$this->var2str(\substr($ncf, 6, 3))." AND ".
            "tipo_comprobante = ".$this->var2str(\substr($ncf, 9, 2)).";";
        $data = $this->db->select($sql);
        $item = 0;
        if($data){
            $item = $data[0]['correlativo'];
        }
        return $item;
    }
}
