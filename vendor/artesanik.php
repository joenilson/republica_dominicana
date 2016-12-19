<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
namespace Artesanik;
/**
 * Clase princpipal para llamar a funciones y clases
 * generadas para utilizar de forma gnerica
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class artesanik {
    public $name;
    public $version;
    public $release;
    public $patch;
    /**
     * Construimos la clase llamando el nombre y la versión
     */
    public function __construct() {
        $json = file_get_contents(dirname(__FILE__)."/config.json");
        $config = json_decode($json);
        $this->name = $config['name'];
        $this->version = $config['version'];
        $this->release = $config['release'];
        $this->patch = $config['patch'];
    }
}
