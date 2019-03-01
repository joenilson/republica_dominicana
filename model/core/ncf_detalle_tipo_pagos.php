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
 * Description of ncf_detalle_tipo_pagos
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf_detalle_tipo_pagos extends \fs_model
{
    /**
     *
     * @var string
     */
    public $codigo;
    /**
     *
     * @var string
     */
    public $codpago;

    
    public function __construct($t = FALSE)
    {
        parent::__construct('ncf_detalle_tipo_pagos', 'plugins/republica_dominicana/');
        if ($t) {
            $this->codigo = $t['codigo'];
            $this->codpago = $t['codpago'];
        } else {
            $this->codigo = null;
            $this->codpago = '';
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
        if (is_null($this->codigo) AND is_null($this->codpago)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($this->codigo)." and codpago = ".$this->var2str($this->codpago).";");
        }
    }

    /**
     * 
     * @return boolean
     */
    public function save()
    {
        if ($this->exists()) {
            return false;
        } else {
            $sql = "INSERT INTO ".$this->table_name." (codigo, codpago) VALUES ".
                "(".$this->var2str($this->codigo).", ".$this->var2str($this->codpago).");";
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
        return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codigo = ".$this->var2str($this->codigo)." AND codpago = ".$this->var2str($this->codpago).";");
    }

    public function all()
    {
        $lista = array();
        $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codigo,codpago");

        if ($data) {
            foreach ($data as $d) {
                $item = new ncf_detalle_tipo_pagos($d);
                $this->info_formapago($item);
                $lista[] = $item;
            }
        }

        return $lista;
    }

    /**
     * Get the info for a codigo and codpago params
     * @param string $codigo
     * @param string $codpago
     * @return \FacturaScripts\model\ncf_detalle_tipo_pagos
     */
    public function get($codigo,$codpago)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo)." AND codpago = ".$this->var2str($codpago).";");

        $item = new ncf_detalle_tipo_pagos($data[0]);
        $this->info_formapago($item);
        return $item;
    }
    
    /**
     * Get the list of codpago for a given codigo
     * @param string $codigo
     * @return \FacturaScripts\model\ncf_detalle_tipo_pagos
     */
    public function get_codpagos($codigo)
    {
        $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo).";");
        
        $lista = [];
        if($data) {
            foreach($data as $d) {
                $item = new ncf_detalle_tipo_pagos($d);
                $this->info_formapago($item);
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
    /**
     * Get the codigo for a given codpago
     * @param string $codpago
     * @return \FacturaScripts\model\ncf_detalle_tipo_pagos
     */
    public function get_codigo($codpago)
    {
        $data = $this->db->select("SELECT codigo FROM ".$this->table_name." WHERE codpago = ".$this->var2str($codpago).";");
        $item = false;
        if($data) {
            $item = $data[0]['codigo'];
        }
        return $item;
    }
    
    private function info_formapago(&$data)
    {
        $formapago = new \forma_pago();
        $fp = $formapago->get($data->codpago);
        $data->descripcion_codpago = $fp->descripcion;
    }
    
    public function get_codpago_libres()
    {
        $sql = "select codpago, descripcion from formaspago where codpago NOT IN (SELECT codpago from ".$this->table_name.") order by codpago;";
        $data = $this->db->select($sql);
        $this->new_error_msg($sql);
        $lista = [];
        if($data) {
            foreach($data as $d) {
                $item = new \forma_pago($d);
                $lista[] = $item;
            }
        }
        return $lista;
    }
}
