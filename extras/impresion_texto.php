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

/**
 * Description of impresion_texto
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class impresion_texto {

    function __contruct() {
        $tmpfname = "docu.txt";
        if (is_file($tmpfname))unlink($tmpfname);
        $fp = fopen($tmpfname, "w");

        function cabecera($fp, $ccli, $czon, $crut, $cven, $fven, $ditc, $rcli, $dirc, $nfac, $nped) {
            fputs($fp, sprintf("%8s%-5s%8s%-2s%8s%-3s%8s%-3s%8s%-10s%8s%-11s \r\n", "", $ccli, "", $czon, "", $crut, "", $cven, "", $fven, "", $ditc));
            fputs($fp, sprintf("%18s%s\r\n", "", $rcli));
            fputs($fp, sprintf("%18s%s\r\n", "", $dirc));
            fputs($fp, sprintf("\r\n"));
            fputs($fp, sprintf("%104s%s\r\n", "", "$nfac - $nped"));
        }

        function detalle($fp, $cpro, $dpro, $qpro, $puni, $pcjs, $obss) {
            fputs($fp, sprintf("%8s%-3s%8s%-26s%8s%10s%2s%10s%2s%10s%5s%s \r\n", "", $cpro, "", $dpro, "", $qpro, "", $puni, "", $pcjs, "", $obss));
        }

        function bottom($fp, $sped, $dped, $vped, $iped, $tped, $letras) {
            fputs($fp, sprintf("%67s%-10s%10s \r\n", "", "Subtotal", number_format(round($sped, 2), 2, "", "")));
            fputs($fp, sprintf("%67s%-10s%11s \r\n", "", "Dscto", "(" . number_format(round($dped, 2), 2, "", "") . ")"));
            fputs($fp, sprintf("%77s%10s \r\n", "", number_format(round($vped, 2), 2, "", "")));
            fputs($fp, sprintf("%77s%10s \r\n", "", number_format(round($iped, 2), 2, "", "")));
            fputs($fp, sprintf("%77s%10s \r\n", "", number_format(round($tped, 2), 2, "", "")));
            fputs($fp, sprintf("%12s%s \r\n", "", $letras));
            for ($st = 0; $st < 3; $st++)
                fputs($fp, sprintf("\r\n"));
        }

        $fven = $fecven;
        $tot_lineas = 32;
        foreach ($Cabdoc as $cod => $val) {
            $nlinea = 13;
            if (!empty($Marcas[$cod]) || !empty($Volver[$cod]) || !empty($Anular[$cod])) {
                if (!empty($Anular)) {
                    $udoc = $Nrodoc[$val[5]];
                    $Ultdoc = explode("-", $udoc);
                    $seri = $Ultdoc[0];
                    $core = $Ultdoc[1];
                    $core++;
                    $ndoc = corr($seri, 3) . "-" . corr($core, 6);
                    $Nrodoc[$val[5]] = $ndoc;
                } else
                    $ndoc = $cod;
                for ($st = 0; $st < 6; $st++)
                    fputs($fp, sprintf("\r\n"));
                fputs($fp, sprintf("%8s SUBDISTRIBUIDOR %s\r\n", "", $Vsubdi[$val[3]]));
                cabecera($fp, $val[1], $val[5], $val[6], $val[4], $fven, $Repcli[$val[1]][3], $Repcli[$val[1]][0], $Repcli[$val[1]][1], $ndoc, $val[0]);
                foreach ($Despro as $cpro => $vpro) {
                    $Matris = $Detdoc[$cpro][$cod];
                    if (!empty($Matris)) {
                        if ($nlinea == 22)
                            detalle($fp, $cpro, $vpro, number_format(round($Matris[0], 2), 2, "", ""), number_format(round($Matris[2], 2), 2, "", ""), number_format(round($Matris[3], 2), 2, "", ""), strtoupper($obss));
                        else
                            detalle($fp, $cpro, $vpro, number_format(round($Matris[0], 2), 2, "", ""), number_format(round($Matris[2], 2), 2, "", ""), number_format(round($Matris[3], 2), 2, "", ""), "");
                        $nlinea++;
                        if (!empty($Anular))
                            $Detdoc[$cpro][$ndoc] = $Matris;
                    }
                }
                $blancos = 22 - ($nlinea);
                for ($st = 0; $st < $blancos; $st++) {
                    fputs($fp, sprintf("\r\n"));
                    $nlinea++;
                }
                if ($nlinea == 22) {
                    detalle($fp, "", "", "", "", "", strtoupper($obss));
                    $salto = $tot_lineas - ($nlinea + 5);
                } else {
                    $salto = $tot_lineas - ($nlinea + 4);
                }
                for ($st = 0; $st < $salto; $st++)
                    fputs($fp, sprintf("\r\n"));
                $letras = numlet(number_format(round($val[8], 2), 2, "", ""));
                $sped = $val[8] / $figv;
                $iped = $sped * $igv;
                bottom($fp, $sped, $val[9], $sped, $iped, $val[8], $letras);
                if (!empty($Anular)) {
                    $Cabdoc[$ndoc] = $val;
                    $Cabdoc[$cod][10] = "2";
                } else
                    $Cabdoc[$cod][10] = "1";
            }
        }
        fclose($fp);
        session_register('Cabdoc');
        session_register('Detdoc');
        session_register('Nrodoc');
        $sisope = strpos($_SERVER["PATH"], "WINDOWS");
        if (!empty($sisope)) {
            $prn1 = "\\$lstimp";
            $prn2 = $Vimpre[$lstimp][1];
            $prn2 = "\\$prn2";
            system("type $tmpfname>" . addslashes($prn1) . $prn2);
        } else {
            system("lpr -P" . $Vimpre[$lstimp][1] . " $tmpfname");
        }
    }
}
