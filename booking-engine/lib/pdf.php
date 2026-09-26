<?php
/* ===========================================================================
 *  A small PDF writer — enough for receipts and terms, with no library to
 *  install (cPanel-friendly). A4 pages, Helvetica and Helvetica-Bold, text,
 *  lines and filled boxes. Coordinates are in points from the TOP-LEFT.
 *
 *  Text is written in Windows-1252 (the encoding the built-in PDF fonts use),
 *  so the rupee sign ₹ — which those fonts do not have — is printed as "Rs.".
 * ======================================================================== */

class Pdf {
    const W = 595.28;   // A4 in points
    const H = 841.89;

    private array $pages = [];
    private string $cur = '';

    /* Glyph widths (per 1000 units of font size) for ASCII 32–126 — the
       standard Adobe metrics — so text can be right-aligned and wrapped. */
    private const HELV = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,
        667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,
        611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,
        556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    private const HELVB = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,
        667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,
        611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,
        611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

    public function addPage(): void {
        if ($this->cur !== '') $this->pages[] = $this->cur;
        $this->cur = "\n";
    }

    /** UTF-8 in, the bytes the PDF fonts understand out. */
    private static function enc(string $s): string {
        $s = str_replace('₹', 'Rs. ', $s);
        return (string) mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    public function width(string $s, float $size, bool $bold = false): float {
        $w = 0;
        $table = $bold ? self::HELVB : self::HELV;
        foreach (str_split(self::enc($s)) as $ch) {
            $o = ord($ch);
            $w += ($o >= 32 && $o <= 126) ? $table[$o - 32]
                : ([0x97 => 1000, 0x96 => 556, 0xB7 => 278, 0x92 => $bold ? 278 : 222, 0x95 => 350][$o] ?? 556);
        }
        return $w * $size / 1000;
    }

    private static function rgb(array $c): string {
        return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255);
    }

    /** Write one line of text. $align: left | right | center (x is the anchor). */
    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false,
                         array $color = [36, 31, 27], string $align = 'left'): void {
        if ($align === 'right')  $x -= $this->width($s, $size, $bold);
        if ($align === 'center') $x -= $this->width($s, $size, $bold) / 2;
        $raw = strtr(self::enc($s), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
        $this->cur .= sprintf("BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $size, self::rgb($color), $x, self::H - $y, $raw);
    }

    /** Break text into lines no wider than $max points. */
    public function wrap(string $s, float $size, float $max, bool $bold = false): array {
        $lines = [];
        foreach (preg_split("/\r?\n/", $s) as $para) {
            $line = '';
            foreach (preg_split('/\s+/', trim($para)) as $word) {
                $try = $line === '' ? $word : "$line $word";
                if ($line !== '' && $this->width($try, $size, $bold) > $max) { $lines[] = $line; $line = $word; }
                else $line = $try;
            }
            $lines[] = $line;
        }
        return $lines;
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $color = [228, 220, 209], float $w = 0.8): void {
        $this->cur .= sprintf("%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            self::rgb($color), $w, $x1, self::H - $y1, $x2, self::H - $y2);
    }

    public function box(float $x, float $y, float $w, float $h, array $fill): void {
        $this->cur .= sprintf("%s rg %.2F %.2F %.2F %.2F re f\n", self::rgb($fill), $x, self::H - $y - $h, $w, $h);
    }

    /** The finished file. */
    public function output(): string {
        if ($this->cur !== '') { $this->pages[] = $this->cur; $this->cur = ''; }
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $n = 5;
        foreach ($this->pages as $content) {
            $data = function_exists('gzcompress') ? gzcompress($content) : $content;
            $filter = function_exists('gzcompress') ? ' /Filter /FlateDecode' : '';
            $objs[$n] = sprintf('<< /Length %d%s >>' . "\nstream\n%s\nendstream", strlen($data), $filter, $data);
            $objs[$n + 1] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::W, self::H, $n);
            $kids[] = ($n + 1) . ' 0 R';
            $n += 2;
        }
        $objs[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($kids));
        ksort($objs);

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $i => $body) {
            $offsets[$i] = strlen($out);
            $out .= "$i 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) $out .= sprintf("%010d 00000 n \n", $o);
        $out .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $out;
    }
}
