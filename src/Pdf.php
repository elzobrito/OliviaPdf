<?php

namespace Oliviapdf;

use Fpdf\Fpdf;

class Pdf extends Fpdf
{
    public $B = 0;
    public $I = 0;
    public $U = 0;
    public $HREF = '';
    public $ALIGN = '';

    public $widths = [];
    public $aligns = [];

    //Footer
    public $autentication = null;
    public $end_page = '/{nb}';

    //Header
    public $image_logo = null;
    public $title = null;
    public $title_config_array = null;
    public $subtitle = null;
    public $subtitle_config_array = null;

    function Circle($x, $y, $r, $style = 'D')
    {
        $this->Ellipse($x, $y, $r, $r, $style);
    }

    function Ellipse($x, $y, $rx, $ry, $style = 'D')
    {
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;
        $k = $this->k;
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $k,
            ($h - $y) * $k,
            ($x + $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x + $lx) * $k,
            ($h - ($y - $ry)) * $k,
            $x * $k,
            ($h - ($y - $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k,
            ($h - ($y - $ry)) * $k,
            ($x - $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x - $rx) * $k,
            ($h - $y) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x - $lx) * $k,
            ($h - ($y + $ry)) * $k,
            $x * $k,
            ($h - ($y + $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $lx) * $k,
            ($h - ($y + $ry)) * $k,
            ($x + $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x + $rx) * $k,
            ($h - $y) * $k,
            $op
        ));
    }

