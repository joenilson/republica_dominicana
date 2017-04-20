<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('agencia_transporte.php');
require_model('albaran_cliente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('fabricante.php');
require_model('factura_cliente.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_pedido_cliente.php');
require_model('pais.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');

class ventas_pedido extends fs_controller
{
   public $agencia;
   public $agente;
   public $allow_delete;
   public $almacen;
   public $cliente;
   public $cliente_s;
   public $divisa;
   public $ejercicio;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $historico;
   public $impuesto;
   public $nuevo_pedido_url;
   public $pais;
   public $pedido;
   public $serie;
   public $versiones;
   
   public $multi_almacen;   
   
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDO), 'ventas', FALSE, FALSE);
   }

   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->ppage = $this->page->get('ventas_pedidos');
      $this->agente = FALSE;

      $pedido = new pedido_cliente();
      $this->pedido = FALSE;
      $this->almacen = new almacen();
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->fabricante = new fabricante();
      $this->familia = new familia();
      $this->forma_pago = new forma_pago();
      $this->impuesto = new impuesto();
      $this->nuevo_pedido_url = FALSE;
      $this->pais = new pais();
      $this->serie = new serie();
      $this->agencia = new agencia_transporte();

      /// cargamos la configuración
      $fsvar = new fs_var();
      $this->multi_almacen = $fsvar->simple_get('multi_almacen');
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_venta,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('nueva_venta', FALSE) )
      {
         $nuevopedp = $this->page->get('nueva_venta');
         if($nuevopedp)
         {
            $this->nuevo_pedido_url = $nuevopedp->url();
         }
      }

      if( isset($_POST['idpedido']) )
      {
         $this->pedido = $pedido->get($_POST['idpedido']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->pedido = $pedido->get($_GET['id']);
      }

      if($this->pedido)
      {
         $this->page->title = $this->pedido->codigo;
         
         /// cargamos el agente
         if( !is_null($this->pedido->codagente) )
         {
            $agente = new agente();
            $this->agente = $agente->get($this->pedido->codagente);
         }

         /// cargamos el cliente
         $this->cliente_s = $this->cliente->get($this->pedido->codcliente);

         /// comprobamos el pedido
         if( $this->pedido->full_test() )
         {
            if( isset($_REQUEST['status']) )
            {
               $this->pedido->status = intval($_REQUEST['status']);
               
               if($this->pedido->status == 1 AND is_null($this->pedido->idalbaran))
               {
                  $this->generar_albaran();
               }
               elseif ($this->pedido->save())
               {
                  $this->new_message(ucfirst(FS_PEDIDO)." modificado correctamente.");
               }
               else
               {
                  $this->new_error_msg("¡Imposible modificar el ".FS_PEDIDO."!");
               }
            }
            else if( isset($_GET['nversion']) )
            {
               $this->nueva_version();
            }
            else if( isset($_GET['nversionok']) )
            {
               $this->new_message('Esta es la nueva versión del '.FS_PEDIDO.'.');
            }
         }
         
         $this->versiones = $this->pedido->get_versiones();
         $this->get_historico();
      }
      else
      {
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " de cliente no encontrado!", 'error', FALSE, FALSE);
      }
   }
   
   private function nueva_version()
   {
      $pedi = clone $this->pedido;
      $pedi->idpedido = NULL;
      $pedi->idalbaran = NULL;
      $pedi->fecha = $this->today();
      $pedi->hora = $this->hour();
      $pedi->femail = NULL;
      $pedi->status = 0;
      $pedi->numdocs = 0;
      
      $pedi->idoriginal = $this->pedido->idpedido;
      if($this->pedido->idoriginal)
      {
         $pedi->idoriginal = $this->pedido->idoriginal;
      }
      
      /// enlazamos con el ejercicio correcto
      $ejercicio = $this->ejercicio->get_by_fecha($pedi->fecha);
      if($ejercicio)
      {
         $pedi->codejercicio = $ejercicio->codejercicio;
      }
      
      if( $pedi->save() )
      {
         /// también copiamos las líneas del presupuesto
         foreach($this->pedido->get_lineas() as $linea)
         {
            $newl = clone $linea;
            $newl->idlinea = NULL;
            $newl->idpedido = $pedi->idpedido;
            $newl->save();
         }
         
         $this->new_message('<a href="' . $pedi->url() . '">Documento</a> de ' . FS_PEDIDO . ' copiado correctamente.');
         header('Location: '.$pedi->url().'&nversionok=TRUE');
      }
      else
      {
         $this->new_error_msg('Error al copiar el documento.');
      }
   }

   public function url()
   {
      if (!isset($this->pedido))
      {
         return parent::url();
      }
      else if ($this->pedido)
      {
         return $this->pedido->url();
      }
      else
         return $this->page->url();
   }

   private function modificar()
   {
      $this->pedido->observaciones = $_POST['observaciones'];
      $this->pedido->numero2 = $_POST['numero2'];
      
      /// ¿El pedido es editable o ya ha sido aprobado?
      if($this->pedido->editable)
      {
         $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha'], FALSE);
         if($eje0)
         {
            $this->pedido->fecha = $_POST['fecha'];
            $this->pedido->hora = $_POST['hora'];
            
            $this->pedido->fechasalida = NULL;
            if($_POST['fechasalida'] != '')
            {
               $this->pedido->fechasalida = $_POST['fechasalida'];
            }
            
            if($this->pedido->codejercicio != $eje0->codejercicio)
            {
               $this->pedido->codejercicio = $eje0->codejercicio;
               $this->pedido->new_codigo();
            }
         }
         else
         {
            $this->new_error_msg('Ningún ejercicio encontrado.');
         }
         
         /// ¿cambiamos el cliente?
         if($_POST['cliente'] != $this->pedido->codcliente)
         {
            $cliente = $this->cliente->get($_POST['cliente']);
            if($cliente)
            {
               $this->pedido->codcliente = $cliente->codcliente;
               $this->pedido->cifnif = $cliente->cifnif;
               $this->pedido->nombrecliente = $cliente->razonsocial;
               $this->pedido->apartado = NULL;
               $this->pedido->ciudad = NULL;
               $this->pedido->coddir = NULL;
               $this->pedido->codpais = NULL;
               $this->pedido->codpostal = NULL;
               $this->pedido->direccion = NULL;
               $this->pedido->provincia = NULL;
               
               foreach($cliente->get_direcciones() as $d)
               {
                  if($d->domfacturacion)
                  {
                     $this->pedido->apartado = $d->apartado;
                     $this->pedido->ciudad = $d->ciudad;
                     $this->pedido->coddir = $d->id;
                     $this->pedido->codpais = $d->codpais;
                     $this->pedido->codpostal = $d->codpostal;
                     $this->pedido->direccion = $d->direccion;
                     $this->pedido->provincia = $d->provincia;
                     break;
                  }
               }
            }
            else
            {
               $this->pedido->codcliente = NULL;
               $this->pedido->nombrecliente = $_POST['nombrecliente'];
               $this->pedido->cifnif = $_POST['cifnif'];
               $this->pedido->coddir = NULL;
            }
         }
         else
         {
            $this->pedido->nombrecliente = $_POST['nombrecliente'];
            $this->pedido->cifnif = $_POST['cifnif'];
            $this->pedido->codpais = $_POST['codpais'];
            $this->pedido->provincia = $_POST['provincia'];
            $this->pedido->ciudad = $_POST['ciudad'];
            $this->pedido->codpostal = $_POST['codpostal'];
            $this->pedido->direccion = $_POST['direccion'];
            $this->pedido->apartado = $_POST['apartado'];
            
            $this->pedido->envio_nombre = $_POST['envio_nombre'];
            $this->pedido->envio_apellidos = $_POST['envio_apellidos'];
            $this->pedido->envio_codtrans = NULL;
            if($_POST['envio_codtrans'] != '')
            {
               $this->pedido->envio_codtrans = $_POST['envio_codtrans'];
            }
            $this->pedido->envio_codigo = $_POST['envio_codigo'];
            $this->pedido->envio_codpais = $_POST['envio_codpais'];
            $this->pedido->envio_provincia = $_POST['envio_provincia'];
            $this->pedido->envio_ciudad = $_POST['envio_ciudad'];
            $this->pedido->envio_codpostal = $_POST['envio_codpostal'];
            $this->pedido->envio_direccion = $_POST['envio_direccion'];
            $this->pedido->envio_apartado = $_POST['envio_apartado'];
            
            $cliente = $this->cliente->get($this->pedido->codcliente);
         }

         $serie = $this->serie->get($this->pedido->codserie);

         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->pedido->codserie)
         {
            $serie2 = $this->serie->get($_POST['serie']);
            if($serie2)
            {
               $this->pedido->codserie = $serie2->codserie;
               $this->pedido->new_codigo();

               $serie = $serie2;
            }
         }
         
         $this->pedido->codalmacen = $_POST['almacen'];
         $this->pedido->codpago = $_POST['forma_pago'];
         
         /// ¿Cambiamos la divisa?
         if($_POST['divisa'] != $this->pedido->coddivisa)
         {
            $divisa = $this->divisa->get($_POST['divisa']);
            if($divisa)
            {
               $this->pedido->coddivisa = $divisa->coddivisa;
               $this->pedido->tasaconv = $divisa->tasaconv;
            }
         }
         else if($_POST['tasaconv'] != '')
         {
            $this->pedido->tasaconv = floatval($_POST['tasaconv']);
         }
         
         if( isset($_POST['numlineas']) )
         {
            $numlineas = intval($_POST['numlineas']);

            $this->pedido->neto = 0;
            $this->pedido->totaliva = 0;
            $this->pedido->totalirpf = 0;
            $this->pedido->totalrecargo = 0;
            $this->pedido->irpf = 0;
            
            $lineas = $this->pedido->get_lineas();
            $articulo = new articulo();

            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= $numlineas; $num++)
               {
                  if( isset($_POST['idlinea_' . $num]) )
                  {
                     if($l->idlinea == intval($_POST['idlinea_' . $num]))
                     {
                        $encontrada = TRUE;
                        break;
                     }
                  }
               }
               if(!$encontrada)
               {
                  if( !$l->delete() )
                  {
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $l->referencia . "!");
                  }
               }
            }
            
            $regimeniva = 'general';
            if($cliente)
            {
               $regimeniva = $cliente->regimeniva;
            }
            
            /// modificamos y/o añadimos las demás líneas
            for($num = 0; $num <= $numlineas; $num++)
            {
               $encontrada = FALSE;
               if( isset($_POST['idlinea_' . $num]) )
               {
                  foreach($lineas as $k => $value)
                  {
                     /// modificamos la línea
                     if($value->idlinea == intval($_POST['idlinea_' . $num]))
                     {
                        $encontrada = TRUE;
                        $lineas[$k]->cantidad = floatval($_POST['cantidad_' . $num]);
                        $lineas[$k]->pvpunitario = floatval($_POST['pvp_' . $num]);
                        $lineas[$k]->dtopor = floatval($_POST['dto_' . $num]);
                        $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                        $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor) / 100);
                        $lineas[$k]->descripcion = $_POST['desc_' . $num];

                        $lineas[$k]->codimpuesto = NULL;
                        $lineas[$k]->iva = 0;
                        $lineas[$k]->recargo = 0;
                        $lineas[$k]->irpf = floatval($_POST['irpf_' . $num]);
                        if(!$serie->siniva AND $regimeniva != 'Exento')
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                           if($imp0)
                           {
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                           }

                           $lineas[$k]->iva = floatval($_POST['iva_' . $num]);
                           $lineas[$k]->recargo = floatval($_POST['recargo_' . $num]);
                        }

                        if( $lineas[$k]->save() )
                        {
                           $this->pedido->neto += $value->pvptotal;
                           $this->pedido->totaliva += $value->pvptotal * $value->iva / 100;
                           $this->pedido->totalirpf += $value->pvptotal * $value->irpf / 100;
                           $this->pedido->totalrecargo += $value->pvptotal * $value->recargo / 100;
                           
                           if($value->irpf > $this->pedido->irpf)
                           {
                              $this->pedido->irpf = $value->irpf;
                           }
                        }
                        else
                           $this->new_error_msg("¡Imposible modificar la línea del artículo " . $value->referencia . "!");
                        
                        break;
                     }
                  }

                  /// añadimos la línea
                  if(!$encontrada AND intval($_POST['idlinea_' . $num]) == -1 AND isset($_POST['referencia_' . $num]))
                  {
                     $linea = new linea_pedido_cliente();
                     $linea->idpedido = $this->pedido->idpedido;
                     $linea->descripcion = $_POST['desc_' . $num];
                     
                     if(!$serie->siniva AND $regimeniva != 'Exento')
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                        }
                        
                        $linea->iva = floatval($_POST['iva_' . $num]);
                        $linea->recargo = floatval($_POST['recargo_' . $num]);
                     }
                     
                     $linea->irpf = floatval($_POST['irpf_'.$num]);
                     $linea->cantidad = floatval($_POST['cantidad_' . $num]);
                     $linea->pvpunitario = floatval($_POST['pvp_' . $num]);
                     $linea->dtopor = floatval($_POST['dto_' . $num]);
                     $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                     $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100);
                     
                     $art0 = $articulo->get($_POST['referencia_' . $num]);
                     if($art0)
                     {
                        $linea->referencia = $art0->referencia;
                        if($_POST['codcombinacion_' . $num])
                        {
                           $linea->codcombinacion = $_POST['codcombinacion_' . $num];
                        }
                     }
                     
                     if( $linea->save() )
                     {
                        $this->pedido->neto += $linea->pvptotal;
                        $this->pedido->totaliva += $linea->pvptotal * $linea->iva / 100;
                        $this->pedido->totalirpf += $linea->pvptotal * $linea->irpf / 100;
                        $this->pedido->totalrecargo += $linea->pvptotal * $linea->recargo / 100;
                        
                        if($linea->irpf > $this->pedido->irpf)
                        {
                           $this->pedido->irpf = $linea->irpf;
                        }
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
                  }
               }
            }

            /// redondeamos
            $this->pedido->neto = round($this->pedido->neto, FS_NF0);
            $this->pedido->totaliva = round($this->pedido->totaliva, FS_NF0);
            $this->pedido->totalirpf = round($this->pedido->totalirpf, FS_NF0);
            $this->pedido->totalrecargo = round($this->pedido->totalrecargo, FS_NF0);
            $this->pedido->total = $this->pedido->neto + $this->pedido->totaliva - $this->pedido->totalirpf + $this->pedido->totalrecargo;

            if (abs(floatval($_POST['atotal']) - $this->pedido->total) >= .02)
            {
               $this->new_error_msg("El total difiere entre el controlador y la vista (" . $this->pedido->total .
                       " frente a " . $_POST['atotal'] . "). Debes informar del error.");
            }
         }
      }

      if( $this->pedido->save() )
      {
         $this->new_message(ucfirst(FS_PEDIDO) . " modificado correctamente.");
         $this->new_change(ucfirst(FS_PEDIDO) . ' Cliente ' . $this->pedido->codigo, $this->pedido->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PEDIDO . "!");
   }

   private function generar_albaran()
   {
      $albaran = new albaran_cliente();
      $albaran->apartado = $this->pedido->apartado;
      $albaran->cifnif = $this->pedido->cifnif;
      $albaran->ciudad = $this->pedido->ciudad;
      $albaran->codagente = $this->pedido->codagente;
      $albaran->codalmacen = $this->pedido->codalmacen;
      $albaran->codcliente = $this->pedido->codcliente;
      $albaran->coddir = $this->pedido->coddir;
      $albaran->coddivisa = $this->pedido->coddivisa;
      $albaran->tasaconv = $this->pedido->tasaconv;
      $albaran->codpago = $this->pedido->codpago;
      $albaran->codpais = $this->pedido->codpais;
      $albaran->codpostal = $this->pedido->codpostal;
      $albaran->codserie = $this->pedido->codserie;
      $albaran->direccion = $this->pedido->direccion;
      $albaran->neto = $this->pedido->neto;
      $albaran->nombrecliente = $this->pedido->nombrecliente;
      $albaran->observaciones = $this->pedido->observaciones;
      $albaran->provincia = $this->pedido->provincia;
      $albaran->total = $this->pedido->total;
      $albaran->totaliva = $this->pedido->totaliva;
      $albaran->numero2 = $this->pedido->numero2;
      $albaran->irpf = $this->pedido->irpf;
      $albaran->porcomision = $this->pedido->porcomision;
      $albaran->totalirpf = $this->pedido->totalirpf;
      $albaran->totalrecargo = $this->pedido->totalrecargo;
      
      $albaran->envio_nombre = $this->pedido->envio_nombre;
      $albaran->envio_apellidos = $this->pedido->envio_apellidos;
      $albaran->envio_codtrans = $this->pedido->envio_codtrans;
      $albaran->envio_codigo = $this->pedido->envio_codigo;
      $albaran->envio_codpais = $this->pedido->envio_codpais;
      $albaran->envio_provincia = $this->pedido->envio_provincia;
      $albaran->envio_ciudad = $this->pedido->envio_ciudad;
      $albaran->envio_codpostal = $this->pedido->envio_codpostal;
      $albaran->envio_direccion = $this->pedido->envio_direccion;
      $albaran->envio_apartado = $this->pedido->envio_apartado;
      
      if( isset($_POST['facturar']) )
      {
         $albaran->fecha = $_POST['facturar'];
      }
      
      if( is_null($albaran->codagente) )
      {
         $albaran->codagente = $this->user->codagente;
      }
      
      /**
       * Obtenemos el ejercicio para la fecha de hoy (puede que
       * no sea el mismo ejercicio que el del pedido, por ejemplo
       * si hemos cambiado de año)
       */
      $eje0 = $this->ejercicio->get_by_fecha($albaran->fecha, FALSE);
      if($eje0)
      {
         $albaran->codejercicio = $eje0->codejercicio;
      }

      if(!$eje0)
      {
         $this->new_error_msg("Ejercicio no encontrado.");
      }
      else if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $albaran->save() )
      {
         $art0 = new articulo();
         $continuar = TRUE;
         $trazabilidad = FALSE;
         $stock0 = new stock();
         
         foreach($this->pedido->get_lineas() as $l)
         {
            $n = new linea_albaran_cliente();
            $n->idlineapedido = $l->idlinea;
            $n->idpedido = $l->idpedido;
            $n->idalbaran = $albaran->idalbaran;
            $n->cantidad = $l->cantidad;
            $n->codimpuesto = $l->codimpuesto;
            $n->descripcion = $l->descripcion;
            $n->dtopor = $l->dtopor;
            $n->irpf = $l->irpf;
            $n->iva = $l->iva;
            $n->pvpsindto = $l->pvpsindto;
            $n->pvptotal = $l->pvptotal;
            $n->pvpunitario = $l->pvpunitario;
            $n->recargo = $l->recargo;
            $n->referencia = $l->referencia;
            $n->codcombinacion = $l->codcombinacion;
            $n->orden = $l->orden;
            $n->mostrar_cantidad = $l->mostrar_cantidad;
            $n->mostrar_precio = $l->mostrar_precio;

            if( $n->save() )
            {
               /// descontamos del stock
               if($n->referencia)
               {
                  $articulo = $art0->get($n->referencia);
                  if($articulo)
                  {
                     $stockfis = $articulo->stockfis;
                     if($this->multi_almacen)
                     {
                        $stockfis = $stock0->total_from_articulo($articulo->referencia, $albaran->codalmacen);
                     }
                     
                     if( !$articulo->controlstock AND $n->cantidad > $stockfis )
                     {
                        $this->new_error_msg("No hay suficiente stock del artículo <b>".$n->referencia.'</b>.');
                        $n->delete();
                        $continuar = FALSE;
                     }
                     else
                     {
                         /// descontamos del stock
                         $articulo->sum_stock($albaran->codalmacen, 0 - $n->cantidad, FALSE, $n->codcombinacion);
                         if($articulo->trazabilidad)
                         {
                            $trazabilidad = TRUE;
                         }                         
                     }
                  }
               }
            }
            else
            {
               $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
               $continuar = FALSE;
            }
         }

         if($continuar)
         {
            $this->pedido->idalbaran = $albaran->idalbaran;
            $this->pedido->fechasalida = $albaran->fecha;
            
            if( $this->pedido->save() )
            {
               $this->new_message("<a href='" . $albaran->url() . "'>" . ucfirst(FS_ALBARAN) . '</a> generado correctamente.');
               
               if($trazabilidad)
               {
                  header('Location: index.php?page=ventas_trazabilidad&doc=albaran&id='.$albaran->idalbaran);
               }
               else if( isset($_POST['facturar']) )
               {
                  header('Location: '.$albaran->url().'&facturar='.$_POST['facturar'].'&petid='.$this->random_string());
               }
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el ".FS_PEDIDO." con el nuevo " . FS_ALBARAN . "!");
               if( $albaran->delete() )
               {
                  $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
            }
         }
         else
         {
            if( $albaran->delete() )
            {
               $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el " . FS_ALBARAN . "!");
   }
   
   public function get_lineas_stock()
   {
      $lineas = array();
      
      $sql = "SELECT l.referencia,l.descripcion,l.cantidad,s.cantidad as stock,s.ubicacion FROM lineaspedidoscli l"
              . " LEFT JOIN stocks s ON l.referencia = s.referencia"
              . " AND s.codalmacen = ".$this->pedido->var2str($this->pedido->codalmacen)
              . " WHERE l.idpedido = ".$this->pedido->var2str($this->pedido->idpedido)
              . " ORDER BY referencia ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         $art0 = new articulo();
         
         foreach($data as $d)
         {
            $articulo = $art0->get($d['referencia']);
            if($articulo)
            {
               $lineas[] = array(
                   'articulo' => $articulo,
                   'descripcion' => $d['descripcion'],
                   'cantidad' => floatval($d['cantidad']),
                   'stock' => floatval($d['stock']),
                   'ubicacion' => $d['ubicacion']
               );
            }
         }
      }
      
      return $lineas;
   }
   
   private function get_historico()
   {
      $this->historico = array();
      $orden = 0;
      
      /// presupuestos
      $sql = "SELECT * FROM presupuestoscli WHERE idpedido = ".$this->pedido->var2str($this->pedido->idpedido)
              ." ORDER BY idpresupuesto ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $presupuesto = new presupuesto_cliente($d);
            $this->historico[] = array(
                'orden' => $orden,
                'documento' => FS_PRESUPUESTO,
                'modelo' => $presupuesto
            );
            $orden++;
         }
      }
      
      if($this->pedido->idalbaran)
      {
         $sql1 = "SELECT * FROM albaranescli WHERE idalbaran = ".$this->pedido->var2str($this->pedido->idalbaran)
                 ." ORDER BY idalbaran ASC;";
         
         $data1 = $this->db->select($sql1);
         if($data1)
         {
            foreach($data1 as $d1)
            {
               $albaran = new albaran_cliente($d1);
               $this->historico[] = array(
                   'orden' => $orden,
                   'documento' => FS_ALBARAN,
                   'modelo' => $albaran
               );
               $orden++;
               
               if($albaran->idfactura)
               {
                  $sql2 = "SELECT * FROM facturascli WHERE idfactura = ".$albaran->var2str($albaran->idfactura)
                          ." ORDER BY idfactura ASC;";
                  
                  $data2 = $this->db->select($sql2);
                  if($data2)
                  {
                     foreach($data2 as $d2)
                     {
                        $factura = new factura_cliente($d2);
                        $this->historico[] = array(
                            'orden' => $orden,
                            'documento' => 'factura',
                            'modelo' => $factura
                        );
                        $orden++;
                     }
                  }
               }
            }
         }
      }
   }
}
