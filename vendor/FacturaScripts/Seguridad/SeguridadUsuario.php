<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Seguridad;

/**
 * Description of Usuario
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class SeguridadUsuario {
    public $agente;
    public $almacenes;
    public function __construct() {
        $this->agente = new \agente();
        $this->almacenes = new \almacen();
    }
    
    public function accesoAlmacenes($user){
        $user->codalmacen = false;
        $user->nombrealmacen = false;
        if(!$user->admin){
            $cod = $this->agente->get($user->codagente);
            if(isset($cod) AND !empty($cod)){
                $user_almacen = $this->almacenes->get($cod->codalmacen);
                $user->codalmacen = $user_almacen->codalmacen;
                $user->nombrealmacen = $user_almacen->nombre;
            }
        }
        return $user;
    }
}
