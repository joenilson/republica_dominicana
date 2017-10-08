<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
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

namespace FacturaScripts\Impresion;
require_once 'extras/xlsxwriter.class.php';
/**
 * Description of FS_XLSX
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */

class FS_XLSX extends XLSXWriter
{
    public $widths;
    public $aligns;
    public $verlogotipo = 0;
    public $empresa;
    public $cabecera_info;
    public $documento_nombre;
    public $documento_numero;
    public $documento_codigo;
    public $documento_cabecera_lineas;
    public $x_pos;
    public $y_pos;
    public $mostrar_borde;
    public $mostrar_colores;
    public $mostrar_linea;
    public $fs_font='Arial';
    public $fs_espacio = 5;
    public $fs_font_title_size = 10;
    public $fs_font_subtitle_size = 9;
    public $fs_font_text_size = 8;
    public $fs_font_lines_size = 9;
    public $hoja_nombre;
    public $hoja_header = array();
    public $hoja_header_esconder = true;
    /**
     *
     * @param string $orientation
     * @param string $unit
     * @param string $size
     */
    public function __construct($file = 'documento.xlsx') {
        parent::__construct();
        //Initialization
        $this->fontList=array('arial', 'times', 'courier', 'helvetica', 'symbol');
        $this->fileName = $file;
        $this->setAuthor('FacturaScritps');
    }

    public function addCabecera(){
        $this->writeSheetHeader($this->hoja_nombre, $this->hoja_header, $this->hoja_header_esconder);
    }

    public function addEmpresaInfo(){
        $x1 = ($this->verlogotipo == '1')?50:10;
        $y1 = $this->y_pos;
        $this->SetXY( $x1, $y1 );
        $this->SetFont($this->fs_font,'B',$this->fs_font_title_size);
        $this->SetTextColor(0);
        $length1 = $this->GetStringWidth($this->empresa->nombre);
        $this->Cell( $length1, 4, utf8_decode($this->empresa->nombre));
        $y1+=4;
        $this->SetXY( $x1, $y1);
        $length2 = $this->GetStringWidth(FS_CIFNIF.': '.$this->empresa->cifnif);
        $this->SetFont($this->fs_font,'',$this->fs_font_subtitle_size);
        $this->Cell($length2, 4, utf8_decode(FS_CIFNIF.': '.$this->empresa->cifnif));
        $y1+=4;
        $this->SetXY( $x1, $y1);
        $this->SetFont($this->fs_font,'',$this->fs_font_text_size);
        $length3 = $this->GetStringWidth( $this->empresa->direccion.' - '.$this->empresa->ciudad.' - '.$this->empresa->provincia );
        $this->MultiCell($length3, 4, utf8_decode($this->empresa->direccion.' - '.$this->empresa->ciudad.' - '.$this->empresa->provincia));
        $y1 += ($this->getY() - $y1);
        if ($this->empresa->telefono != '')
        {
            $this->SetXY($x1, $y1);
            $this->SetFont($this->fs_font,'',$this->fs_font_text_size);
            $this->Cell($length2, 4, utf8_decode('Teléfono: '.$this->empresa->telefono));
            $this->SetTextColor(0);
            $this->SetFont('');
            $y1+=4;
        }

        if ($this->empresa->email != '')
        {
            $this->SetXY($x1, $y1);
            $this->SetFont($this->fs_font,'',$this->fs_font_text_size);
            $this->Write(5,'Email: ');
            $this->SetTextColor(0,0,255);
            $this->Write(5, utf8_decode($this->empresa->email), 'mailto:' . $this->empresa->email);
            $this->SetTextColor(0);
            $this->SetFont('');
        }

        if ($this->empresa->web != '')
        {
            $this->SetXY($x1+$this->GetStringWidth($this->empresa->email)+14, $y1);
            $this->SetFont($this->fs_font,'',$this->fs_font_text_size);
            $this->Write(5,'Web: ');
            $this->SetTextColor(0,0,255);
            $this->Write(5, utf8_decode($this->empresa->web), $this->empresa->web);
            $this->SetTextColor(0);
            $this->SetFont('');
        }
    }

