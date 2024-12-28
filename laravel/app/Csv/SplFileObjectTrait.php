<?php
namespace App\Csv;

use WrapPhp;
trait SplFileObjectTrait
{
    use \Ajgl\Csv\Rfc\Spl\SplFileObjectTrait;

    private $to_enc;
    private $from_enc;
    private $delimiter;
    private $enclosure;
    private $escape;
    
    public function setEncode(string $to_enc, string $from_enc) {
        $this->to_enc = $to_enc;
        $this->from_enc = $from_enc;
    }
    
    public function fgetcsv($delimiter = ',', $enclosure = '"', $escape = '"')
    {
        $e = $enclosure ?? $this->enclosure ?? '"';
        $e = preg_quote($e);
        $_line = "";
        $eof = false;
        while (($eof != true) and (!$this->eof())) {
            $_line .= $this->fgets();
            
            if (isset($this->to_enc) || isset($this->from_enc)) {
                $_line = mb_convert_encoding($_line, $this->to_enc ?? 'UTF-8', $this->from_enc ?? 'UTF-8');
            }
            
            $itemcnt = preg_match_all('/'.$e.'/', $_line);
            if ($itemcnt % 2 === 0) {
                $eof = true;
            }
        }
        
        if ($_line === '') {
            return false;
        }
        
        $d = $delimiter ?? $this->delimiter ?? ',';
        $d = preg_quote($d);
        
        $_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
        $_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
        preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
        $_csv_data = $_csv_matches[1];
        for ($_csv_i = 0; $_csv_i < WrapPhp::count($_csv_data); $_csv_i++) {
            $_csv_data[$_csv_i] = preg_replace('/^'.$e.'(.*)'.$e.'$/s', '$1', $_csv_data[$_csv_i]);
            $_csv_data[$_csv_i ] = str_replace($e.$e, $e, $_csv_data[$_csv_i]);
        }
        return $_csv_data;
    }
    
    public function fputcsv($fields, $delimiter = null, $enclosure = null, $escape = null) :void
    {
        parent::fputcsv($fields, $delimiter ?? $this->delimiter ?? ',', $enclosure ?? $this->enclosure ?? '"');
    }

    public function setCsvControl($delimiter = ',', $enclosure = '"', $escape = '"') :void
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        parent::setCsvControl($delimiter, $enclosure, $escape);
    }
}
