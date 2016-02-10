<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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

require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('albaran_cliente.php');
require_model('fabricante.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_pedido_cliente.php');
require_model('pais.php');
require_model('pedido_cliente.php');
require_model('serie.php');

class ventas_pedido extends fs_controller
{
   public $agente;
   public $allow_delete;
   public $cliente;
   public $cliente_s;
   public $divisa;
   public $ejercicio;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $nuevo_pedido_url;
   public $pais;
   public $pedido;
   public $serie;

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
         }
      }
      else
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " de cliente no encontrado!");
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

      if($this->pedido->editable)
      {
         $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha'], FALSE);
         if(!$eje0)
         {
            $this->new_error_msg('Ningún ejercicio encontrado.');
         }
         else
         {
            $this->pedido->fecha = $_POST['fecha'];
            $this->pedido->hora = $_POST['hora'];

            $this->pedido->fechasalida = NULL;
            if($_POST['fechasalida'] != '')
            {
               $this->pedido->fechasalida = $_POST['fechasalida'];
            }
         }

         /// ¿cambiamos el cliente?
         if($_POST['cliente'] != $this->pedido->codcliente)
         {
            $cliente = $this->cliente->get($_POST['cliente']);
            if($cliente)
            {
               foreach($cliente->get_direcciones() as $d)
               {
                  if($d->domfacturacion)
                  {
                     $this->pedido->codcliente = $cliente->codcliente;
                     $this->pedido->cifnif = $cliente->cifnif;
                     $this->pedido->nombrecliente = $cliente->razonsocial;
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
               die('No se ha encontrado el cliente.');
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
                        if(!$serie->siniva AND $cliente->regimeniva != 'Exento')
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

                     if(!$serie->siniva AND $cliente->regimeniva != 'Exento')
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

      if( isset($_POST['facturar']) )
      {
         $albaran->fecha = $_POST['facturar'];
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
         $continuar = TRUE;
         $art0 = new articulo();

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

            if( $n->save() )
            {
               /// descontamos del stock
               if( !is_null($n->referencia) )
               {
                  $articulo = $art0->get($n->referencia);
                  if($articulo)
                  {
                     $articulo->sum_stock($albaran->codalmacen, 0 - $l->cantidad);
                  }
               }
            }
            else
            {
               $continuar = FALSE;
               $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
               break;
            }
         }

         if($continuar)
         {
            $this->pedido->idalbaran = $albaran->idalbaran;
            $this->pedido->fechasalida = $albaran->fecha;

            if( $this->pedido->save() )
            {
               $this->new_message("<a href='" . $albaran->url() . "'>" . ucfirst(FS_ALBARAN) . '</a> generado correctamente.');

               if( isset($_POST['facturar']) )
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

      $sql = "SELECT l.referencia,l.descripcion,l.cantidad,s.cantidad as stock,s.ubicacion FROM lineaspedidoscli l, stocks s"
              . " WHERE l.idpedido = ".$this->pedido->var2str($this->pedido->idpedido)
              . " AND l.referencia = s.referencia"
              . " AND s.codalmacen = ".$this->pedido->var2str($this->pedido->codalmacen)
              . " ORDER BY referencia ASC;;";
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
}
