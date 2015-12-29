<?php
/*
 * Copyright (C) 2015 joenilson
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
require_model('almacen.php');
require_model('pais.php');
require_model('ncf_rango.php');
require_model('ncf_tipo.php');
require_model('ncf_ventas.php');
/**
 * Description of helper_ncf
 *
 * @author joenilson
 */
class helper_ncf extends fs_controller {
    
    public function __construct() {
        parent::__construct(__CLASS__, 'Helper de NCF', 'plugins/republica_dominicana', FALSE, FALSE);
    }
    
    protected function private_core() {
        
    }
}
