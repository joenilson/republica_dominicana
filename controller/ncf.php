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
require_model('ncf_rango.php');
require_model('ncf_tipo.php');

/**
 * Description of ncf
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class ncf extends fs_controller {
    
    public $ncf_rango;
    
    public function __construct()
    {
       parent::__construct(__CLASS__, 'Maestro de NCF', 'contabilidad');
    }
    
    protected function private_core()
    {
        $this->ncf_rango = new ncf_rango();
        $this->ncf_tipo = new ncf_tipo();
    }

}
