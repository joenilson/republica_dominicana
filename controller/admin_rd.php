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

        $this->impuestos_rd = array(
            array('codigo' => 'ITBIS18', 'descripcion' => 'ITBIS 18%', 'porcentaje' => 18, 'recargo' => 0, 'subcuenta_compras' => '4011', 'subcuenta_ventas' => 4011),
            array('codigo' => 'ITBIS10', 'descripcion' => 'ITBIS 10%', 'porcentaje' => 10, 'recargo' => 0, 'subcuenta_compras' => '4011', 'subcuenta_ventas' => 4011),
            array('codigo' => 'ITBIS8', 'descripcion' => 'ITBIS 8%', 'porcentaje' => 8, 'recargo' => 0, 'subcuenta_compras' => '4011', 'subcuenta_ventas' => 4011),
            array('codigo' => 'EXENTO', 'descripcion' => 'EXENTO', 'porcentaje' => 0, 'recargo' => 0, 'subcuenta_compras' => '4011', 'subcuenta_ventas' => 4011)
        );

        if (isset($_GET['opcion'])) {
            if ($_GET['opcion'] == 'moneda') {
                $this->moneda();
            } else if ($_GET['opcion'] == 'impuestos') {
                $this->impuestos();
            } else if ($_GET['opcion'] == 'pais') {
                $this->pais();
            } else if ($_GET['opcion'] == 'configuracion_regional') {
                $this->configuracion_regional();
            }
        }

        $this->conf_divisa = ($this->empresa->coddivisa == 'DOP') ? TRUE : FALSE;
        $this->conf_pais = ($this->empresa->codpais == 'DOM') ? TRUE : FALSE;
        $this->conf_regional = ($GLOBALS['config2']['iva'] == 'ITBIS') ? TRUE : FALSE;
        $this->conf_impuestos = ($impuesto_empresa->get_by_iva(18)) ? TRUE : FALSE;
    }

    public function moneda() {
        $tratamiento = false;
        //Validamos si existe la moneda DOP
        $div0 = new divisa();
        $divisa = $div0->get('DOP');
        if (!$divisa) {
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
        $divisa = $div0->get('USD');
        if (!$divisa) {
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
        $pais = $pais0->get('DOM');
        if (!$pais) {
            $pais0->codpais = 'DOM';
            $pais0->codiso = 'DO';
            $pais0->nombre = 'República Dominicana';
            $pais0->save();
        }

        $pais = $pais0->get('USA');
        if (!$pais) {
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
        $fsext->params = FS_PATH.'plugins/republica_dominicana/extras/rd_completo.xml';
        $fsext->save();

        $fsext->name = 'pcgr';
        $fsext->from = __CLASS__;
        $fsext->to = 'contabilidad_ejercicio';
        $fsext->type = 'fuente';
        $fsext->text = 'Plan Contable República Dominicana <strong>para pymes</strong>';
        $fsext->params = FS_PATH.'plugins/republica_dominicana/extras/rd_basico.xml';
        $fsext->save();
    }

}
