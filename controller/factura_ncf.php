<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014  Valentín González    valengon@hotmail.com
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'plugins/republica_dominicana/extras/fpdf181/fs_fpdf.php';
require_once 'plugins/republica_dominicana/extras/rd_controller.php';
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
define('FPDF_FONTPATH', 'plugins/republica_dominicana/extras/fpdf181/font/');
define('EEURO', chr(128));
class factura_ncf extends rd_controller
{
    public $cliente;
    public $factura;
    public $documento;
    public $ncf_ventas;
    public $distrib_transporte;
    public $idtransporte;
    public $archivo;
    public $agente;
    public $logo;
    public $negativo;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Factura NCF', 'ventas', false, false);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->template = false;
        $this->agente = new agente();

        $this->share_extensions();
        $this->checkLogo();
        $val_id = \filter_input(INPUT_GET, 'id');
        $solicitud = \filter_input(INPUT_GET, 'solicitud');
        $valores_id = explode(',', $val_id);

        if (class_exists('distribucion_ordenescarga_facturas')) {
            $this->distrib_transporte = new distribucion_ordenescarga_facturas();
        }

        if (!empty($valores_id[0]) and $solicitud == 'imprimir') {
            $this->procesar_facturas($valores_id);
        } elseif (!empty($valores_id[0]) and $solicitud == 'email') {
            $this->enviar_email($valores_id[0]);
        }
    }

    public function checkLogo()
    {
        $this->logo = false;
        if (file_exists(FS_MYDOCS . 'images/logo.png')) {
            $this->logo = 'images/logo.png';
        } elseif (file_exists(FS_MYDOCS . 'images/logo.jpg')) {
            $this->logo = 'images/logo.jpg';
        }
    }

    // Corregir el Bug de fpdf con el Simbolo del Euro ---> €
    public function ckeckEuro($cadena)
    {
        $mostrar = '';
        if (!empty($cadena)) {
            $mostrar = $this->show_precio($cadena, $this->factura->coddivisa);
            $pos = strpos($mostrar, '€');
            if ($pos !== false) {
                if (FS_POS_DIVISA == 'right') {
                    return number_format($cadena, FS_NF0, FS_NF1, FS_NF2) . ' ' . EEURO;
                } else {
                    return EEURO . ' ' . number_format($cadena, FS_NF0, FS_NF1, FS_NF2);
                }
            }
        }
        return $mostrar;
    }

    public function checkPorcentaje($cadena)
    {
        $mostrar = '';
        if (!empty($cadena)) {
            $mostrar = $cadena.'%';
        }
        return $mostrar;
    }

    public function checkFechaVencimiento($ncf_datos, $tipo_comprobante)
    {
        if (isset($ncf_datos)) {
            $fecha_vencimiento_comprobante = \date("d-m-Y", \strtotime($ncf_datos));
        } else {
            $fecha_vencimiento_comprobante = \date("d-m-Y", \strtotime($tipo_comprobante));
        }

        return $fecha_vencimiento_comprobante;
    }

    public function procesar_facturas($valores_id, $archivo = false)
    {
        if (!empty($valores_id)) {
            if (ob_get_status()) {
                ob_end_clean();
            }
            $pdf_doc = new PDF_MC_Table('P', 'mm', 'letter');
            $pdf_doc->SetTitle('Facturas de Venta');
            $pdf_doc->SetSubject('Facturas de Venta para clientes');
            $pdf_doc->SetAuthor($this->empresa->nombre);
            $pdf_doc->SetCreator('FacturaScripts V_' . $this->version());
            $pdf_doc->SetFont('Arial', '', 8);
            $this->archivo = $archivo;
            $contador = 0;
            foreach ($valores_id as $id) {
                $factura = new factura_cliente();
                $this->factura = $factura->get($id);
                if ($this->factura) {
                    $ncf_datos = new ncf_ventas();
                    $valores = $ncf_datos->get_ncf(
                        $this->empresa->id,
                        $this->factura->idfactura,
                        $this->factura->codcliente
                    );
                    $ncf_tipo = new ncf_tipo();
                    $ncf_rango = new ncf_rango();
                    $tipo_comprobante = $ncf_tipo->get($valores->tipo_comprobante);
                    $tipo_comprobante_data = $ncf_rango->get_by_tipo(
                        $this->empresa->id,
                        $tipo_comprobante->tipo_comprobante
                    );
                    $this->factura->ncf = $valores->ncf;
                    $this->factura->ncf_afecta = $valores->ncf_modifica;
                    $this->factura->estado = $valores->estado;
                    $this->factura->tipo_comprobante = ($tipo_comprobante) ? $tipo_comprobante->descripcion : '';
                    $this->factura->fecha_vencimiento_comprobante = '';
                    if (in_array($tipo_comprobante->tipo_comprobante, array('02', '04'), true)===false) {
                        $this->factura->fecha_vencimiento_comprobante = $this->checkFechaVencimiento(
                            $valores->fecha_vencimiento,
                            $tipo_comprobante_data->fecha_vencimiento
                        );
                    }
                    if ($this->distrib_transporte) {
                        $transporte = $this->distrib_transporte->get(
                            $this->empresa->id,
                            $this->factura->idfactura,
                            $this->factura->codalmacen
                        );
                        $this->idtransporte =
                            (isset($transporte[0]->idtransporte))?
                            str_pad($transporte[0]->idtransporte, 10, "0", STR_PAD_LEFT):
                            false;
                    }
                    $cliente = new cliente();
                    $this->cliente = $cliente->get($this->factura->codcliente);
                    $this->generar_pdf($pdf_doc);
                    $contador++;
                }
            }
            if ($this->archivo) {
                if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                    mkdir('tmp/' . FS_TMP_NAME . 'enviar');
                }
                $pdf_doc->Output('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo, 'F');
            } else {
                $pdf_doc->Output();
            }
            if (!$this->factura) {
                $this->new_error_msg("¡Factura de cliente no encontrada!");
            }
        }
    }

    private function enviar_email($doc, $tipo = 'ncf')
    {
        $factura = new factura_cliente();
        $factura_enviar = $factura->get($doc);
        $cliente = new cliente();
        $this->cliente = $cliente->get($factura_enviar->codcliente);
        if ($this->empresa->can_send_mail()) {
            if ($_POST['email'] != $this->cliente->email and isset($_POST['guardar'])) {
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
                $mail->Subject = $this->empresa->nombre . ': Su factura ' . $this->factura->codigo;

                $mail->AltBody = $_POST['mensaje'];
                $mail->msgHTML(nl2br($_POST['mensaje']));
                $mail->isHTML(true);

                $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $filename);
                if (is_uploaded_file($_FILES['adjunto']['tmp_name'])) {
                    $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
                }

                if ($mail->smtpConnect($this->empresa->smtp_options())) {
                    if ($mail->send()) {
                        $this->template = 'ventas_imprimir';
                        $this->new_message('Mensaje enviado correctamente.');
                        $this->documento = $this->factura;

                        /// nos guardamos la fecha de envío
                        $factura_enviar->femail = $this->today();
                        $factura_enviar->save();

                        $this->empresa->save_mail($mail);
                    } else {
                        $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
                    }
                } else {
                    $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
                }

                unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $filename);
            } else {
                $this->new_error_msg('Imposible generar el PDF.');
            }
        }
    }

    public function pdf_informacion_empresa($pdf_doc)
    {
        $vendedor = $this->agente->get($this->factura->codagente);
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
        $pdf_doc->fde_vendedor = '';
        if (in_array('distribucion', $GLOBALS['plugins'])) {
            $pdf_doc->fde_vendedor = $vendedor->nombreap;
            $pdf_doc->fdf_ruta = $this->factura->codruta;
            $pdf_doc->fde_ruta = $this->factura->codruta;
            $pdf_doc->fdf_transporte = $this->idtransporte;
        }
        $pdf_doc->fde_piefactura = $this->empresa->pie_factura;
    }

    public function configuracion_pdf($pdf_doc)
    {
        $pdf_doc->fdf_verlogotipo = ($this->rd_setup['rd_imprimir_logo'] == 'TRUE' and $this->logo) ? '1' : '0';
        $pdf_doc->fdf_Xlogotipo = ($this->rd_setup['rd_imprimir_logo'] == 'TRUE' and $this->logo) ? '10' : '0';
        $pdf_doc->fdf_Ylogotipo = ($this->rd_setup['rd_imprimir_logo'] == 'TRUE' and $this->logo) ? '5' : '0';
        $pdf_doc->fdf_vermarcaagua = ($this->rd_setup['rd_imprimir_marca_agua'] == 'TRUE' and $this->logo) ? '1' : '0';
        $pdf_doc->fdf_Xmarcaagua = ($this->rd_setup['rd_imprimir_marca_agua'] == 'TRUE' and $this->logo) ? '25' : '0';
        $pdf_doc->fdf_Ymarcaagua = ($this->rd_setup['rd_imprimir_marca_agua'] == 'TRUE' and $this->logo) ? '110' : '0';
        $pdf_doc->fdf_verSelloPagado = (isset($this->rd_setup['rd_imprimir_sello_pagado']) && $this->rd_setup['rd_imprimir_sello_pagado'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_Xsellopagado = (isset($this->rd_setup['rd_imprimir_sello_pagado']) && $this->rd_setup['rd_imprimir_sello_pagado'] == 'TRUE') ? '65' : '0';
        $pdf_doc->fdf_Ysellopagado = (isset($this->rd_setup['rd_imprimir_sello_pagado']) && $this->rd_setup['rd_imprimir_sello_pagado'] == 'TRUE') ? '180' : '0';

        $pdf_doc->fdf_imprimir_bn = ($this->rd_setup['rd_imprimir_bn'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_cliente_box = ($this->rd_setup['rd_imprimir_cliente_box'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_detalle_box = ($this->rd_setup['rd_imprimir_detalle_box'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_detalle_lineas = ($this->rd_setup['rd_imprimir_detalle_lineas'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_detalle_colores = ($this->rd_setup['rd_imprimir_detalle_colores'] == 'TRUE') ? '1' : '0';
        $pdf_doc->fdf_cabecera_fcolor = ($this->rd_setup['rd_imprimir_bn'] == 'FALSE') ? $this->rd_setup['rd_imprimir_cabecera_fcolor'] : false;
        $pdf_doc->fdf_cabecera_tcolor = ($this->rd_setup['rd_imprimir_bn'] == 'FALSE') ? $this->rd_setup['rd_imprimir_cabecera_tcolor'] : false;
        $pdf_doc->fdf_detalle_color = ($this->rd_setup['rd_imprimir_detalle_colores'] == 'TRUE') ? $this->rd_setup['rd_imprimir_detalle_color'] : '#000000';
    }

    public function pdf_tipo_documento($pdf_doc)
    {
        // Tipo de Documento
        $pdf_doc->fdf_documento = $this->factura;
        $pdf_doc->fdf_tipodocumento = $this->factura->tipo_comprobante;
        $pdf_doc->fdf_codigo = $this->factura->ncf;
        $pdf_doc->fdf_codigorect = $this->factura->ncf_afecta;
        $pdf_doc->fdf_tipodocumento_vencimiento = $this->factura->fecha_vencimiento_comprobante;
        $pdf_doc->fdf_estado = ($this->factura->estado) ? "" : "DOCUMENTO ANULADO";
    }

    public function pdf_datos_cliente($pdf_doc)
    {
        // Fecha, Codigo Cliente y observaciones de la factura
        $pdf_doc->fdf_fecha = $this->factura->fecha;
        $pdf_doc->fdf_vencimiento = $this->factura->vencimiento;
        $pdf_doc->fdf_codcliente = $this->factura->codcliente;
        $pdf_doc->fdf_observaciones = utf8_decode($this->fix_html($this->factura->observaciones));


        // Datos del Cliente
        $pdf_doc->fdf_nombrecliente = $this->fix_html($this->factura->nombrecliente);
        $pdf_doc->fdf_FS_CIFNIF = FS_CIFNIF;
        $pdf_doc->fdf_cifnif = $this->factura->cifnif;
        $pdf_doc->fdf_direccion = $this->fix_html($this->factura->direccion);
        $pdf_doc->fdf_codpostal = $this->factura->codpostal;
        $pdf_doc->fdf_ciudad = $this->factura->ciudad;
        $pdf_doc->fdf_provincia = $this->factura->provincia;
        $pdf_doc->fdc_telefono1 = $this->cliente->telefono1;
        $pdf_doc->fdc_telefono2 = $this->cliente->telefono2;
        $pdf_doc->fdc_fax = $this->cliente->fax;
        $pdf_doc->fdc_email = $this->cliente->email;
        $pdf_doc->fdc_factura_codigo = $this->factura->codigo;
        $pdf_doc->fdf_epago = $pdf_doc->fdf_divisa = $pdf_doc->fdf_pais = '';
    }

    public function pdf_divisa_pago_pais($pdf_doc)
    {
        // Forma de Pago de la Factura
        $pago = new forma_pago();
        $epago = $pago->get($this->factura->codpago);
        if ($epago) {
            $pdf_doc->fdf_epago = $epago->descripcion;
        }

        // Divisa de la Factura
        $divisa = new divisa();
        $edivisa = $divisa->get($this->factura->coddivisa);
        if ($edivisa) {
            $pdf_doc->fdf_divisa = $edivisa->descripcion;
        }

        // Pais de la Factura
        $pais = new pais();
        $epais = $pais->get($this->factura->codpais);
        if ($epais) {
            $pdf_doc->fdf_pais = $epais->nombre;
        }
    }

    public function pdf_cabecera_titulo_columnas($pdf_doc)
    {
        list($r, $g, $b) = $pdf_doc->htmlColor2Hex($pdf_doc->fdf_detalle_color);
        // Cabecera Titulos Columnas
        $pdf_doc->Setdatoscab(array('ARTICULO', 'DESCRIPCION', 'CANT', 'P. UNIT', 'IMPORTE', 'DSCTO', FS_IVA, 'NETO'));
        $pdf_doc->SetWidths(array(20, 68, 12, 15, 20, 20, 16, 25));
        $pdf_doc->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
        $colores = ($this->rd_setup['rd_imprimir_bn'] == 'FALSE') ? $r . '|' . $g . '|' . $b : '0|0|0';
        $pdf_doc->SetColors(array($colores, $colores, $colores, $colores, $colores, $colores, $colores, $colores));
    }

    public function pdf_lineas_iva($pdf_doc)
    {
        $lineas_iva = $this->factura->get_lineas_iva();
        if (count($lineas_iva) > 3) {
            $pdf_doc->fdf_lineasiva = $lineas_iva;
        } else {
            $filaiva = array(); $i = 0;
            foreach ($lineas_iva as $li) {
                $i++;
                $filaiva[$i][0] = '';
                $filaiva[$i][1] = $this->ckeckEuro(($li->neto * $this->negativo));
                $filaiva[$i][2] = $this->checkPorcentaje(($li->iva * $this->negativo));
                $filaiva[$i][3] = $this->ckeckEuro(($li->totaliva * $this->negativo));
                $filaiva[$i][4] = $this->checkPorcentaje($li->recargo);
                $filaiva[$i][5] = $this->ckeckEuro(($li->totalrecargo * $this->negativo));
                $filaiva[$i][6] = '';
                $filaiva[$i][7] = '';
                $filaiva[$i][8] = $this->ckeckEuro(($li->totallinea * $this->negativo));
            }
            if (!empty($filaiva)) {
                $filaiva[1][6] = $this->factura->irpf . ' %';
                $filaiva[1][7] = $this->ckeckEuro(0 - ($this->factura->totalirpf * $this->negativo));
            }
            $pdf_doc->fdf_lineasiva = $filaiva;
        }
    }

    public function pdf_lineas_factura($pdf_doc)
    {
         // Lineas de la Factura
        $lineas = $this->factura->get_lineas();
        $cantidad_lineas = count($lineas);
        $lineas_restantes = count($lineas);
        $neto = 0;
        $descuento = 0;
        for ($i = 0; $i < $cantidad_lineas; $i++) {
            $pdf_doc->piepagina = false;
            $neto += ($lineas[$i]->pvptotal * $this->negativo);
            $linea_impuesto = (($lineas[$i]->pvptotal * $this->negativo) * ($lineas[$i]->iva / 100));
            $linea_neto = ($lineas[$i]->pvptotal * $this->negativo) * (1 + ($lineas[$i]->iva / 100));
            $descuento_linea = ($lineas[$i]->dtopor) ? (($lineas[$i]->pvpunitario * $this->negativo) * ($lineas[$i]->cantidad * $this->negativo)) * ($lineas[$i]->dtopor / 100) : 0;
            $descuento += $descuento_linea;
            $pdf_doc->neto = $this->ckeckEuro($neto);
            $articulo = new articulo();
            $art = $articulo->get($lineas[$i]->referencia);
            if ($art) {
                $observa = "\n" . utf8_decode($this->fix_html($art->observaciones));
            } else {
                $observa = "\n";
            }

            $lafila = array(
                '0' => utf8_decode($lineas[$i]->referencia),
                '1' => utf8_decode(strtoupper($lineas[$i]->descripcion)) . $observa,
                '2' => utf8_decode(($lineas[$i]->cantidad * $this->negativo)),
                '3' => $this->show_numero($lineas[$i]->pvpunitario, FS_NF0),
                '4' => $this->show_numero(($lineas[$i]->pvpsindto * $this->negativo), FS_NF0),
                '5' => ($lineas[$i]->dtopor) ? $this->show_numero($descuento_linea, FS_NF0) : '',
                '6' => utf8_decode($this->show_numero($linea_impuesto, FS_NF0)),
                '7' => utf8_decode($this->show_numero($linea_neto, FS_NF0)), // Importe con Descuentos aplicados
            );

            $pdf_doc->Row($lafila, '1', $lineas_restantes);
            $lineas_restantes--;
        }
        $pdf_doc->fdf_documento_descuentos = ($descuento) ? $this->ckeckEuro(($descuento)) : '';
    }

    public function generar_pdf($pdf_doc)
    {
        if (!empty($pdf_doc)) {
            ///// INICIO - Factura Detallada
            /// Creamos el PDF y escribimos sus metadatos

            $pdf_doc->StartPageGroup();
            $pdf_doc->AliasNbPages();
            $pdf_doc->SetAutoPageBreak(true, 40);
            $pdf_doc->lineaactual = 0;
            $pdf_doc->fdf_observaciones = "";

            // Definimos el color de relleno (gris, rojo, verde, azul o con un codigo html)
            if ($this->rd_setup['rd_imprimir_bn'] == 'FALSE') {
                $pdf_doc->SetColorRelleno($this->rd_setup['rd_imprimir_cabecera_fcolor']);
            } else {
                $pdf_doc->SetColorRelleno('blanco');
            }

            $this->pdf_informacion_empresa($pdf_doc);
            $this->configuracion_pdf($pdf_doc);
            $this->pdf_tipo_documento($pdf_doc);
            $this->pdf_datos_cliente($pdf_doc);
            $this->pdf_divisa_pago_pais($pdf_doc);
            $this->pdf_cabecera_titulo_columnas($pdf_doc);

            /// Agregamos la pagina inicial de la factura
            $pdf_doc->AddPage();
            $this->negativo = (!empty($this->factura->idfacturarect)) ? -1 : 1;
            $this->pdf_lineas_iva($pdf_doc);
            // Total factura numerico
            $pdf_doc->fdf_documento_neto = $this->ckeckEuro(($this->factura->neto * $this->negativo));
            $pdf_doc->fdf_documento_totaliva = $this->ckeckEuro(($this->factura->totaliva * $this->negativo));
            $pdf_doc->fdf_numtotal = $this->ckeckEuro(($this->factura->total * $this->negativo));

            // Total factura numeros a texto
            $pdf_doc->fdf_textotal = ($this->factura->total * $this->negativo);

            $pdf_doc->fdf_pagada = $this->factura->pagada;
            $pdf_doc->fdf_fecha_pagada = '';
            if ($this->factura->pagada) {
                $dato_pago = $this->factura->get_asiento_pago();
                $pdf_doc->fdf_fecha_pagada = ($dato_pago)?$dato_pago->fecha:$this->factura->fecha;
            }

            $this->pdf_lineas_factura($pdf_doc);

            $pdf_doc->piepagina = true;
        }
    }

    private function share_extensions()
    {
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

    /**
     * Undocumented function
     *
     * @param string $txt 
     * 
     * @return void
     */
    private function fix_html($txt)
    {
        $newt1 = str_replace('&lt;', '<', $txt);
        $newt2 = str_replace('&gt;', '>', $newt1);
        $newt3 = str_replace('&quot;', '"', $newt2);
        $newt = str_replace('&#39;', "'", $newt3);
        return $newt;
    }
}
