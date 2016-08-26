<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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

/**
 * Description of admin_peru
 *
 * @author carlos
 */
class admin_rd extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'República Dominicana', 'admin');
   }
   
   protected function private_core()
   {
      $this->share_extensions();
      
      $variables=array();
      $variables['zona_horaria']="America/Santo_Domingo";
      $variables['nf0']="2";
      $variables['nf0_art']="4";
      $variables['nf1']=".";
      $variables['nf2']=",";
      $variables['pos_divisa']="left";
      $variables['factura']="factura";
      $variables['facturas']="facturas";
      $variables['factura_simplificada']="factura simplificada";
      $variables['factura_rectificativa']="nota de credito";
      $variables['albaran']="conduce";
      $variables['albaranes']="conduces";
      $variables['pedido']="pedido";
      $variables['pedidos']="pedidos";
      $variables['presupuesto']="presupuesto";
      $variables['presupuestos']="presupuestos";
      $variables['provincia']="provincia";
      $variables['apartado']="apartado";
      $variables['cifnif']="Cedula/RNC";
      $variables['iva']="ITBIS";
      $variables['numero2']="NCF";
      $variables['serie']="serie";
      $variables['series']="series";
      
      
      if( isset($_GET['opcion']) )
      {
         if($_GET['opcion'] == 'moneda')
         {
            //Validamos si existe la moneda DOP
            $div0 = new divisa();
            $divisa = $div0->get('DOP');
            if(!$divisa)
            {
               $div0->coddivisa = 'DOP';
               $div0->codiso = '214';
               $div0->descripcion = 'PESOS DOMINICANOS';
               $div0->simbolo = 'RD$';
               $div0->tasaconv = 45.15;
               $div0->tasaconv_compra = 45.90;
               $div0->save();
            }
            //Validamos si existe la moneda USD
            //por temas de operaciones en dolares
            $divisa = $div0->get('USD');
            if(!$divisa)
            {
               $div0->coddivisa = 'USD';
               $div0->codiso = '840';
               $div0->descripcion = 'DÓLARES EE.UU.';
               $div0->simbolo = '$';
               $div0->tasaconv = 1;
               $div0->tasaconv_compra = 1;
               $div0->save();
            }
            //Elegimos la divisa para la empresa como DOP
            $this->empresa->coddivisa = 'DOP';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
         }
         else if($_GET['opcion'] == 'pais')
         {
            $pais0 = new pais();
            $pais = $pais0->get('DOM');
            if(!$pais)
            {
               $pais0->codpais = 'DOM';
               $pais0->codiso = 'DO';
               $pais0->nombre = 'República Dominicana';
               $pais0->save();
            }
            
            $pais = $pais0->get('USA');
            if(!$pais)
            {
               $pais0->codpais = 'USA';
               $pais0->codiso = 'US';
               $pais0->nombre = 'Estados Unidos';
               $pais0->save();
            }
            
            $this->empresa->codpais = 'DOM';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
         }
         else if($_GET['opcion'] == 'configuracion_regional'){
            //Configuramos la información básica para config2.ini
            $guardar = FALSE;
            foreach($GLOBALS['config2'] as $i => $value)
            {
               if( isset($variables[$i]) )
               {
                  $GLOBALS['config2'][$i] = $variables[$i];
                  $guardar = TRUE;
               }
            }

            if($guardar)
            {
               $file = fopen('tmp/'.FS_TMP_NAME.'config2.ini', 'w');
               if($file)
               {
                  foreach($GLOBALS['config2'] as $i => $value)
                  {
                     if( is_numeric($value) )
                     {
                        fwrite($file, $i." = ".$value.";\n");
                     }
                     else
                     {
                        fwrite($file, $i." = '".$value."';\n");
                     }
                  }
                  fclose($file);
               }
               $this->new_message('Datos de configuracion regional guardados correctamente.');
            }
         }
      }
   }
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'pcgr';
      $fsext->from = __CLASS__;
      $fsext->to = 'contabilidad_ejercicio';
      $fsext->type = 'fuente';
      $fsext->text = 'Plan Contable República Dominicana';
      $fsext->params = 'plugins/republica_dominicana/extras/republica_dominicana.xml';
      $fsext->save();
   }
}
