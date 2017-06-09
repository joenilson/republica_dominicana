<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Valentín González    valengon@hotmail.com
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
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

//require_once 'plugins/republica_dominicana/fpdf17/fs_fpdf.php';
//define('FPDF_FONTPATH', 'plugins/republica_dominicana/fpdf17/font/');
require_once 'plugins/facturacion_base/extras/fs_pdf.php';
//define('EEURO', chr(128));
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('articulo.php');
require_model('articulo_traza.php');
require_model('divisa.php');
require_model('pais.php');
require_model('forma_pago.php');
require_model('ncf_ventas.php');
require_model('ncf_tipo.php');
require_model('ncf_entidad_tipo.php');
require_model('agente.php');

//Compatibilidad con plugin distribucion
require_model('distribucion_ordenescarga_facturas.php');

require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';

class factura_ncf extends fs_controller {

    public $cliente;
    public $documento;
    public $ncf_ventas;
    public $distrib_transporte;
    public $idtransporte;
    public $archivo;
    public $rd_setup;
    public $agente;
    public $numpaginas;
    public $impresion;
    public $articulo_traza;
    public $impuesto;

    public function __construct() {
        parent::__construct(__CLASS__, 'Factura NCF', 'ventas', FALSE, FALSE);
    }

    protected function private_core() {
        $this->template = false;
        $this->share_extensions();
        $this->impuesto = new impuesto();
        $fsvar = new fs_var();
        /// obtenemos los datos de configuración de impresión globales
        $this->impresion = array(
            'print_ref' => '1',
            'print_dto' => '1',
            'print_alb' => '0',
            'print_formapago' => '1'
        );

        $this->impresion = $fsvar->array_get($this->impresion, FALSE);
        //Obtenemos las configuraciones de impresión de las facturas de RD
        $this->rd_setup = $fsvar->array_get(
                array(
            'rd_imprimir_logo' => 'TRUE',
            'rd_imprimir_marca_agua' => 'TRUE',
            'rd_imprimir_bn' => 'FALSE',
                ), FALSE
        );

        $val_id = \filter_input(INPUT_GET, 'id');
        $solicitud = \filter_input(INPUT_GET, 'solicitud');
        $valores_id = explode(',', $val_id);
        if (class_exists('distribucion_ordenescarga_facturas')) {
            $this->distrib_transporte = new distribucion_ordenescarga_facturas();
        }

        if (class_exists('agente')) {
            $this->agente = new agente();
        }

        if (!empty($valores_id[0]) AND $solicitud == 'imprimir') {
            $this->articulo_traza = new articulo_traza();
            $this->procesar_facturas($valores_id);
        } elseif (!empty($valores_id[0]) AND $solicitud == 'email') {
            $this->articulo_traza = new articulo_traza();
            $this->enviar_email($valores_id[0]);
        }
    }

    // Corregir el Bug de fpdf con el Simbolo del Euro ---> €
    public function ckeckEuro($cadena) {
        $mostrar = $this->show_precio($cadena, $this->documento->coddivisa);
        $pos = strpos($mostrar, '€');
        if ($pos !== false) {
            if (FS_POS_DIVISA == 'right') {
                return number_format($cadena, FS_NF0, FS_NF1, FS_NF2) . ' ' . EEURO;
            } else {
                return EEURO . ' ' . number_format($cadena, FS_NF0, FS_NF1, FS_NF2);
            }
        }
        return $mostrar;
    }

    public function procesar_facturas($valores_id, $archivo = FALSE) {
        if (!empty($valores_id)) {
            if (ob_get_status()) {
                ob_end_clean();
            }
            $cantidad = count($valores_id);
            /*
              $pdf_doc = new PDF_MC_Table('P', 'mm', 'letter');
              $pdf_doc->SetTitle('Facturas de Venta' );
              $pdf_doc->SetSubject('Facturas de Venta para clientes');
              $pdf_doc->SetAuthor($this->empresa->nombre);
              $pdf_doc->SetCreator('FacturaSctipts V_' . $this->version());
             */
            $this->archivo = $archivo;
            $contador = 0;
            $this->documento = FALSE;
            foreach ($valores_id as $id) {
                $factura = new factura_cliente();
                $this->documento = $factura->get($id);
                /// Creamos el PDF y escribimos sus metadatos
                if ($contador == 0) {
                    $pdf_doc = new fs_pdf('letter');
                    $pdf_doc->pdf->addInfo('Title', ($cantidad == 1) ? ucfirst(FS_FACTURA) . ' ' . $this->documento->codigo : 'Facturas de Venta');
                    $pdf_doc->pdf->addInfo('Subject', ($cantidad == 1) ? ucfirst(FS_FACTURA) . ' ' . $this->documento->codigo : 'Facturas de Venta para clientes');
                    $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
                    $pdf_doc->pdf->addInfo('Creator', 'FacturaSctipts V_' . $this->version());
                }
                if ($this->documento) {
                    $ncf_datos = new ncf_ventas();
                    $valores = $ncf_datos->get_ncf($this->empresa->id, $this->documento->idfactura, $this->documento->codcliente, $this->documento->fecha);
                    $ncf_tipo = new ncf_tipo();
                    $tipo_comprobante = $ncf_tipo->get($valores->tipo_comprobante);
                    $this->documento->ncf = $valores->ncf;
                    $this->documento->ncf_afecta = $valores->ncf_modifica;
                    $this->documento->estado = $valores->estado;
                    $this->documento->tipo_comprobante = $tipo_comprobante->descripcion;
                    if ($this->distrib_transporte) {
                        $transporte = $this->distrib_transporte->get($this->empresa->id, $this->documento->idfactura, $this->documento->codalmacen);
                        $this->idtransporte = (isset($transporte[0]->idtransporte)) ? str_pad($transporte[0]->idtransporte, 10, "0", STR_PAD_LEFT) : false;
                    }
                    $cliente = new cliente();
                    $this->cliente = $cliente->get($this->documento->codcliente);
                    $this->generar_pdf_factura($pdf_doc);
                    $contador++;
                }
            }
            // Damos salida al archivo PDF
            if ($this->archivo) {
                if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                    mkdir('tmp/' . FS_TMP_NAME . 'enviar');
                }
                $pdf_doc->save('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
            } else {
                $pdf_doc->show(FS_FACTURA . '_' . $this->documento->codigo . '.pdf');
            }

            if (!$this->documento) {
                $this->new_error_msg("¡Factura de cliente no encontrada!");
            }
        }
    }

