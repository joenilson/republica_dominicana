<?php

/*
 * Copyright (C) 2019 joenilson
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of articulos_clasificacion
 *
 * @author joenilson
 */
class articulos_clasificacion extends \fs_controller
{
    public $articulos_elegidos;
    public $articulos_sin_clasificar;
    public $rd_articulos_clasificacion;
    public $resultados_bienes;
    public $resultados_servicios;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'clasificacion', 'ventas', FALSE, FALSE, FALSE);
    }
    
    protected function private_core()
    {
        parent::private_core();
        $this->share_extensions();
        
        $accion = filter_input(INPUT_POST, 'accion');

        $this->articulos_elegidos = filter_input(INPUT_POST, 'check', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if($accion === 'asignar') {
            $this->asignar_articulos();
        } elseif ($accion === 'desasignar') {
            $this->desasignar_articulos();
        }
        
        $this->rd_articulos_clasificacion = new rd_articulo_clasificacion();
        $this->resultados_bienes = $this->rd_articulos_clasificacion->get_articulos_tipo('01');
        $this->resultados_servicios = $this->rd_articulos_clasificacion->get_articulos_tipo('02');
        $this->articulos_sin_clasificar = $this->rd_articulos_clasificacion->get_articulos_sin_clasificar();
    }
    
    public function asignar_articulos()
    {
        $tipo_articulo = filter_input(INPUT_POST, 'tipo_articulo');
        foreach($this->articulos_elegidos as $art) {
            $artcla = new rd_articulo_clasificacion();
            $artcla->referencia = $art;
            $artcla->tipo_articulo = $tipo_articulo;
            $artcla->save();
        }
        $this->new_message('Asignación correcta.');
    }
    
    public function desasignar_articulos()
    {
        foreach($this->articulos_elegidos as $art) {
            $artcla = new rd_articulo_clasificacion();
            $art = $artcla->get_referencia($art);
            if($art) {
                $art->delete();
            }
        }
        $this->new_message('Desasignación correcta.');
    }
    
    private function share_extensions()
    {
        //añadimos la extensión para ventas_artículos
        $fsext = new fs_extension();
        $fsext->name = 'btn_rd_clasificacion';
        $fsext->from = __CLASS__;
        $fsext->to = 'ventas_articulos';
        $fsext->type = 'button';
        $fsext->text = '<i class="fa fa-toggle-on fa-fw" aria-hidden="true"></i>'
            . '<span class="hidden-xs"> &nbsp; Clasificacion</span>';
        $fsext->save();
    }
}
