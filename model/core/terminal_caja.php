<?php
/*
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2016-2017  Joe Nilson Zegarra Galvez  joenilson@gmail.com
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
namespace FacturaScripts\model;

/**
 * Configuración de un terminal de TPV y de la impresora de tickets,
 * además almacena los tickets a imprimir para el plugin de república dominicana.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 * @author Joe Nilson Zegarra Galvez  <joenilson@gmail.com>
 */
class terminal_caja extends \fs_model
{

    /**
     * Clave primiaria.
     * @var integer
     */
    public $id;

    /**
     * Códifo del almacén a usar en los tickets.
     * @var string
     */
    public $codalmacen;

    /**
     * Código de la serie a utilizar en los tickets.
     * @var string
     */
    public $codserie;

    /**
     * Código del cliente predeterminado para los tickets.
     * @var string
     */
    public $codcliente;

    /**
     * Buffer con los ticket pendientes para imprimir.
     * @var string
     */
    public $tickets;

    /**
     * Número de caracteres que caben en una línea del papel del ticket.
     * @var integer
     */
    public $anchopapel;

    /**
     * Comando ESC/POS para cortar el papel.
     * @var string
     */
    public $comandocorte;

    /**
     * Comando ESC/POS para abrir el cajón portamonedas conectado a la impresora.
     * @var string
     */
    public $comandoapertura;

    /**
     * Número de impresiones para cada ticket.
     * @var integer
     */
    public $num_tickets;

    /**
     * Desactivar los comandos ESC/POS para comprobaciones de la impresora de tickets.
     * @var bool
     */
    public $sin_comandos;

    /**
     * El punto de emisión configurado en el maestro de NCF
     * @var varchar(3)
     * @deprecated since version 134
     */
    public $area_impresion;
    /**
     * El tipo de NCF
     * @var varchar(2)
     */
    public $ncf_tipo;

    public function __construct($t = false)
    {
        parent::__construct('cajas_terminales');
        if ($t) {
            $this->id = $this->intval($t['id']);
            $this->codalmacen = $t['codalmacen'];
            $this->codserie = $t['codserie'];
            $this->area_impresion = $t['area_impresion'];
            $this->codcliente = $t['codcliente'];
            $this->tickets = $t['tickets'];

            $this->anchopapel = 40;
            if (isset($t['anchopapel'])) {
                $this->anchopapel = intval($t['anchopapel']);
            }

            $this->comandocorte = '27.105';
            if (isset($t['comandocorte'])) {
                $this->comandocorte = $t['comandocorte'];
            }

            $this->comandoapertura = '27.112.48';
            if (isset($t['comandoapertura'])) {
                $this->comandoapertura = $t['comandoapertura'];
            }

            $this->num_tickets = 1;
            if (isset($t['num_tickets'])) {
                $this->num_tickets = intval($t['num_tickets']);
            }

            $this->sin_comandos = $this->str2bool($t['sin_comandos']);
        } else {
            $this->id = null;
            $this->codalmacen = null;
            $this->codserie = null;
            $this->area_impresion = null;
            $this->codcliente = null;
            $this->tickets = '';
            $this->anchopapel = 40;
            $this->comandocorte = '27.105';
            $this->comandoapertura = '27.112.48';
            $this->num_tickets = 1;
            $this->sin_comandos = false;
        }
    }

    public function disponible()
    {
        if ($this->db->select("SELECT * FROM cajas WHERE f_fin IS NULL AND fs_id = " . $this->var2str($this->id) . ";")) {
            return false;
        }

        return true;
    }

    public function add_linea($linea)
    {
        $this->tickets .= $linea;
    }

    public function add_linea_big($linea)
    {
        if ($this->sin_comandos) {
            $this->tickets .= $linea;
        } else {
            $this->tickets .= chr(27) . chr(33) . chr(56) . $linea . chr(27) . chr(33) . chr(1);
        }
    }

    public function abrir_cajon()
    {
        if ($this->sin_comandos) {
            /// nada
        } elseif ($this->comandoapertura) {
            $aux = explode('.', $this->comandoapertura);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    public function cortar_papel()
    {
        if ($this->sin_comandos) {
            /// nada
        } elseif ($this->comandocorte) {
            $aux = explode('.', $this->comandocorte);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->tickets .= chr($a);
                }

                $this->tickets .= "\n";
            }
        }
    }

