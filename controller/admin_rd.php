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

require_model('divisa.php');
require_model('pais.php');
require_model('impuesto.php');
require_model('cuenta_especial.php');
require_model('ncf_tipo.php');
require_model('ncf_entidad_tipo.php');
require_model('ncf_rango.php');
require_model('ncf_tipo_anulacion.php');
require_model('ncf_ventas.php');
/**
 * Description of admin_rd
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class admin_rd extends fs_controller {

    public $conf_divisa;
    public $conf_impuestos;
    public $conf_pais;
    public $conf_regional;
    public $impuestos_rd;
    public $variables;
    public $rd_setup;
    public function __construct() {
        parent::__construct(__CLASS__, 'República Dominicana', 'admin');
    }

    protected function private_core() {
        //creamos las tablas necesarias si no están ya creadas
        new ncf_tipo();
        new ncf_entidad_tipo();
        new ncf_rango();
        new ncf_tipo_anulacion();
        new ncf_ventas();

        $this->share_extensions();
        $impuesto_empresa = new impuesto();
        $this->variables = array();
        $this->variables['zona_horaria'] = "America/Santo_Domingo";
        $this->variables['nf0'] = "2";
        $this->variables['nf0_art'] = "4";
        $this->variables['nf1'] = ".";
        $this->variables['nf2'] = ",";
        $this->variables['pos_divisa'] = "left";
        $this->variables['factura'] = "factura";
        $this->variables['facturas'] = "facturas";
        $this->variables['factura_simplificada'] = "factura simplificada";
        $this->variables['factura_rectificativa'] = "nota de credito";
        $this->variables['albaran'] = "conduce";
        $this->variables['albaranes'] = "conduces";
        $this->variables['pedido'] = "pedido";
        $this->variables['pedidos'] = "pedidos";
        $this->variables['presupuesto'] = "presupuesto";
        $this->variables['presupuestos'] = "presupuestos";
        $this->variables['provincia'] = "provincia";
        $this->variables['apartado'] = "apartado";
        $this->variables['cifnif'] = "Cedula/RNC";
        $this->variables['iva'] = "ITBIS";
        $this->variables['numero2'] = "NCF";
        $this->variables['serie'] = "serie";
        $this->variables['series'] = "series";

        $this->get_config();
        
        $this->impuestos_rd = array(
            array('codigo' => 'ITBIS18', 'descripcion' => 'ITBIS 18%', 'porcentaje' => 18, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => ''),
            array('codigo' => 'ITBIS10', 'descripcion' => 'ITBIS 10%', 'porcentaje' => 10, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => ''),
            array('codigo' => 'ITBIS8', 'descripcion' => 'ITBIS 8%', 'porcentaje' => 8, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => ''),
            array('codigo' => 'EXENTO', 'descripcion' => 'EXENTO', 'porcentaje' => 0, 'recargo' => 0, 'subcuenta_compras' => '', 'subcuenta_ventas' => '')
        );

        $opcion = \filter_input(INPUT_GET, 'opcion');
        switch($opcion){
            case 'moneda':
                $this->moneda();
                break;
            case 'impuestos':
                $this->impuestos();
                break;
            case 'pais':
                $this->pais();
                break;
            case 'configuracion_regional':
                $this->configuracion_regional();
                break;
            case 'impresion':
                $this->impresion();
                break;
            default:
                break;
        }

        $this->get_config();

        $this->conf_divisa = ($this->empresa->coddivisa == 'DOP') ? TRUE : FALSE;
        $this->conf_pais = ($this->empresa->codpais == 'DOM') ? TRUE : FALSE;
        $this->conf_regional = ($GLOBALS['config2']['iva'] == 'ITBIS') ? TRUE : FALSE;
        $this->conf_impuestos = ($impuesto_empresa->get_by_iva(18)) ? TRUE : FALSE;
        //Cargamos el menu
        $this->check_menu();
    }

    /**
     * Cargamos el menú en la base de datos, pero en varias pasadas.
     */
    private function check_menu() {
        if (file_exists(__DIR__)) {
            $max = 25;

            /// leemos todos los controladores del plugin
            foreach (scandir(__DIR__) as $f) {
                if ($f != '.' AND $f != '..' AND is_string($f) AND strlen($f) > 4 AND ! is_dir($f) AND $f != __CLASS__ . '.php') {
                    /// obtenemos el nombre
                    $page_name = substr($f, 0, -4);

                    /// lo buscamos en el menú
                    $encontrado = FALSE;
                    foreach ($this->menu as $m) {
                        if ($m->name == $page_name) {
                            $encontrado = TRUE;
                            break;
                        }
                    }

                    if (!$encontrado) {
                        require_once __DIR__ . '/' . $f;
                        $new_fsc = new $page_name();

                        if (!$new_fsc->page->save()) {
                            $this->new_error_msg("Imposible guardar la página " . $page_name);
                        }

                        unset($new_fsc);

                        if ($max > 0) {
                            $max--;
                        } else {
                            $this->recargar = TRUE;
                            $this->new_message('Instalando las entradas al menú para el plugin... &nbsp; <i class="fa fa-refresh fa-spin"></i>');
                            break;
                        }
                    }
                }
            }
        } else {
            $this->new_error_msg('No se encuentra el directorio ' . __DIR__);
        }

        $this->load_menu(TRUE);
    }
    
    public function get_config(){
        $fsvar = new fs_var();
        $this->rd_setup = $fsvar->array_get(
            array(
                'rd_imprimir_logo' => 'TRUE',
                'rd_imprimir_marca_agua' => 'TRUE',
                'rd_imprimir_bn' => 'FALSE',
                'rd_imprimir_cliente_box' => 'TRUE',
                'rd_imprimir_detalle_box' => 'TRUE',
                'rd_imprimir_detalle_lineas' => 'TRUE',
                'rd_imprimir_detalle_colores' => 'TRUE',
                'rd_imprimir_cabecera_fcolor' => '#000000',
                'rd_imprimir_cabecera_tcolor' => '#FFFFFF',
                'rd_imprimir_detalle_color' => '#dadada',
            ), FALSE
        );
    }

    public function impresion(){
        $fsvar = new fs_var();
        $op_imprimir_cabecera_fcolor = \filter_input(INPUT_POST, 'rd_imprimir_cabecera_fcolor');
        $op_imprimir_cabecera_tcolor = \filter_input(INPUT_POST, 'rd_imprimir_cabecera_tcolor');
        $op_imprimir_detalle_color = \filter_input(INPUT_POST, 'rd_imprimir_detalle_color');
        $imprimir_logo = (\filter_input(INPUT_POST, 'rd_imprimir_logo'))?'TRUE':'FALSE';
        $imprimir_marca_agua = (\filter_input(INPUT_POST, 'rd_imprimir_marca_agua'))?'TRUE':'FALSE';
        $imprimir_bn = (\filter_input(INPUT_POST, 'rd_imprimir_bn'))?'TRUE':'FALSE';
        $imprimir_cliente_box = (\filter_input(INPUT_POST, 'rd_imprimir_cliente_box'))?'TRUE':'FALSE';
        $imprimir_detalle_box = (\filter_input(INPUT_POST, 'rd_imprimir_detalle_box'))?'TRUE':'FALSE';
        $imprimir_detalle_lineas = (\filter_input(INPUT_POST, 'rd_imprimir_detalle_lineas'))?'TRUE':'FALSE';
        $imprimir_detalle_colores = (\filter_input(INPUT_POST, 'rd_imprimir_detalle_colores'))?'TRUE':'FALSE';
        $imprimir_cabecera_fcolor = ($op_imprimir_cabecera_fcolor)?$op_imprimir_cabecera_fcolor:'#dadada';
        $imprimir_cabecera_tcolor = ($op_imprimir_cabecera_tcolor)?$op_imprimir_cabecera_tcolor:'#dadada';
        $imprimir_detalle_color = ($op_imprimir_detalle_color)?$op_imprimir_detalle_color:'#dadada';
        $rd_config = array(
            'rd_imprimir_logo' => $imprimir_logo, 'rd_imprimir_marca_agua' => $imprimir_marca_agua,
            'rd_imprimir_bn' => $imprimir_bn, 'rd_imprimir_cliente_box' => $imprimir_cliente_box,
            'rd_imprimir_detalle_box' => $imprimir_detalle_box, 'rd_imprimir_detalle_lineas' => $imprimir_detalle_lineas,
            'rd_imprimir_detalle_colores' => $imprimir_detalle_colores, 
            'rd_imprimir_cabecera_fcolor' => $imprimir_cabecera_fcolor,
            'rd_imprimir_cabecera_tcolor' => $imprimir_cabecera_tcolor,
            'rd_imprimir_detalle_color' => $imprimir_detalle_color,
        );
        if ($fsvar->array_save($rd_config)) {
            $this->new_message('Opciones de impresión actualizadas correctamente.');
        }else{
            $this->new_error_msg('Ocurrió un error al intentar actualizar la información de impresión, por favor revise sus datos.');
        }
    }
    
    public function moneda() {
        $tratamiento = false;
        //Validamos si existe la moneda DOP
        $div0 = new divisa();
        $divisa1 = $div0->get('DOP');
        if (!$divisa1) {
            $div0->coddivisa = 'DOP';
            $div0->codiso = '214';
            $div0->descripcion = 'PESOS DOMINICANOS';
            $div0->simbolo = 'RD$';
            $div0->tasaconv = 45.15;
            $div0->tasaconv_compra = 45.90;
            $div0->save();
            $tratamiento = true;
        }
        //Validamos si existe la moneda USD
        //por temas de operaciones en dolares
        $divisa2 = $div0->get('USD');
        if (!$divisa2) {
            $div0->coddivisa = 'USD';
            $div0->codiso = '840';
            $div0->descripcion = 'DÓLARES EE.UU.';
            $div0->simbolo = '$';
            $div0->tasaconv = 1;
            $div0->tasaconv_compra = 1;
            $div0->save();
            $tratamiento = true;
        }

        if($tratamiento){
            $this->new_message('Datos de moneda DOP y USD actualizados correctamente.');
        }

        if ($this->empresa->coddivisa != 'DOP') {
            //Elegimos la divisa para la empresa como DOP si no esta generada
            $this->empresa->coddivisa = 'DOP';
            if ($this->empresa->save()) {
                $this->new_message('Datos de moneda para la empresa guardados correctamente.');
            }
        }
    }

    public function impuestos() {
        $tratamiento = false;
        $impuestos = new impuesto();
        //Eliminamos los Impuestos que no son de RD
        $lista_impuestos =array();
        foreach ($this->impuestos_rd as $imp) {
            $lista_impuestos[]=$imp['porcentaje'];
        }

        foreach ($impuestos->all() as $imp) {
            if(!in_array($imp->iva, $lista_impuestos)){
                $imp->delete();
            }
        }

        //Agregamos los Impuestos de RD
        foreach ($this->impuestos_rd as $imp) {
            if(!$impuestos->get_by_iva($imp['porcentaje'])){
                $imp0 = new impuesto();
                $imp0->codimpuesto = $imp['codigo'];
                $imp0->descripcion = $imp['descripcion'];
                $imp0->iva = $imp['porcentaje'];
                $imp0->recargo = $imp['recargo'];
                $imp0->codsubcuentasop = $imp['subcuenta_compras'];
                $imp0->codsubcuentarep = $imp['subcuenta_ventas'];
                if($imp0->save()){
                    $tratamiento = true;
                }
            }
        }

        //Corregimos la información de las Cuentas especiales con los nombres correctos
        $cuentas_especiales_rd['IVAACR']='Cuentas acreedoras de ITBIS en la regularización';
        $cuentas_especiales_rd['IVASOP']='Cuentas de ITBIS Compras';
        $cuentas_especiales_rd['IVARXP']='Cuentas de ITBIS exportaciones';
        $cuentas_especiales_rd['IVASIM']='Cuentas de ITBIS importaciones';
        $cuentas_especiales_rd['IVAREX']='Cuentas de ITBIS para clientes exentos';
        $cuentas_especiales_rd['IVAREP']='Cuentas de ITBIS Ventas';
        $cuentas_especiales = new cuenta_especial();
        foreach($cuentas_especiales_rd as $id=>$desc){
            $linea = $cuentas_especiales->get($id);
            if($linea->descripcion!==$desc){
                $linea->descripcion = $desc;
                $linea->save();
            }
        }

        if($tratamiento){
            $this->new_message('Información de impuestos actualizada correctamente');
        }else{
            $this->new_message('No se modificaron datos de impuestos previamente tratados.');
        }
    }

    public function pais() {
        $pais0 = new pais();
        $pais1 = $pais0->get('DOM');
        if (!$pais1) {
            $pais0->codpais = 'DOM';
            $pais0->codiso = 'DO';
            $pais0->nombre = 'República Dominicana';
            $pais0->save();
        }

        $pais2 = $pais0->get('USA');
        if (!$pais2) {
            $pais0->codpais = 'USA';
            $pais0->codiso = 'US';
            $pais0->nombre = 'Estados Unidos';
            $pais0->save();
        }

        $this->empresa->codpais = 'DOM';
        if ($this->empresa->save()) {
            $this->new_message('Datos guardados correctamente.');
        }
    }

    public function configuracion_regional() {
        //Configuramos la información básica para config2.ini
        $guardar = FALSE;
        foreach ($GLOBALS['config2'] as $i => $value) {
            if (isset($this->variables[$i])) {
                $GLOBALS['config2'][$i] = $this->variables[$i];
                $guardar = TRUE;
            }
        }

        if ($guardar) {
            $file = fopen('tmp/' . FS_TMP_NAME . 'config2.ini', 'w');
            if ($file) {
                foreach ($GLOBALS['config2'] as $i => $value) {
                    if (is_numeric($value)) {
                        fwrite($file, $i . " = " . $value . ";\n");
                    } else {
                        fwrite($file, $i . " = '" . $value . "';\n");
                    }
                }
                fclose($file);
            }
            $this->new_message('Datos de configuracion regional guardados correctamente.');
        }
    }

    private function share_extensions() {
        $fsext = new fs_extension();
        $fsext->name = 'pcgr_completo';
        $fsext->from = __CLASS__;
        $fsext->to = 'contabilidad_ejercicio';
        $fsext->type = 'fuente';
        $fsext->text = 'Plan Contable República Dominicana <strong>para industrias</strong>';
        $fsext->params = 'plugins/republica_dominicana/extras/rd_completo.xml';
        $fsext->save();

        $fsext->name = 'pcgr';
        $fsext->from = __CLASS__;
        $fsext->to = 'contabilidad_ejercicio';
        $fsext->type = 'fuente';
        $fsext->text = 'Plan Contable República Dominicana <strong>para pymes</strong>';
        $fsext->params = 'plugins/republica_dominicana/extras/rd_basico.xml';
        $fsext->save();

        $extensiones = array(
            array(
                'name' => '001_admin_rd_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="plugins/republica_dominicana/view/js/bootstrap-colorpicker.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_admin_rd_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/republica_dominicana/view/css/bootstrap-colorpicker.min.css"/>',
                'params' => ''
            ),
        );
        foreach($extensiones as $ext){
            $fext = new fs_extension($ext);
            if(!$fext->save()){
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }

    }

}