    public function addDocumentoInfo(){
        $r1  = $this->w - 80;
        $r2  = $r1 + 70;
        $y1  = $this->y_pos;
        $y2  = $y1 + 16;

        $szfont = 10;
        $loop   = 0;

        while ( $loop == 0 )
        {
           $this->SetFont($this->fs_font, "B", $szfont);
           $sz = $this->GetStringWidth($this->documento_nombre);
           if ( ($r1+$sz) > $r2 ){
              $szfont--;
           }else{
              $loop++;
           }
        }
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        $y1++;
        $this->SetFont( $this->fs_font, "B", $this->fs_font_title_size );
        $this->SetXY( $r1+1, $y1+3);
        $this->MultiCell(67,5, utf8_decode(strtoupper($this->documento_nombre)), 0, "C");
        $y1+=4;
        $this->SetXY( $r1+1, $y1+3);
        $this->MultiCell(67,5, utf8_decode($this->documento_numero), 0, "C" );
        $this->y_pos = ($y1+$y2);
    }

    public function addCabeceraInfo(){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = $this->y_pos;
        $y2  = 5;
        $y1++;
        $this->SetXY( $r1, $y1);
        foreach($this->cabecera_info as $linea){
            $this->SetFont( $this->fs_font, "B", $this->fs_font_title_size );
            $this->Cell(30,5, utf8_decode($linea['label']), 0, 0, 'R' );
            $this->SetFont( $this->fs_font, "", $this->fs_font_title_size );
            if(isset($linea['html']) AND $linea['html']){
                $this->WriteHTML($linea['valor']);
            }else{
                $this->Cell($linea['size'],5, utf8_decode($linea['valor']), 0, 0, 'L' );
            }
            if($linea['salto_linea']){
                $y1+=5;
                $this->SetXY( $r1, $y1);
                $y2+=5;
            }
        }

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1-($y2-2),($r2 - $r1), $y2, 'B');
        $this->y_pos = $y1+5;
    }

    public function AddCabeceraLineas(){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = $this->y_pos;
        $y2  = 5;

        //Verificamos el total de lineas a imprimir que no se salga del margen
        $total_lineas = 0;
        foreach($this->documento_cabecera_lineas as $c){
            $total_lineas += $c['size'];
        }
        if($total_lineas > ($this->w-20)){
            $cantidad_filas = count($this->documento_cabecera_lineas);
            $exceso_longitud = $total_lineas-($this->w-20);
            $eliminar_por_linea = ceil($exceso_longitud/$cantidad_filas);
            for($i = 0; $i<$cantidad_filas; $i++){
                $this->documento_cabecera_lineas[$i]['size'] -= $eliminar_por_linea;
            }
        }

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        $this->SetXY($r1, $y1);
        $this->SetFont( $this->fs_font, "B", $this->fs_font_title_size );
        $w = array();
        $a = array();
        foreach($this->documento_cabecera_lineas as $cab){
            $this->Cell($cab['size'],5, utf8_decode($cab['descripcion']),0,0,$cab['align']);
            $w[] = $cab['size'];
            $a[] = $cab['align'];
        }
        $this->SetWidths($w);
        $this->SetAligns($a);
        $this->y_pos = $y1+6;
    }

    public function addDetalleLineas($lineas,$separador=false){
        $r1 = 10;
        $y1 = $this->y_pos;
        $this->SetFont($this->fs_font, "", $this->fs_font_lines_size);
        $this->SetXY($r1, $y1);
        foreach($lineas as $linea){
            $this->Row($linea,$separador);
            $y1 += 5;
            if($this->getY()>=(ceil($this->h-60))){
                $this->AddPage();
                $r1 = 10;
                $this->y_pos = 8;
                $this->addEmpresaInfo();
                $this->addDocumentoInfo();
                $this->addCabeceraInfo();
                $this->AddCabeceraLineas();
                $y1 = $this->y_pos;
                $this->SetXY($r1, $y1);
                $this->SetFont($this->fs_font, "", $this->fs_font_lines_size);
            }
        }
        $this->y_pos = $y1+3;
    }

    public function addTotalesLineas($totales){
        $r1 = 10;
        $y1 = -60;
        $this->SetXY($r1, $y1);
        $this->Line($r1, $this->getY(), $this->w-10, $this->getY());
        $y1++;
        $this->SetFont($this->fs_font, "B", $this->fs_font_title_size);
        $this->SetXY($r1, $y1);
        foreach($this->documento_cabecera_lineas as $c){
            if($c['total']){
                $this->Cell($c['size'],5, utf8_decode(number_format($totales[$c['total_campo']],FS_NF0)),0,0,$c['align']);
            }else{
                $this->Cell($c['size'],5, '',0,0,$c['align']);
            }
            $y1+=5;
        }
        $this->y_pos = $y1+3;
    }

    public function addObservaciones($observaciones){
        $r1 = 10;
        $y1 = $this->y_pos;
        $this->SetXY($r1, -50);
        //$this->Line($r1, $this->getY(), $this->w-10, $this->getY());
        //$y1++;
        if($observaciones){
            $strlength = $this->GetStringWidth('Observaciones: ');
            $this->SetXY($r1, -50);
            $this->SetFont($this->fs_font, "B", $this->fs_font_subtitle_size);
            $this->Cell($strlength+5,5, utf8_decode('Observaciones: '),0,0,'L');
            $this->SetFont($this->fs_font, "", $this->fs_font_subtitle_size);
            $this->MultiCell(($this->w-90),5, utf8_decode($observaciones), 0, "L");
        }else{
            $y1+=5;
        }
        $this->y_pos = $y1+6;
    }

    public function addFirmas($firmas){
        $largo_firma = 0;
        foreach($firmas as $firma){
            if($largo_firma<$this->GetStringWidth($firma)){
                $largo_firma = $this->GetStringWidth($firma);
            }
        }
        $largo_firma+=20;
        $r1 = 10;
        $y1 = $this->y_pos+30;
        $this->SetXY($r1, -30);
        $l1 = $r1;
        foreach($firmas as $firma){
            $this->SetXY($l1, -30);
            $this->Line($l1, $this->getY(), $l1+$largo_firma, $this->getY());
            $this->Cell($largo_firma,5, utf8_decode($firma),0,0,'C');
            $l1 = $l1+$largo_firma+20;
        }
        $this->y_pos = $y1+6;
    }

    public function Footer(){
        $this->SetY(-15);
        $this->Cell(0, 10, utf8_decode('Página ').$this->GroupPageNo().' de '.$this->PageGroupAlias(), 0, 0, 'C');
    }

    public function cerrarArchivo(){

    }

    // create a new page group; call this before calling AddPage()
    public function StartPageGroup()
    {
        $this->NewPageGroup = true;
    }

    // current page in the group
    public function GroupPageNo()
    {
        return $this->PageGroups[$this->CurrPageGroup];
    }

    // alias of the current page group -- will be replaced by the total number of pages in this group
    public function PageGroupAlias()
    {
        return $this->CurrPageGroup;
    }

    public function _beginpage($orientation, $format, $rotation)
    {
        parent::_beginpage($orientation, $format, $rotation);
        if($this->NewPageGroup)
        {
            // start a new group
            $n = sizeof($this->PageGroups)+1;
            $alias = "{nb$n}";
            $this->PageGroups[$alias] = 1;
            $this->CurrPageGroup = $alias;
            $this->NewPageGroup = false;
        }
        elseif($this->CurrPageGroup)
            $this->PageGroups[$this->CurrPageGroup]++;
    }

    public function _putpages()
    {
        $nb = $this->page;
        if (!empty($this->PageGroups))
        {
            // do page number replacement
            foreach ($this->PageGroups as $k => $v)
            {
                for ($n = 1; $n <= $nb; $n++)
                {
                    $this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
                }
            }
        }
        parent::_putpages();
    }

    public function WriteHTML($html)
    {
        //HTML parser
        $prehtml=strip_tags($html, "<b><u><i><a><img><p><br><strong><em><font><tr><blockquote>"); //supprime tous les tags sauf ceux reconnus
        $posthtml=str_replace("\n", ' ', $prehtml); //remplace retour à la ligne par un espace
        $a=preg_split('/<(.*)>/U', $posthtml, -1, PREG_SPLIT_DELIM_CAPTURE); //éclate la chaîne avec les balises
        foreach($a as $i=>$e)
        {
            if($i%2==0){
                //Text
                $this->WriteHTMLText($e);
            }else{
                //Tag
                $this->WriteHTMLTag($e);
            }
        }
    }

    private function WriteHTMLText($e){
        if($this->HREF){
            $this->PutLink($this->HREF, $e);
        }else{
            $this->Write(5, stripslashes(txtentities($e)));
        }
    }

    /**
     *
     * @param type $e
     * @var $a3 array
     */
    private function WriteHTMLTag($e){
        if($e[0]=='/'){
            $this->CloseTag(strtoupper(substr($e, 1)));
        }else{
            //Extract attributes
            $a2=explode(' ', $e);
            $tag=strtoupper(array_shift($a2));
            $attr=array();
            $a3=array();
            foreach($a2 as $v)
            {
                if(preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3)){
                    $attr[strtoupper($a3[1])]=$a3[2];
                }
            }
            $this->OpenTag($tag, $attr);
        }
    }

    public function OpenTag($tag, $attr)
    {
        //Opening tag
        switch($tag){
            case 'STRONG':
                $this->SetStyle('B', true);
                break;
            case 'EM':
                $this->SetStyle('I', true);
                break;
            case 'B':
            case 'I':
            case 'U':
                $this->SetStyle($tag, true);
                break;
            case 'A':
                $this->HREF=$attr['HREF'];
                break;
            case 'IMG':
                $this->ImgTag($attr);
                break;
            case 'TR':
            case 'BLOCKQUOTE':
            case 'BR':
                $this->Ln(5);
                break;
            case 'P':
                $this->Ln(10);
                break;
            case 'FONT':
                $this->FontTag($attr);
                break;
        }
    }

    private function ImgTag($attr){
        if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
            if(!isset($attr['WIDTH'])){
                $attr['WIDTH'] = 0;
            }
            if(!isset($attr['HEIGHT'])){
                $attr['HEIGHT'] = 0;
            }
            $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
        }
    }

    private function FontTag($attr){
        if (isset($attr['COLOR']) && $attr['COLOR']!='') {
            $coul=hex2dec($attr['COLOR']);
            $this->SetTextColor($coul['R'], $coul['V'], $coul['B']);
            $this->issetcolor=true;
        }
        if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontList)) {
            $this->SetFont(strtolower($attr['FACE']));
            $this->issetfont=true;
        }
    }

    public function CloseTag($tag)
    {
        //Closing tag
        if($tag=='STRONG'){
            $tag='B';
        }

        if($tag=='EM'){
            $tag='I';
        }

        if($tag=='B' || $tag=='I' || $tag=='U'){
            $this->SetStyle($tag, false);
        }

        if($tag=='A'){
            $this->HREF='';
        }

        if($tag=='FONT'){
            if ($this->issetcolor) {
                $this->SetTextColor(0);
            }

            if ($this->issetfont) {
                $this->SetFont($this->fs_font);
                $this->issetfont=false;
            }
        }
    }

    public function SetStyle($tag, $enable)
    {
        //Modify style and select corresponding font
        $this->$tag+=($enable ? 1 : -1);
        $style='';
        foreach(array('B', 'I', 'U') as $s){
            if($this->$s>0){
                $style.=$s;
            }
        }
        $this->SetFont('', $style);
    }

    public function PutLink($URL, $txt)
    {
        //Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }

    public function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths=$w;
    }

    public function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns=$a;
    }

    public function Row($data,$separador)
    {
        //Calculate the height of the row
        $nb=0;
        for($c=0;$c<count($data);$c++){
            $nb=max($nb, $this->NbLines($this->widths[$c], $data[$c]));
        }
        $h=$this->fs_espacio*$nb;
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            $w=$this->widths[$i];
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //Print the text
            $this->MultiCell($w, $this->fs_espacio, (!$data[$i] AND $a=='C')?str_pad('_',($w/3),'_',STR_PAD_BOTH):utf8_decode($data[$i]), 0, $a);
            //Put the position to the right of the cell
            $this->SetXY($x+$w, $y);
        }
        if($separador){
            $this->Line(10, $this->getY(), ($this->w-10), $this->getY());
        }
        //Go to the next line
        $this->Ln($h);
    }

    public function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger){
            $this->AddPage($this->CurOrientation);
        }
    }

    public function NbLines($w, $txt){
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0){
            $w=$this->w-$this->rMargin-$this->x;
        }
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r", '', $txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n"){
            $nb--;
        }
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' '){
                $sep=$i;
            }
            $l+=$cw[$c];
            if($l>$wmax){
                if($sep==-1){
                    if($i==$j){
                        $i++;
                    }
                }else{
                    $i=$sep+1;
                }
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }else{
                $i++;
            }
        }
        return $nl;
    }

}