    function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths = is_array($w) ? array_values($w) : [];
    }

    function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns = is_array($a) ? array_values($a) : [];
    }

    function Row($data, $no_border = null)
    {
        if (empty($this->widths)) {
            $this->Error('Column widths are not configured. Call SetWidths() before Row().');
        }

        $data = array_values($data);
        //Calculate the height of the row
        $nb = 0;
        $columns = count($data);

        for ($i = 0; $i < $columns; $i++) {
            $width = isset($this->widths[$i]) ? $this->widths[$i] : 0;
            $text = $this->normalizeText($data[$i]);
            $nb = max($nb, $this->NbLines($width, $text));
            $data[$i] = $text;
        }
        $h = 5 * $nb;
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        // Color and font restoration
        // $this->SetFillColor(224, 235, 255);
        // $this->SetTextColor(0);
        // $this->SetFont('');

        $fill = false;
        for ($i = 0; $i < $columns; $i++) {
            $w = isset($this->widths[$i]) ? $this->widths[$i] : 0;
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            //Draw the border
            if (!$no_border)
                $this->Rect($x, $y, $w, $h);

            //Print the text
            $this->MultiCell($w, 5, $data[$i], 0, $a, $fill);
            //Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
            //            $fill = !$fill;
        }
        //Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        if (!isset($this->CurrentFont['cw'])) {
            $this->Error('No font has been set. Call SetFont() before printing rows.');
        }

        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $this->normalizeText($txt));
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : (isset($cw['?']) ? $cw['?'] : 0);
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    function WriteHTML($html)
    {
        //HTML parser
        $html = str_replace("\n", ' ', (string) $html);
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($a as $i => $e) {
            if ($i % 2 == 0) {
                //Text
                if ($this->HREF)
                    $this->PutLink($this->HREF, $this->normalizeText($e));
                elseif ($this->ALIGN == 'center')
                    $this->Cell(0, 5, $this->normalizeText($e), 0, 1, 'C');
                else
                    $this->Write(5, $this->normalizeText($e));
            } else {
                //Tag
                if ($e !== '' && $e[0] == '/')
                    $this->CloseTag(strtoupper(substr($e, 1)));
                else {
                    //Extract properties
                    $a2 = explode(' ', $e);
                    $tag = strtoupper(array_shift($a2));
                    $prop = array();
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
                            $prop[strtoupper($a3[1])] = $a3[2];
                    }
                    $this->OpenTag($tag, $prop);
                }
            }
        }
    }

    function OpenTag($tag, $prop)
    {
        //Opening tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, true);
        if ($tag == 'A')
            $this->HREF = isset($prop['HREF']) ? $prop['HREF'] : '';
        if ($tag == 'BR')
            $this->Ln(5);
        if ($tag == 'P')
            $this->ALIGN = isset($prop['ALIGN']) ? strtolower($prop['ALIGN']) : '';
        if ($tag == 'HR') {
            if (!empty($prop['WIDTH']))
                $Width = (float) $prop['WIDTH'];
            else
                $Width = $this->w - $this->lMargin - $this->rMargin;
            $this->Ln(2);
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetLineWidth(0.4);
            $this->Line($x, $y, $x + $Width, $y);
            $this->SetLineWidth(0.2);
            $this->Ln(2);
        }
    }

    function CloseTag($tag)
    {
        //Closing tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, false);
        if ($tag == 'A')
            $this->HREF = '';
        if ($tag == 'P')
            $this->ALIGN = '';
    }

    function SetStyle($tag, $enable)
    {
        //Modify style and select corresponding font
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s)
            if ($this->$s > 0)
                $style .= $s;
        $this->SetFont('', $style);
    }

    function PutLink($URL, $txt)
    {
        //Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $this->normalizeText($txt), $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }


    function TextWithDirection($x, $y, $txt, $direction = 'R')
    {
        if ($direction == 'R')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 1, 0, 0, 1, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'L')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', -1, 0, 0, -1, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'U')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, 1, -1, 0, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'D')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, -1, 1, 0, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        else
            $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        if ($this->ColorFlag)
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        $this->_out($s);
    }

    function Header()
    {
        if ($this->image_logo)
            // Logo
            $this->Image($this->image_logo, 75, 10, 50);
        // Arial bold 15
        $this->Rect(40, 35, 120, 0, 1);
        $this->SetTextColor(110, 19, 28);

        $this->SetFont('Arial', 'B', 10);
        if (is_array($this->title_config_array) && count($this->title_config_array) >= 3)
            $this->SetFont($this->title_config_array[0], $this->title_config_array[1], $this->title_config_array[2]);
            
        $this->SetXY(10, 37);
        if ($this->title)
            $this->MultiCell(190, 7, $this->normalizeText($this->title), 0, 'C', 0);

        if (is_array($this->subtitle_config_array) && count($this->subtitle_config_array) >= 3)
            $this->SetFont($this->subtitle_config_array[0], $this->subtitle_config_array[1], $this->subtitle_config_array[2]);
        if ($this->subtitle)
            $this->MultiCell(190, 7, $this->normalizeText($this->subtitle), 0, 'C', 0);
        // Line break
        $this->Ln(5);
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, $this->normalizeText('Página ') . $this->PageNo() . $this->end_page, 0, 0, 'C');

        //$this->end_page = $this->PageNo() . '/{nb}';

        if ($this->autentication != null) {
            $this->SetFont('Arial', '', 8);
            $this->SetXY(10, 10);
            $this->TextWithRotation(205, 280, $this->normalizeText('Autenticação: ') . $this->normalizeText($this->autentication), 90, 0);
        }
    }


    function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle = 0)
    {
        $font_angle += 90 + $txt_angle;
        $txt_angle *= M_PI / 180;
        $font_angle *= M_PI / 180;

        $txt_dx = cos($txt_angle);
        $txt_dy = sin($txt_angle);
        $font_dx = cos($font_angle);
        $font_dy = sin($font_angle);

        $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', $txt_dx, $txt_dy, $font_dx, $font_dy, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        if ($this->ColorFlag)
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        $this->_out($s);
    }

    protected function normalizeText($text)
    {
        if ($text === null) {
            return '';
        }

        if (is_bool($text)) {
            return $text ? '1' : '0';
        }

        if (is_scalar($text)) {
            $text = (string) $text;
        } else {
            $text = json_encode($text);
        }

        if ($text === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

        return utf8_decode($text);
    }
}