    private function enviar_email($doc, $tipo = 'ncf') {
        $factura = new factura_cliente();
        $factura_enviar = $factura->get($doc);
        if ($this->empresa->can_send_mail()) {
            if ($_POST['email'] != $this->cliente->email AND isset($_POST['guardar'])) {
                $this->cliente->email = $_POST['email'];
                $this->cliente->save();
            }

            $filename = 'factura_' . $factura_enviar->numero2 . '.pdf';
            if ($tipo == 'ncf') {
                $this->procesar_facturas(array($factura_enviar->idfactura), $filename);
            }


            if (file_exists('tmp/' . FS_TMP_NAME . 'enviar/' . $filename)) {
                $mail = $this->empresa->new_mail();
                $mail->FromName = $this->user->get_agente_fullname();
                $mail->addReplyTo($_POST['de'], $mail->FromName);

                $mail->addAddress($_POST['email'], $this->cliente->razonsocial);
                if ($_POST['email_copia']) {
                    if (isset($_POST['cco'])) {
                        $mail->addBCC($_POST['email_copia'], $this->cliente->razonsocial);
                    } else {
                        $mail->addCC($_POST['email_copia'], $this->cliente->razonsocial);
                    }
                }
                $mail->Subject = $this->empresa->nombre . ': Su factura ' . $this->documento->codigo;

                $mail->AltBody = $_POST['mensaje'];
                $mail->msgHTML(nl2br($_POST['mensaje']));
                $mail->isHTML(TRUE);

                $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $filename);
                if (is_uploaded_file($_FILES['adjunto']['tmp_name'])) {
                    $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
                }

                if ($mail->smtpConnect($this->empresa->smtp_options())) {
                    if ($mail->send()) {
                        $this->template = 'ventas_imprimir';
                        $this->new_message('Mensaje enviado correctamente.');

                        /// nos guardamos la fecha de envío
                        $factura_enviar->femail = $this->today();
                        $factura_enviar->save();

                        $this->empresa->save_mail($mail);
                    } else
                        $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
                } else
                    $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);

                unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $filename);
            } else
                $this->new_error_msg('Imposible generar el PDF.');
        }
    }

    public function generar_pdf_factura($pdf_doc) {
        $lineas = $this->documento->get_lineas();
        $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
        if ($lineas) {
            $linea_actual = 0;
            $pagina = 1;

            /// imprimimos las páginas necesarias
            while ($linea_actual < count($lineas)) {
                $lppag = 35;

                /// salto de página
                if ($linea_actual > 0) {
                    $pdf_doc->pdf->ezNewPage();
                }

                $this->generar_pdf_cabecera($pdf_doc, $lppag);
                $this->generar_pdf_datos_cliente($pdf_doc, $lppag);

                $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag, $this->documento);

                if ($linea_actual == count($lineas)) {
                    if (!$this->documento->pagada AND $this->impresion['print_formapago']) {
                        $fp0 = new forma_pago();
                        $forma_pago = $fp0->get($this->documento->codpago);
                        if ($forma_pago) {
                            $texto_pago = "\n<b>Forma de pago</b>: " . $forma_pago->descripcion;

                            if (!$forma_pago->imprimir) {
                                /// nada
                            } else if ($forma_pago->domiciliado) {
                                $cbc0 = new cuenta_banco_cliente();
                                $encontrada = FALSE;
                                foreach ($cbc0->all_from_cliente($this->documento->codcliente) as $cbc) {
                                    $texto_pago .= "\n<b>Domiciliado en</b>: ";
                                    if ($cbc->iban) {
                                        $texto_pago .= $cbc->iban(TRUE);
                                    }

                                    if ($cbc->swift) {
                                        $texto_pago .= "\n<b>SWIFT/BIC</b>: " . $cbc->swift;
                                    }
                                    $encontrada = TRUE;
                                    break;
                                }
                                if (!$encontrada) {
                                    $texto_pago .= "\n<b>El cliente no tiene cuenta bancaria asignada.</b>";
                                }
                            } else if ($forma_pago->codcuenta) {
                                $cb0 = new cuenta_banco();
                                $cuenta_banco = $cb0->get($forma_pago->codcuenta);
                                if ($cuenta_banco) {
                                    if ($cuenta_banco->iban) {
                                        $texto_pago .= "\n<b>IBAN</b>: " . $cuenta_banco->iban(TRUE);
                                    }

                                    if ($cuenta_banco->swift) {
                                        $texto_pago .= "\n<b>SWIFT o BIC</b>: " . $cuenta_banco->swift;
                                    }
                                }
                            }
                            $pdf_doc->pdf->ezText($texto_pago, 9);
                        }
                    }
                }

                $pdf_doc->set_y(120);
                $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);

                /// pié de página para la factura
                if ($this->empresa->pie_factura) {
                    $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text(fs_fix_html($this->empresa->pie_factura), 180));
                }
                $pagina++;
            }
        } else {
            $pdf_doc->pdf->ezText('¡' . ucfirst(FS_FACTURA) . ' sin líneas!', 20);
        }
    }
    
    /**
    * Añade la cabecera al PDF con el logotipo y los datos de la empresa.
    * @param type $this->empresa
    * @param int $lppag
    */
   public function generar_pdf_cabecera(&$pdf_doc, &$lppag) {
        /// ¿Añadimos el logo?
        //$pdf_doc->logo = false;
       $page_width = 612;
       $page_height = 792;
       $this->rd_setup['rd_imprimir_logo'] = 'TRUE';
       if ($pdf_doc->logo AND $this->rd_setup['rd_imprimir_logo'] == 'TRUE') {
            if (function_exists('imagecreatefromstring')) {
                $lppag -= 2; /// si metemos el logo, caben menos líneas

                $tamanyo = $this->calcular_tamanyo_logo($pdf_doc);
                if (substr(strtolower($pdf_doc->logo), -4) == '.png') {
                    $pdf_doc->pdf->addPngFromFile($pdf_doc->logo, 35, 687, $tamanyo[0], $tamanyo[1]);
                } else if (function_exists('imagepng')) {
                    /**
                     * La librería ezpdf tiene problemas al redimensionar jpegs,
                     * así que hacemos la conversión a png para evitar estos problemas.
                     */
                    if (imagepng(imagecreatefromstring(file_get_contents($this->logo)), FS_MYDOCS . 'images/logo.png')) {
                        $pdf_doc->pdf->addPngFromFile(FS_MYDOCS . 'images/logo.png', 35, 687, $tamanyo[0], $tamanyo[1]);
                    } else {
                        $pdf_doc->pdf->addJpegFromFile($this->logo, 35, 687, $tamanyo[0], $tamanyo[1]);
                    }
                } else {
                    $pdf_doc->pdf->addJpegFromFile($this->logo, 35, 687, $tamanyo[0], $tamanyo[1]);
                }
            } else {
                die('ERROR: no se encuentra la función imagecreatefromstring(). '
                        . 'Y por tanto no se puede usar el logotipo en los documentos.');
            }
        }
        $left = ($this->rd_setup['rd_imprimir_logo'] == 'TRUE')?90:0;
        //$pdf_doc->pdf->ezSetY(765);
        $pdf_doc->pdf->ez['rightMargin'] = 40;
        $pdf_doc->pdf->ezText("<b>" . fs_fix_html($this->empresa->nombre) . "</b>", 12, array('justification' => 'left', 'left'=>$left));
        $pdf_doc->pdf->ezText(FS_CIFNIF . ": " . $this->empresa->cifnif, 8, array('justification' => 'left', 'left'=>$left));

        $direccion = $this->empresa->direccion . "\n";
        if ($this->empresa->apartado) {
            $direccion .= ucfirst(FS_APARTADO) . ': ' . $this->empresa->apartado . ' - ';
        }

        if ($this->empresa->codpostal) {
            $direccion .= 'CP: ' . $this->empresa->codpostal . ' - ';
        }

        if ($this->empresa->ciudad) {
            $direccion .= $this->empresa->ciudad . ' - ';
        }

        if ($this->empresa->provincia) {
            $direccion .= '(' . $this->empresa->provincia . ')';
        }

        if ($this->empresa->telefono) {
            $direccion .= "\nTeléfono: " . $this->empresa->telefono;
        }

        $pdf_doc->pdf->ezText(fs_fix_html($direccion) . "\n", 8, array('justification' => 'left', 'left'=>$left));
        //$pdf_doc->pdf->line(310,740,310,820);
        $pdf_doc->pdf->setLineStyle(1);
        $pdf_doc->pdf->rectangle(340, 687, 220,80);
        $this->informacion_documento($pdf_doc);
        if($this->distrib_transporte){
            $this->informacion_distribucion($pdf_doc);
            $pdf_doc->set_y(670);
        }else{
            $pdf_doc->set_y(650);
        }
    }
    
    private function informacion_documento(&$pdf_doc)
    {
        $pdf_doc->set_y(765);
        $tipo_doc = ucfirst(FS_ALBARAN);
        $width_campo1 = 90;
        $rectificativa = FALSE;
        if (get_class_name($this->documento) == 'factura_cliente') {
            if ($this->documento->idfacturarect) {
                $tipo_doc = ucfirst(FS_FACTURA_RECTIFICATIVA);
                $rectificativa = TRUE;
                $width_campo1 = 110;
            } else {
                $tipo_doc = 'Factura';
            }
        }
        $pdf_doc->pdf->ezText("<b>" . $tipo_doc . ":</b> ".$this->documento->codigo, 9, array('justification' => 'center','left'=>305, 'right' => 10));
        $pdf_doc->pdf->ezText("<b>" . FS_NUMERO2 . ":</b> ".$this->documento->ncf, 9, array('justification' => 'center','left'=>305, 'right' => 10));
        if ($rectificativa) {
            $pdf_doc->pdf->ezText("<b>Afecta a:</b> ".$this->documento->ncf_afecta, 9, array('justification' => 'center','left'=>305, 'right' => 10));
        }else{
            $pdf_doc->pdf->ezText("\n", 6);
        }
        $pdf_doc->pdf->ezText("<b>" . $this->documento->tipo_comprobante . "</b> ", 9, array('justification' => 'center','left'=>305, 'right' => 10));
        $pdf_doc->pdf->ezText("\n", 3);
        $pdf_doc->pdf->ezText("<b>Fecha</b> ".$this->documento->fecha." | <b>Vencimiento</b> ".$this->documento->vencimiento, 9, array('justification' => 'center','left'=>305, 'right' => 10));
    }
    
    private function informacion_distribucion(&$pdf_doc){
        $pdf_doc->set_y(685);
        $pdf_doc->pdf->rectangle(340, 667, 220,20);
        $pdf_doc->pdf->ezText("<b>Transporte:</b> ".$this->idtransporte." - <b>Ruta:</b> ".$this->documento->codruta, 9, array('justification' => 'center','left'=>305, 'right' => 10));
        
    }

    private function calcular_tamanyo_logo($pdf_doc)
   {
      $tamanyo = $size = getimagesize($pdf_doc->logo);
      if($size[0] > 200)
      {
         $tamanyo[0] = 200;
         $tamanyo[1] = $tamanyo[1] * $tamanyo[0]/$size[0];
         $size[0] = $tamanyo[0];
         $size[1] = $tamanyo[1];
      }
      
      if($size[1] > 80)
      {
         $tamanyo[1] = 80;
         $tamanyo[0] = $tamanyo[0] * $tamanyo[1]/$size[1];
      }
      
      return $tamanyo;
   }

    private function generar_pdf_datos_cliente(&$pdf_doc, &$lppag) {
        $tipo_doc = ucfirst(FS_ALBARAN);
        $width_campo1 = 90;
        $rectificativa = FALSE;
        if (get_class_name($this->documento) == 'factura_cliente') {
            if ($this->documento->idfacturarect) {
                $tipo_doc = ucfirst(FS_FACTURA_RECTIFICATIVA);
                $rectificativa = TRUE;
                $width_campo1 = 110;
            } else {
                $tipo_doc = 'Factura';
            }
        }

        $tipoidfiscal = FS_CIFNIF;
        //Esto se activará en una siguiente corrección
        /*
        if ($this->cliente) {
            $tipoidfiscal = $this->cliente->tipoidfiscal;
        }
        */
        $vendedor = $this->agente->get($this->documento->codagente);
        $direccion = $this->documento->direccion;
        if ($this->documento->apartado) {
            $direccion .= ' - ' . ucfirst(FS_APARTADO) . ': ' . $this->documento->apartado;
        }
        if ($this->documento->codpostal) {
            $direccion .= ' - CP: ' . $this->documento->codpostal;
        }
        $direccion .= ' - ' . $this->documento->ciudad;
        if ($this->documento->provincia) {
            $direccion .= ' (' . $this->documento->provincia . ')';
        }
        if ($this->documento->codpais != $this->empresa->codpais) {
            $pais0 = new pais();
            $pais = $pais0->get($this->documento->codpais);
            if ($pais) {
                $direccion .= ' ' . $pais->nombre;
            }
        }
        $telefonos = '';
        if (!$this->cliente) {
            /// nada
        } else if ($this->cliente->telefono1) {
            $telefonos = "<b>Teléfonos:</b> " . $this->cliente->telefono1;
            if ($this->cliente->telefono2) {
                $telefonos .= "\n" . $this->cliente->telefono2;
                $lppag -= 2;
            }
        } else if ($this->cliente->telefono2) {
            $telefonos = "<b>Teléfonos:</b> " . $this->cliente->telefono2;
        }
        $pdf_doc->pdf->ezText("<b>Cliente:</b> ".$this->documento->codcliente.' - '.fs_fix_html($this->documento->nombrecliente), 9, array('justification' => 'left'));
        $pdf_doc->pdf->ezText("\n", 1);
        $pdf_doc->pdf->ezText( "<b>" . $tipoidfiscal . ":</b> " . $this->documento->cifnif." - " . $telefonos, 9, array('justification' => 'left'));
        $pdf_doc->pdf->ezText("\n", 1);
        $pdf_doc->pdf->ezText("<b>Dirección:</b> ".fs_fix_html($direccion), 9, array('justification' => 'left'));
        $pdf_doc->pdf->ezText("\n", 1);
        $pdf_doc->pdf->ezText("<b>Vendedor:</b> ".fs_fix_html($vendedor->nombreap), 9, array('justification' => 'left'));
        $pdf_doc->pdf->ezText("\n", 1);
        /* Si tenemos dirección de envío y es diferente a la de facturación */
        if ($this->documento->envio_direccion && $this->documento->direccion != $this->documento->envio_direccion) {
            $pdf_doc->new_table();
            $direccionenv = '';
            if ($this->documento->envio_codigo) {
                $direccionenv .= 'Cod. Seg.: "' . $this->documento->envio_codigo . '" - ';
            }
            if ($this->documento->envio_nombre) {
                $direccionenv .= $this->documento->envio_nombre . ' ' . $this->documento->envio_apellidos . ' - ';
            }
            $direccionenv .= $this->documento->envio_direccion;
            if ($this->documento->envio_apartado) {
                $direccionenv .= ' - ' . ucfirst(FS_APARTADO) . ': ' . $this->documento->envio_apartado;
            }
            if ($this->documento->envio_codpostal) {
                $direccionenv .= ' - CP: ' . $this->documento->envio_codpostal;
            }
            $direccionenv .= ' - ' . $this->documento->envio_ciudad;
            if ($this->documento->envio_provincia) {
                $direccionenv .= ' (' . $this->documento->envio_provincia . ')';
            }
            if ($this->documento->envio_codpais != $this->empresa->codpais) {
                $pais0 = new pais();
                $pais = $pais0->get($this->documento->envio_codpais);
                if ($pais) {
                    $direccionenv .= ' ' . $pais->nombre;
                }
            }
            /* Tal y como está la plantilla actualmente:
             * Cada 54 caracteres es una línea en la dirección y no sabemos cuantas líneas tendrá,
             * a partir de ahí es una linea a restar por cada 54 caracteres
             */
            $lppag -= ceil(strlen($direccionenv) / 54);
            $row_dir_env = array(
                'campo1' => "<b>Enviar a:</b>",
                'dato1' => fs_fix_html($direccionenv),
                'campo2' => ''
            );
            $pdf_doc->add_table_row($row_dir_env);
            $pdf_doc->save_table(
            array(
                'cols' => array(
                    'campo1' => array('width' => $width_campo1, 'justification' => 'right'),
                    'dato1' => array('justification' => 'left'),
                    'campo2' => array('justification' => 'right')
                ),
                'showLines' => 0,
                'width' => 520,
                'shaded' => 0,
                'fontSize' => 8
            )
        );
        }

        
        $pdf_doc->pdf->ezText("\n", 8);
    }

    private function generar_pdf_totales(&$pdf_doc, &$lineas_iva, $pagina) {
        if (isset($_GET['noval'])) {
            $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text('Página ' . $pagina . '/' . $this->numpaginas, 250));
        } else {
            /*
             * Rellenamos la última tabla de la página:
             * 
             * Página            Neto    IVA   Total
             */
            /**
             * Generamos un pie de página con firma
             * Total Exento
             * Total Gravado
             * ITBIS
             * Total a Pagar
             */
            $pdf_doc->new_table();
            $titulo = array('pagina' => '<b>Página</b>', 'neto' => '<b>Neto</b>',);
            $fila = array(
                'pagina' => $pagina . '/' . $this->numpaginas,
            );
            $opciones = array(
                'cols' => array(
                    'campo1' => array('justification' => 'right'),
                    'desc1' => array('justification' => 'right'),
                ),
                'showLines' => 1,
                'showHeadings' => 0,
                'shaded'=> 0,
                'xPos' => 'right',
                'xOrientation' => 'left',
                'width' => 200
            );
            $fila_neto = array(
                'campo1' => '<b>Neto</b>',
                'desc1' => $this->show_precio($this->documento->neto, $this->documento->coddivisa),
            );
            $pdf_doc->add_table_row($fila_neto);
            foreach ($lineas_iva as $li) {
                $imp = $this->impuesto->get($li['codimpuesto']);
                $fila_iva = array(
                    'campo1' => ($imp)?'<b>' . $imp->descripcion . '</b>':'<b>' . FS_IVA . ' ' . $li['iva'] . '%</b>',
                    'desc1' => $this->show_precio($li['totaliva'], $this->documento->coddivisa),
                );
                $pdf_doc->add_table_row($fila_iva);
            }
            
            if ($this->documento->totalirpf != 0) {
                $opciones['cols']['irpf'] = array('justification' => 'right');
                $fila_irpf = array(
                    'campo1' => '<b>' . FS_IRPF . ' ' . $this->documento->irpf . '%</b>',
                    'desc1' => $this->show_precio($this->documento->totalirpf),
                );
                $pdf_doc->add_table_row($fila_irpf);
            }
            
            $fila_final = array(
                'campo1' => '<b>Total</b>',
                'desc1' => $this->show_precio($this->documento->total, $this->documento->coddivisa),
            );
            $pdf_doc->add_table_row($fila_final);
            
            $pdf_doc->save_table($opciones);
            $pdf_doc->pdf->line(60,60,180,60);
            $pdf_doc->pdf->line(210,60,360,60);
            $pdf_doc->pdf->addText(100, 50, 8, 'Firma Cliente', 0);
            $pdf_doc->pdf->addText(260, 50, 8, 'Firma Emisor', 0);
            
        }
        $pdf_doc->pdf->addText(10, 10, 8, $pdf_doc->center_text('Página ' . $pagina . '/' . $this->numpaginas, 250));
    }

    private function generar_pdf_lineas(&$pdf_doc, &$lineas, &$linea_actual, &$lppag) {
        /// calculamos el número de páginas
        if (!isset($this->numpaginas)) {
            $this->numpaginas = 0;
            $linea_a = 0;
            while ($linea_a < count($lineas)) {
                $lppag2 = $lppag;
                foreach ($lineas as $i => $lin) {
                    if ($i >= $linea_a AND $i < $linea_a + $lppag2) {
                        $linea_size = 1;
                        $len = mb_strlen($lin->referencia . ' ' . $lin->descripcion);
                        while ($len > 85) {
                            $len -= 85;
                            $linea_size += 0.5;
                        }

                        $aux = explode("\n", $lin->descripcion);
                        if (count($aux) > 1) {
                            $linea_size += 0.5 * ( count($aux) - 1);
                        }

                        if ($linea_size > 1) {
                            $lppag2 -= $linea_size - 1;
                        }
                    }
                }

                $linea_a += $lppag2;
                $this->numpaginas++;
            }

            if ($this->numpaginas == 0) {
                $this->numpaginas = 1;
            }
        }

        if ($this->impresion['print_dto']) {
            $this->impresion['print_dto'] = FALSE;

            /// leemos las líneas para ver si de verdad mostramos los descuentos
            foreach ($lineas as $lin) {
                if ($lin->dtopor != 0) {
                    $this->impresion['print_dto'] = TRUE;
                    break;
                }
            }
        }

        $dec_cantidad = 0;
        $multi_iva = FALSE;
        $multi_re = FALSE;
        $multi_irpf = FALSE;
        $iva = FALSE;
        $re = FALSE;
        $irpf = FALSE;
        /// leemos las líneas para ver si hay que mostrar los tipos de iva, re o irpf
        foreach ($lineas as $i => $lin) {
            if ($lin->cantidad != intval($lin->cantidad)) {
                $dec_cantidad = 2;
            }

            if ($iva === FALSE) {
                $iva = $lin->iva;
            } else if ($lin->iva != $iva) {
                $multi_iva = TRUE;
            }

            if ($re === FALSE) {
                $re = $lin->recargo;
            } else if ($lin->recargo != $re) {
                $multi_re = TRUE;
            }

            if ($irpf === FALSE) {
                $irpf = $lin->irpf;
            } else if ($lin->irpf != $irpf) {
                $multi_irpf = TRUE;
            }

            /// restamos líneas al documento en función del tamaño de la descripción
            if ($i >= $linea_actual AND $i < $linea_actual + $lppag) {
                $linea_size = 1;
                $len = mb_strlen($lin->referencia . ' ' . $lin->descripcion);
                while ($len > 85) {
                    $len -= 85;
                    $linea_size += 0.5;
                }

                $aux = explode("\n", $lin->descripcion);
                if (count($aux) > 1) {
                    $linea_size += 0.5 * ( count($aux) - 1);
                }

                if ($linea_size > 1) {
                    $lppag -= $linea_size - 1;
                }
            }
        }

        /*
         * Creamos la tabla con las lineas del documento
         */
        $pdf_doc->new_table();
        $table_header = array(
            'alb' => '<b>' . ucfirst(FS_ALBARAN) . '</b>',
            'descripcion' => '<b>Ref. + Descripción</b>',
            'cantidad' => '<b>Cant.</b>',
            'pvp' => '<b>P. Unitario</b>',
        );

        /// ¿Desactivamos la columna de albaran?
        if (get_class_name($this->documento) == 'factura_cliente') {
            if ($this->impresion['print_alb']) {
                /// aunque esté activada, si la factura no viene de un albaran, la desactivamos
                $this->impresion['print_alb'] = FALSE;
                foreach ($lineas as $lin) {
                    if ($lin->idalbaran) {
                        $this->impresion['print_alb'] = TRUE;
                        break;
                    }
                }
            }

            if (!$this->impresion['print_alb']) {
                unset($table_header['alb']);
            }
        } else {
            unset($table_header['alb']);
        }

        if ($this->impresion['print_dto'] AND ! isset($_GET['noval'])) {
            $table_header['dto'] = '<b>Descuento</b>';
        }

        if ($multi_iva AND ! isset($_GET['noval'])) {
            $table_header['iva'] = '<b>' . FS_IVA . '</b>';
        }

        if ($multi_re AND ! isset($_GET['noval'])) {
            $table_header['re'] = '<b>R.E.</b>';
        }

        if ($multi_irpf AND ! isset($_GET['noval'])) {
            $table_header['irpf'] = '<b>' . FS_IRPF . '</b>';
        }

        if (isset($_GET['noval'])) {
            unset($table_header['pvp']);
        } else {
            $table_header['importe'] = '<b>Importe</b>';
        }

        $pdf_doc->add_table_header($table_header);

        for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ( $linea_actual < count($lineas)));) {
            $descripcion = fs_fix_html($lineas[$linea_actual]->descripcion);
            if (!is_null($lineas[$linea_actual]->referencia)) {
                $descripcion = '<b>' . $lineas[$linea_actual]->referencia . '</b> ' . $descripcion;
            }

            /// ¿El articulo tiene trazabilidad?
            $descripcion .= $this->generar_trazabilidad($lineas[$linea_actual]);

            $fila = array(
                'alb' => '-',
                'cantidad' => $this->show_numero($lineas[$linea_actual]->cantidad, $dec_cantidad),
                'descripcion' => $descripcion,
                'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $this->documento->coddivisa, TRUE, FS_NF0),
                'dto' => ($lineas[$linea_actual]->dtopor)?$this->show_precio(($lineas[$linea_actual]->pvpunitario*$lineas[$linea_actual]->cantidad)*($lineas[$linea_actual]->dtopor/100), $this->documento->coddivisa, TRUE, FS_NF0):'',
                'iva' => $this->show_numero($lineas[$linea_actual]->iva) . " %",
                're' => $this->show_numero($lineas[$linea_actual]->recargo) . " %",
                'irpf' => $this->show_numero($lineas[$linea_actual]->irpf) . " %",
                'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->documento->coddivisa)
            );

            if ($lineas[$linea_actual]->dtopor == 0) {
                $fila['dto'] = '';
            }

            if ($lineas[$linea_actual]->recargo == 0) {
                $fila['re'] = '';
            }

            if ($lineas[$linea_actual]->irpf == 0) {
                $fila['irpf'] = '';
            }

            if (!$lineas[$linea_actual]->mostrar_cantidad) {
                $fila['cantidad'] = '';
            }

            if (!$lineas[$linea_actual]->mostrar_precio) {
                $fila['pvp'] = '';
                $fila['dto'] = '';
                $fila['iva'] = '';
                $fila['re'] = '';
                $fila['irpf'] = '';
                $fila['importe'] = '';
            }

            if (get_class_name($lineas[$linea_actual]) == 'linea_factura_cliente' AND $this->impresion['print_alb']) {
                $fila['alb'] = $lineas[$linea_actual]->albaran_numero();
            }

            $pdf_doc->add_table_row($fila);
            $linea_actual++;
        }

        $pdf_doc->save_table(
            array(
                'fontSize' => 8,
                'cols' => array(
                    'cantidad' => array('justification' => 'right'),
                    'pvp' => array('justification' => 'right'),
                    'dto' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'irpf' => array('justification' => 'right'),
                    'importe' => array('justification' => 'right')
                ),
                'width' => 520,
                'shaded' => 0,
                'showLines' => 1,
                'linesCol' => array(0.5,0.5,0.5)
            )
        );

        /// ¿Última página?
        if ($linea_actual == count($lineas)) {
            if ($this->documento->observaciones != '') {
                $pdf_doc->pdf->ezText("\n" . "<b>Observaciones:</b> ".fs_fix_html($this->documento->observaciones), 9);
            }
        }
    }

    /**
     * Devuelve el texto con los números de serie o lotes de la $linea
     * @param linea_albaran_compra $linea
     * @return string
     */
    private function generar_trazabilidad($linea) {
        $lineast = array();
        if (get_class_name($linea) == 'linea_albaran_cliente') {
            $lineast = $this->articulo_traza->all_from_linea('idlalbventa', $linea->idlinea);
        } else if (get_class_name($linea) == 'linea_factura_cliente') {
            $lineast = $this->articulo_traza->all_from_linea('idlfacventa', $linea->idlinea);
        }

        $lote = FALSE;
        $txt = '';
        foreach ($lineast as $lt) {
            $salto = "\n";
            if ($lt->numserie) {
                $txt .= $salto . 'N/S: ' . $lt->numserie . ' ';
                $salto = '';
            }

            if ($lt->lote AND $lt->lote != $lote) {
                $txt .= $salto . 'Lote: ' . $lt->lote;
                $lote = $lt->lote;
            }
        }

        return $txt;
    }

    public function generar_pdf($pdf_doc) {

        if (!empty($pdf_doc)) {
            ///// INICIO - Factura Detallada
            /// Creamos el PDF y escribimos sus metadatos

            $pdf_doc->StartPageGroup();
            $pdf_doc->AliasNbPages();
            $pdf_doc->SetAutoPageBreak(true, 40);
            $pdf_doc->lineaactual = 0;
            $pdf_doc->fdf_observaciones = "";

            // Definimos el color de relleno (gris, rojo, verde, azul)
            if ($this->rd_setup['rd_imprimir_bn'] == 'FALSE') {
                $pdf_doc->SetColorRelleno('gris');
            } else {
                $pdf_doc->SetColorRelleno('blanco');
            }

            /// Definimos todos los datos de la cabecera de la factura
            /// Datos de la empresa
            $vendedor = $this->agente->get($this->documento->codagente);
            $vender = substr($vendedor->nombre, 0, 1) . substr($vendedor->apellidos, 0, 1);
            $pdf_doc->fde_nombre = $this->empresa->nombre;
            $pdf_doc->fde_FS_CIFNIF = FS_CIFNIF;
            $pdf_doc->fde_cifnif = $this->empresa->cifnif;
            $pdf_doc->fde_direccion = $this->empresa->direccion;
            $pdf_doc->fde_codpostal = $this->empresa->codpostal;
            $pdf_doc->fde_ciudad = $this->empresa->ciudad;
            $pdf_doc->fde_provincia = $this->empresa->provincia;
            $pdf_doc->fde_telefono = 'Teléfono: ' . $this->empresa->telefono;
            $pdf_doc->fde_fax = 'Fax: ' . $this->empresa->fax;
            $pdf_doc->fde_email = $this->empresa->email;
            $pdf_doc->fde_web = $this->empresa->web;
            if (in_array('distribucion', $GLOBALS['plugins'])) {
                $pdf_doc->fde_vendedor = 'Vendedor: (' . $vender . ')'; //Mostrando iniciales del vendedor.
                $pdf_doc->fde_ruta = 'Ruta: ' . $this->documento->codruta;
            }
            $pdf_doc->fde_piefactura = $this->empresa->pie_factura;

            /// Insertamos el Logo y Marca de Agua si esta configurado así
            if (file_exists(FS_MYDOCS . 'images/logo.png') AND ( $this->rd_setup['rd_imprimir_logo'] == 'TRUE')) {
                $pdf_doc->fdf_verlogotipo = '1'; // 1/0 --> Mostrar Logotipo
                $pdf_doc->fdf_Xlogotipo = '10'; // Valor X para Logotipo
                $pdf_doc->fdf_Ylogotipo = '40'; // Valor Y para Logotipo
                $pdf_doc->fdf_vermarcaagua = '1'; // 1/0 --> Mostrar Marca de Agua
                $pdf_doc->fdf_Xmarcaagua = '25'; // Valor X para Marca de Agua
                $pdf_doc->fdf_Ymarcaagua = '110'; // Valor Y para Marca de Agua
            } elseif (file_exists(FS_MYDOCS . 'images/logo.jpg') AND ( $this->rd_setup['rd_imprimir_logo'] == 'TRUE')) {
                $pdf_doc->fdf_verlogotipo = '1'; // 1/0 --> Mostrar Logotipo
                $pdf_doc->fdf_Xlogotipo = '10'; // Valor X para Logotipo
                $pdf_doc->fdf_Ylogotipo = '40'; // Valor Y para Logotipo
                $pdf_doc->fdf_vermarcaagua = '1'; // 1/0 --> Mostrar Marca de Agua
                $pdf_doc->fdf_Xmarcaagua = '25'; // Valor X para Marca de Agua
                $pdf_doc->fdf_Ymarcaagua = '110'; // Valor Y para Marca de Agua
            } else {
                $pdf_doc->fdf_verlogotipo = '0';
                $pdf_doc->fdf_Xlogotipo = '0';
                $pdf_doc->fdf_Ylogotipo = '0';
                $pdf_doc->fdf_vermarcaagua = '0';
                $pdf_doc->fdf_Xmarcaagua = '0';
                $pdf_doc->fdf_Ymarcaagua = '0';
            }

            // Tipo de Documento
            $pdf_doc->fdf_tipodocumento = $this->documento->tipo_comprobante; // (FACTURA, FACTURA PROFORMA, ¿ALBARAN, PRESUPUESTO?...)
            $pdf_doc->fdf_codigo = $this->documento->ncf;
            $pdf_doc->fdf_codigorect = $this->documento->ncf_afecta;
            $pdf_doc->fdf_estado = ($this->documento->estado) ? "" : "DOCUMENTO ANULADO";

            // Fecha, Codigo Cliente y observaciones de la factura
            $pdf_doc->fdf_fecha = $this->documento->fecha;
            $pdf_doc->fdf_codcliente = $this->documento->codcliente;
            $pdf_doc->fdf_observaciones = utf8_decode($this->fix_html($this->documento->observaciones));


            // Datos del Cliente
            $pdf_doc->fdf_nombrecliente = $this->fix_html($this->documento->nombrecliente);
            $pdf_doc->fdf_FS_CIFNIF = FS_CIFNIF;
            $pdf_doc->fdf_cifnif = $this->documento->cifnif;
            $pdf_doc->fdf_direccion = $this->fix_html($this->documento->direccion);
            $pdf_doc->fdf_codpostal = $this->documento->codpostal;
            $pdf_doc->fdf_ciudad = $this->documento->ciudad;
            $pdf_doc->fdf_provincia = $this->documento->provincia;
            $pdf_doc->fdc_telefono1 = $this->cliente->telefono1;
            $pdf_doc->fdc_telefono2 = $this->cliente->telefono2;
            $pdf_doc->fdc_fax = $this->cliente->fax;
            $pdf_doc->fdc_email = $this->cliente->email;
            $pdf_doc->fdc_factura_codigo = $this->documento->codigo;

            $pdf_doc->fdf_epago = $pdf_doc->fdf_divisa = $pdf_doc->fdf_pais = '';

            // Conduce asociado
            $pdf_doc->fdf_transporte = $this->idtransporte;
            //Si va usar distribucion se agrega el codigo de la ruta
            //$pdf_doc->fdf_ruta = $this->factura->apartado;
            // Forma de Pago de la Factura
            $pago = new forma_pago();
            $epago = $pago->get($this->documento->codpago);
            if ($epago) {
                $pdf_doc->fdf_epago = $epago->descripcion;
            }

            // Divisa de la Factura
            $divisa = new divisa();
            $edivisa = $divisa->get($this->documento->coddivisa);
            if ($edivisa) {
                $pdf_doc->fdf_divisa = $edivisa->descripcion;
            }

            // Pais de la Factura
            $pais = new pais();
            $epais = $pais->get($this->documento->codpais);
            if ($epais) {
                $pdf_doc->fdf_pais = $epais->nombre;
            }

            // Cabecera Titulos Columnas
            $pdf_doc->Setdatoscab(array('ARTICULO', 'DESCRIPCION', 'CANT', 'PRECIO', 'DTO', FS_IVA, 'IMPORTE'));
            $pdf_doc->SetWidths(array(25, 85, 15, 20, 18, 10, 22));
            $pdf_doc->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R'));
            //$pdf_doc->SetColors(array('6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109', '6|47|109'));
            $pdf_doc->SetColors(array('0|0|0', '0|0|0', '0|0|0', '0|0|0', '0|0|0', '0|0|0', '0|0|0'));
            /// Agregamos la pagina inicial de la factura
            $pdf_doc->AddPage();

            /// Definimos todos los datos del PIE de la factura
            /// Lineas de IVA
            $lineas_iva = $this->documento->get_lineas_iva();
            $negativo = (!empty($this->documento->idfacturarect)) ? -1 : 1;
            if (count($lineas_iva) > 3) {
                $pdf_doc->fdf_lineasiva = $lineas_iva;
            } else {
                $filaiva = array();
                $i = 0;
                foreach ($lineas_iva as $li) {
                    $i++;
                    //La Primera linea es de Neto
                    $filaiva[$i][0] = '';
                    $filaiva[$i][1] = ($li->neto) ? $this->ckeckEuro(($li->neto * $negativo)) : '';
                    $filaiva[$i][2] = ($li->iva) ? ($li->iva * $negativo) . "%" : '';
                    $filaiva[$i][3] = ($li->totaliva) ? $this->ckeckEuro(($li->totaliva * $negativo)) : '';
                    $filaiva[$i][4] = ($li->recargo) ? $li->recargo . "%" : '';
                    $filaiva[$i][5] = ($li->totalrecargo) ? $this->ckeckEuro(($li->totalrecargo * $negativo)) : '';
                    $filaiva[$i][6] = ''; //// POR CREARRRRRR
                    $filaiva[$i][7] = ''; //// POR CREARRRRRR
                    $filaiva[$i][8] = ($li->totallinea) ? $this->ckeckEuro(($li->totallinea * $negativo)) : '';
                }

                if ($filaiva) {
                    $filaiva[1][6] = $this->documento->irpf . ' %';
                    $filaiva[1][7] = $this->ckeckEuro(0 - ($this->documento->totalirpf * $negativo));
                }

                $pdf_doc->fdf_lineasiva = $filaiva;
            }

            // Total factura numerico
            $pdf_doc->fdf_numtotal = $this->ckeckEuro(($this->documento->total * $negativo));

            // Total factura numeros a texto
            $pdf_doc->fdf_textotal = ($this->documento->total * $negativo);



            // Lineas de la Factura
            $lineas = $this->documento->get_lineas();
            if ($lineas) {
                $neto = 0;
                for ($i = 0; $i < count($lineas); $i++) {
                    $neto += ($lineas[$i]->pvptotal * $negativo);
                    $pdf_doc->neto = $this->ckeckEuro($neto);
                    $articulo = new articulo();
                    $art = $articulo->get($lineas[$i]->referencia);
                    if ($art) {
                        $observa = "\n" . utf8_decode($this->fix_html($art->observaciones));
                    } else {
                        //$observa = null; // No mostrar mensaje de error
                        $observa = "\n";
                    }

                    $lafila = array(
                        // '0' => utf8_decode($lineas[$i]->albaran_codigo() . '-' . $lineas[$i]->albaran_numero()),
                        '0' => utf8_decode($lineas[$i]->referencia),
                        '1' => utf8_decode(strtoupper($lineas[$i]->descripcion)) . $observa,
                        '2' => utf8_decode(($lineas[$i]->cantidad * $negativo)),
                        '3' => $this->ckeckEuro($lineas[$i]->pvpunitario),
                        '4' => utf8_decode($this->show_numero($lineas[$i]->dtopor, 0) . " %"),
                        '5' => utf8_decode($this->show_numero(($lineas[$i]->iva), 0) . " %"),
                        // '6' => $this->ckeckEuro($lineas[$i]->pvptotal), // Importe con Descuentos aplicados
                        '6' => $this->ckeckEuro(($lineas[$i]->total_iva() * $negativo))
                    );
                    $pdf_doc->Row($lafila, '1'); // Row(array, Descripcion del Articulo -- ultimo valor a imprimir)
                }
                $pdf_doc->piepagina = true;
            }
        }
    }

    private function share_extensions() {
        $extensiones = array(
            array(
                'name' => 'factura_ncf',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'pdf',
                'text' => 'Factura con NCF',
                'params' => '&solicitud=imprimir'
            ),
            array(
                'name' => 'email_factura_ncf',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'email',
                'text' => 'Factura con NCF',
                'params' => '&solicitud=email'
            )
        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }

    private function fix_html($txt) {
        $newt = str_replace('&lt;', '<', $txt);
        $newt = str_replace('&gt;', '>', $newt);
        $newt = str_replace('&quot;', '"', $newt);
        $newt = str_replace('&#39;', "'", $newt);
        return $newt;
    }

}