    public function center_text($word = '', $ancho = false)
    {
        if (!$ancho) {
            $ancho = $this->anchopapel;
        }

        if (strlen($word) == $ancho) {
            return $word;
        } elseif (strlen($word) < $ancho) {
            return $this->center_text2($word, $ancho);
        }

        $result = '';
        $nword = '';
        foreach (explode(' ', $word) as $aux) {
            if ($nword == '') {
                $nword = $aux;
            } elseif (strlen($nword) + strlen($aux) + 1 <= $ancho) {
                $nword = $nword . ' ' . $aux;
            } else {
                if ($result != '') {
                    $result .= "\n";
                }

                $result .= $this->center_text2($nword, $ancho);
                $nword = $aux;
            }
        }
        if ($nword != '') {
            if ($result != '') {
                $result .= "\n";
            }

            $result .= $this->center_text2($nword, $ancho);
        }

        return $result;
    }

    private function center_text2($word = '', $ancho = 40)
    {
        $symbol = " ";
        $middle = round($ancho / 2);
        $length_word = strlen($word);
        $middle_word = round($length_word / 2);
        $last_position = $middle + $middle_word;
        $number_of_spaces = $middle - $middle_word;
        $result = sprintf("%'{$symbol}{$last_position}s", $word);
        for ($i = 0; $i < $number_of_spaces; $i++) {
            $result .= "$symbol";
        }
        return $result;
    }

    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM cajas_terminales WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new \terminal_caja($data[0]);
        }

        return false;
    }

    public function exists()
    {
        if (is_null($this->id)) {
            return false;
        }

        return $this->db->select("SELECT * FROM cajas_terminales WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE cajas_terminales SET codalmacen = " . $this->var2str($this->codalmacen) .
                    ", codserie = " . $this->var2str($this->codserie) .
                    ", codcliente = " . $this->var2str($this->codcliente) .
                    ", area_impresion = " . $this->var2str($this->area_impresion) .
                    ", tickets = " . $this->var2str($this->tickets) .
                    ", anchopapel = " . $this->var2str($this->anchopapel) .
                    ", comandocorte = " . $this->var2str($this->comandocorte) .
                    ", comandoapertura = " . $this->var2str($this->comandoapertura) .
                    ", num_tickets = " . $this->var2str($this->num_tickets) .
                    ", sin_comandos = " . $this->var2str($this->sin_comandos) .
                    "  WHERE id = " . $this->var2str($this->id) . ";";

            return $this->db->exec($sql);
        }
        $sql = "INSERT INTO cajas_terminales (codalmacen,codserie,area_impresion,codcliente,tickets,anchopapel,"
                . "comandocorte,comandoapertura,num_tickets,sin_comandos) VALUES (" .
                $this->var2str($this->codalmacen) . "," .
                $this->var2str($this->codserie) . "," .
                $this->var2str($this->area_impresion) . "," .
                $this->var2str($this->codcliente) . "," .
                $this->var2str($this->tickets) . "," .
                $this->var2str($this->anchopapel) . "," .
                $this->var2str($this->comandocorte) . "," .
                $this->var2str($this->comandoapertura) . "," .
                $this->var2str($this->num_tickets) . "," .
                $this->var2str($this->sin_comandos) . ");";

        if ($this->db->exec($sql)) {
            $this->id = $this->db->lastval();
            return true;
        }

        return false;
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM cajas_terminales WHERE id = " . $this->var2str($this->id) . ";");
    }

    private function all_from($sql)
    {
        $tlist = array();
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $tlist[] = new \terminal_caja($a);
            }
        }

        return $tlist;
    }

    public function all()
    {
        return $this->all_from("SELECT * FROM cajas_terminales ORDER BY id ASC;");
    }

    public function disponibles()
    {
        $sql = "SELECT * FROM cajas_terminales WHERE id NOT IN "
                . "(SELECT fs_id as id FROM cajas WHERE f_fin IS NULL) "
                . "ORDER BY id ASC;";

        return $this->all_from($sql);
    }

    /**
     * A partir de una factura añade un ticket a la cola de impresión de este terminal.
     * @param \factura_cliente $factura
     * @param \empresa $empresa
     * @param type $imprimir_descripciones
     * @param type $imprimir_observaciones
     */
    public function imprimir_ticket(&$factura, &$empresa, $imprimir_descripciones = true, $imprimir_observaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->add_linea_big($this->center_text($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->lema)) . "\n\n");
        } else {
            $this->add_linea("\n");
        }

        $this->add_linea(
                $this->center_text($this->sanitize($empresa->direccion) . " - " . $this->sanitize($empresa->ciudad)) . "\n"
        );
        $this->add_linea($this->center_text(FS_CIFNIF . ": " . $empresa->cifnif));
        $this->add_linea("\n\n");

        if ($empresa->horario != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ": " . $factura->codigo . "\n";
        $linea .= "\n" . $factura->tipo_comprobante . "\n";
        $linea .= "NCF: " . $factura->numero2 . "\n";
        $linea .= $factura->fecha . " " . Date('H:i', strtotime($factura->hora)) . "\n";
        $this->add_linea($linea);
        $this->add_linea("Cliente: " . $this->sanitize($factura->nombrecliente) . "\n");
        $this->add_linea(FS_CIFNIF.": " . $this->sanitize($factura->cifnif) . "\n");
        $this->add_linea("Empleado: " . $factura->codagente . "\n\n");

        if ($imprimir_observaciones) {
            $this->add_linea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->add_linea(
                sprintf("%3s", "Ud.") . " " .
                sprintf("%-" . $width . "s", "Articulo") . " " .
                sprintf("%10s", "TOTAL") . "\n"
        );
        $this->add_linea(
                sprintf("%3s", "---") . " " .
                sprintf("%-" . $width . "s", substr("--------------------------------------------------------", 0, $width - 1)) . " " .
                sprintf("%10s", "----------") . "\n"
        );
        foreach ($factura->get_lineas() as $col) {
            if ($imprimir_descripciones) {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", substr($this->sanitize($col->descripcion), 0, $width - 1)) . " " .
                        sprintf("%10s", $this->show_numero($col->total_iva())) . "\n";
            } else {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", $this->sanitize($col->referencia))
                        . " " . sprintf("%10s", $this->show_numero($col->total_iva())) . "\n";
            }

            $this->add_linea($linea);
        }

        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->add_linea($lineaiguales . "\n");
        $this->add_linea(
                'TOTAL A PAGAR: ' . sprintf("%" . ($this->anchopapel - 15) . "s", $this->show_precio($factura->total, $factura->coddivisa)) . "\n"
        );
        $this->add_linea($lineaiguales . "\n");

        /// imprimimos los impuestos desglosados
        $this->add_linea(
                'TIPO   BASE    ' . FS_IVA . '    RE' .
                sprintf('%' . ($this->anchopapel - 24) . 's', 'TOTAL') .
                "\n"
        );
        foreach ($factura->get_lineas_iva() as $imp) {
            $this->add_linea(
                    sprintf("%-6s", $imp->iva . '%') . ' ' .
                    sprintf("%-7s", $this->show_numero($imp->neto)) . ' ' .
                    sprintf("%-6s", $this->show_numero($imp->totaliva)) . ' ' .
                    sprintf("%-6s", $this->show_numero($imp->totalrecargo)) . ' ' .
                    sprintf('%' . ($this->anchopapel - 29) . 's', $this->show_numero($imp->totallinea)) .
                    "\n"
            );
        }

        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->add_linea($lineaiguales);
        $this->cortar_papel();
    }

    /**
     * A partir de una factura añade un ticket regalo a la cola de impresión de este terminal.
     * @param \factura_cliente $factura
     * @param \empresa $empresa
     */
    public function imprimir_ticket_regalo(&$factura, &$empresa, $imprimir_descripciones = true, $imprimir_observaciones = false)
    {
        $medio = $this->anchopapel / 2.5;
        $this->add_linea_big($this->center_text($this->sanitize($empresa->nombre), $medio) . "\n");

        if ($empresa->lema != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->lema)) . "\n\n");
        } else {
            $this->add_linea("\n");
        }

        $this->add_linea(
                $this->center_text($this->sanitize($empresa->direccion) . " - " . $this->sanitize($empresa->ciudad)) . "\n"
        );
        $this->add_linea($this->center_text(FS_CIFNIF . ": " . $empresa->cifnif));
        $this->add_linea("\n\n");

        if ($empresa->horario != '') {
            $this->add_linea($this->center_text($this->sanitize($empresa->horario)) . "\n\n");
        }

        $linea = "\n" . ucfirst(FS_FACTURA_SIMPLIFICADA) . ": " . $factura->codigo . "\n";
        $linea .= "\n" . $factura->tipo_comprobante . "\n";
        $linea .= "NCF: " . $factura->numero2 . "\n";
        $linea .= $factura->fecha . " " . Date('H:i', strtotime($factura->hora)) . "\n";
        $this->add_linea($linea);
        $this->add_linea("Cliente: " . $this->sanitize($factura->nombrecliente) . "\n");
        $this->add_linea("Empleado: " . $factura->codagente . "\n\n");

        if ($imprimir_observaciones) {
            $this->add_linea('Observaciones: ' . $this->sanitize($factura->observaciones) . "\n\n");
        }

        $width = $this->anchopapel - 15;
        $this->add_linea(
                sprintf("%3s", "Ud.") . " " .
                sprintf("%-" . $width . "s", "Articulo") . " " .
                sprintf("%10s", "TOTAL") . "\n"
        );
        $this->add_linea(
                sprintf("%3s", "---") . " " .
                sprintf("%-" . $width . "s", substr("--------------------------------------------------------", 0, $width - 1)) . " " .
                sprintf("%10s", "----------") . "\n"
        );
        foreach ($factura->get_lineas() as $col) {
            if ($imprimir_descripciones) {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", substr($this->sanitize($col->descripcion), 0, $width - 1)) . " " .
                        sprintf("%10s", '-') . "\n";
            } else {
                $linea = sprintf("%3s", $col->cantidad) . " " . sprintf("%-" . $width . "s", $this->sanitize($col->referencia))
                        . " " . sprintf("%10s", '-') . "\n";
            }

            $this->add_linea($linea);
        }


        $lineaiguales = '';
        for ($i = 0; $i < $this->anchopapel; $i++) {
            $lineaiguales .= '=';
        }
        $this->add_linea($lineaiguales);
        $this->add_linea($this->center_text('TICKET REGALO'));
        $lineaiguales .= "\n\n\n\n\n\n\n\n";
        $this->add_linea($lineaiguales);
        $this->cortar_papel();
    }

    public function sanitize($txt)
    {
        $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
            '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
            '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
            '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
            '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
            '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y',
            '/&quot;/' => '-',
            '/À/' => 'A', '/Á/' => 'A', '/Â/' => 'A', '/Ä/' => 'A',
            '/Ç/' => 'C', '/È/' => 'E', '/É/' => 'E', '/Ê/' => 'E',
            '/Ë/' => 'E', '/Ì/' => 'I', '/Í/' => 'I', '/Î/' => 'I', '/Ï/' => 'I',
            '/Ñ/' => 'N', '/Ò/' => 'O', '/Ó/' => 'O', '/Ô/' => 'O', '/Ö/' => 'O',
            '/Ù/' => 'U', '/Ú/' => 'U', '/Û/' => 'U', '/Ü/' => 'U',
            '/Ý/' => 'Y', '/Ÿ/' => 'Y',
        );

        return preg_replace(array_keys($changes), $changes, $txt);
    }

    protected function show_precio($precio, $coddivisa)
    {
        if (FS_POS_DIVISA == 'right') {
            return number_format($precio, FS_NF0, FS_NF1, FS_NF2) . ' ' . $coddivisa;
        }

        return $coddivisa . ' ' . number_format($precio, FS_NF0, FS_NF1, FS_NF2);
    }

    protected function show_numero($num = 0, $decimales = FS_NF0)
    {
        return number_format($num, $decimales, FS_NF1, FS_NF2);
    }
}
