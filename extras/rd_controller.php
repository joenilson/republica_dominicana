<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'plugins/facturacion_base/extras/fbase_controller.php';
/**
 * Description of rd_controller
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class rd_controller extends fbase_controller
{
    /**
     * TRUE si el usuario tiene permisos para eliminar en la página.
     * @var type
     */
    public $allow_delete;

    /**
     * TRUE si hay más de un almacén.
     * @var type
     */
    public $multi_almacen;
    public $divisa;
    public $forma_pago;
    public $periodos;
    public $meses = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Setiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
    public $ncf_rango;
    public $ncf_tipo;
    public $ncf_entidad_tipo;
    public $ncf_tipo_anulacion;
    public $ncf_ventas;
    public $agente;
    public $almacen;
    public $almacenes;
    public $impuesto;
    public $pais;
    public $serie;
    public $array_series;
    public $tesoreria;
    public $rd_setup;
    public $distribucion_clientes;
    public $documentosDir;
    public $exportDir;
    public $publicPath;
    public $ncf_length;
    public $tipo_documento_pos;
    protected function private_core()
    {
        $this->agente = new agente();
        $this->almacen = new almacen();
        $this->almacenes = new almacen();
        $this->divisa = new divisa();
        $this->forma_pago = new forma_pago();
        $this->impuesto = new impuesto();
        $this->pais = new pais();
        $this->serie = new serie();
        $this->ncf_rango = new ncf_rango();
        $this->ncf_tipo = new ncf_tipo();
        $this->ncf_entidad_tipo = new ncf_entidad_tipo();
        $this->ncf_tipo_anulacion = new ncf_tipo_anulacion();
        $this->ncf_ventas = new ncf_ventas();
        $this->array_series = \range('A', 'Z');
        $fsvar = new fs_var();
        $this->multi_almacen = $fsvar->simple_get('multi_almacen');
        $this->periodos = range(2016, \date('Y'));
        $this->existe_tesoreria();
        $this->control_usuarios();
        $this->get_config();
        $this->verificar_plugin_distribucion();
        $this->ncf_length = 11;
        $this->tipo_documento_pos = 1;
    }

    public function verificar_plugin_distribucion()
    {
        //Para el plugin distribucion
        if (class_exists('distribucion_clientes')) {
            $this->distribucion_clientes = new distribucion_clientes();
        }
    }

    public function get_config()
    {
        $fsvar = new fs_var();
        $this->rd_setup = $fsvar->array_get(
            array(
            'rd_imprimir_logo' => 'TRUE',
            'rd_imprimir_marca_agua' => 'TRUE',
            'rd_imprimir_sello_pagado' => 'FALSE',
            'rd_imprimir_bn' => 'FALSE',
            'rd_imprimir_cliente_box' => 'TRUE',
            'rd_imprimir_detalle_box' => 'TRUE',
            'rd_imprimir_detalle_lineas' => 'TRUE',
            'rd_imprimir_detalle_colores' => 'TRUE',
            'rd_imprimir_cabecera_fcolor' => '#000000',
            'rd_imprimir_cabecera_tcolor' => '#dadada',
            'rd_imprimir_detalle_color' => '#000000',
            ), false
        );
    }

    /**
     * Función para devolver un valor u otro dependiendo si está presente
     * el primer valor y si la variable existe
     * @param string $variable
     * @param string $valor_si
     * @param string $valor_no
     * @return string
     */
    public function setValor($variable, $valor_si, $valor_no)
    {
        $valor = $valor_no;
        if(!empty($variable) and ($variable == $valor_si)){
            $valor = $valor_si;
        }
        return $valor;
    }

    /**
     * Función para devolver el valor que no esté vacio
     * @param string $valor1
     * @param string $valor2
     * @return string
     */
    public function confirmarValor($valor1, $valor2)
    {
        $valor = $valor2;
        if(!empty($valor1)){
            $valor = $valor1;
        }
        return $valor;
    }

    public function control_usuarios()
    {
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if (!$this->user->admin) {
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = ($cod and isset($cod->codalmacen))?$this->almacenes->get($cod->codalmacen):false;
            $this->user->codalmacen = (isset($user_almacen->codalmacen))?$user_almacen->codalmacen:'';
            $this->user->nombrealmacen = (isset($user_almacen->nombre))?$user_almacen->nombre:'';
        }
    }

    public function ncf_tipo_comprobante($idempresa, $codigo_entidad, $tipo_entidad = 'CLI')
    {
        $tipo_comprobante = '02';
        $tipo_comprobante_d = $this->ncf_entidad_tipo->get($idempresa, $codigo_entidad, $tipo_entidad);
        if ($tipo_comprobante_d) {
            $tipo_comprobante = $tipo_comprobante_d->tipo_comprobante;
        } else {
            $net0 = new ncf_entidad_tipo();
            $net0->entidad = $codigo_entidad;
            $net0->estado = true;
            $net0->fecha_creacion = \date('Y-m-d H:i:s');
            $net0->usuario_creacion = $this->user->nick;
            $net0->idempresa = $idempresa;
            $net0->tipo_comprobante = $tipo_comprobante;
            $net0->tipo_entidad = $tipo_entidad;
            $net0->save();
        }
        return $tipo_comprobante;
    }

    public function generar_numero_ncf_old($idempresa,$codalmacen,$tipo_comprobante, $condicion_pago)
    {
        $numero_ncf = $this->ncf_rango->generate_old($idempresa, $codalmacen, $tipo_comprobante, $condicion_pago);
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', no se podrá generar la Nota de Crédito.');
            return false;
        }
        return $numero_ncf;
    }

    public function generar_numero_ncf($idempresa,$codalmacen,$tipo_comprobante, $condicion_pago)
    {
        $numero_ncf = $this->ncf_rango->generate($idempresa,$codalmacen, $tipo_comprobante, $condicion_pago);
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', no se podrá generar la Nota de Crédito.');
            return false;
        }
        return $numero_ncf;
    }

    public function guardar_ncf($idempresa, $factura, $tipo_comprobante, $numero_ncf, $motivo = false)
    {
        $function_update = (\strtotime($factura->fecha) < (\strtotime('01-05-2018'))) ? 'update_old' : 'update';
        if ($numero_ncf['NCF'] == 'NO_DISPONIBLE') {
            return $this->new_error_msg('No hay números NCF disponibles del tipo ' . $tipo_comprobante . ', la factura ' . $factura->idfactura . ' se creo sin NCF.');
        } else {
            $ncf_factura = new ncf_ventas();
            $ncf_factura->idempresa = $idempresa;
            $ncf_factura->codalmacen = $factura->codalmacen;
            $ncf_factura->entidad = $factura->codcliente;
            $ncf_factura->cifnif = $factura->cifnif;
            $ncf_factura->documento = $factura->idfactura;
            $ncf_factura->fecha = $factura->fecha;
            $ncf_factura->tipo_comprobante = $tipo_comprobante;
            $ncf_factura->area_impresion = NULL;
            $ncf_factura->ncf = $numero_ncf['NCF'];
            $ncf_factura->usuario_creacion = $this->user->nick;
            $ncf_factura->fecha_creacion = Date('d-m-Y H:i:s');
            $ncf_factura->estado = true;
            if ($factura->idfacturarect) {
                $ncf_orig = new ncf_ventas();
                $val_ncf = $ncf_orig->get_ncf($this->empresa->id, $factura->idfacturarect, $factura->codcliente);
                $ncf_factura->documento_modifica = $factura->idfacturarect;
                $ncf_factura->ncf_modifica = $val_ncf->ncf;
                $ncf_factura->motivo = $motivo;
            }
            if (!$ncf_factura->save()) {
                $factura->numero2 = '';
                $factura->save();
                $this->new_error_msg('Ocurrió un error al grabar la factura ' . $factura->codigo . ' con el NCF: ' . $numero_ncf['NCF'] . ' Ingrese a la factura y dele al botón corregir NCF.');
            } else {
                $factura->numero2 = $numero_ncf['NCF'];
                $factura->save();
                $this->ncf_rango->$function_update($ncf_factura->idempresa, $ncf_factura->codalmacen, $numero_ncf['SOLICITUD'], $numero_ncf['NCF'], $this->user->nick);
            }
        }
    }

    /**
     * Función para devolver el valor de una variable pasada ya sea por POST o GET
     * @param type string
     * @return type string
     */
    public function filter_request($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre);
        $nombre_get = \filter_input(INPUT_GET, $nombre);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function filter_request_array($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $nombre_get = \filter_input(INPUT_GET, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function existe_tesoreria()
    {
        $this->tesoreria = false;
        //revisamos si esta el plugin de tesoreria
        $disabled = array();
        if (defined('FS_DISABLED_PLUGINS')) {
            foreach (explode(',', FS_DISABLED_PLUGINS) as $aux) {
                $disabled[] = $aux;
            }
        }
        if (in_array('tesoreria', $GLOBALS['plugins']) and ! in_array('tesoreria', $disabled)) {
            $this->tesoreria = true;
        }
    }

    public function carpetasPlugin()
    {
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->exportDir = $this->documentosDir . DIRECTORY_SEPARATOR . "informes_rd";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'informes_rd';
        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir);
        }
    }
}
