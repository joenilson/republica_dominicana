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
/**
 * Description of articulo_clasificacion
 *
 * @author Joe Nilson
 */
class articulo_clasificacion extends fs_controller
{

    public $articulo;
    public $articulo_tipo;
    public $rd_articulo_clasificacion;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Clasificación', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->template = 'tab/' . __CLASS__;
        $this->share_extension();
        $art0 = new articulo();
        
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion) {
            if($accion === 'guardar') {
                $this->tratar_clasificacion();
            }
        }
        
        $this->articulo = FALSE;
        if (isset($_REQUEST['ref'])) {
            $this->articulo = $art0->get($_REQUEST['ref']);
            $this->articulo->tipo_articulo = null;
        }

        if ($this->articulo) {
            $ac = new rd_articulo_clasificacion();
            $data_ac = $ac->get_referencia($this->articulo->referencia);
            $this->articulo->tipo_articulo = ($data_ac)?$data_ac->tipo_articulo:null;

        } else {
            $this->new_error_msg('Artículo no encontrado.', 'error', FALSE, FALSE);
        }
        $this->rd_articulo_clasificacion = new rd_articulo_clasificacion();
    }
    
    public function tratar_clasificacion()
    {
        $referencia = filter_input(INPUT_POST, 'referencia');
        $tipo_articulo = filter_input(INPUT_POST, 'tipo_articulo');
        $ac0 = new rd_articulo_clasificacion();
        $ac0->referencia = $referencia;
        $ac0->tipo_articulo = $tipo_articulo;
        if($ac0->save()) {
            $this->new_message('Clasificación de Articulo guardada satisfactoriamente.');
        } else {
            $this->new_error_msg('Ocurrió un error al intentar guardar la información del articulo intente nuevamente.');
        }
    }

    private function share_extension()
    {
        $fsext = new fs_extension();
        $fsext->name = 'articulo_clasificacion';
        $fsext->from = __CLASS__;
        $fsext->to = 'ventas_articulo';
        $fsext->type = 'tab';
        $fsext->text = '<i class="fa fa-toggle-on fa-fw" aria-hidden="true">'
            . '</i><span class="hidden-xs">&nbsp; Clasificación</span>';
        $fsext->save();
    }

    public function url()
    {
        if ($this->articulo) {
            return 'index.php?page=' . __CLASS__ . '&ref=' . $this->articulo->referencia;
        }

        return parent::url();
    }
}
