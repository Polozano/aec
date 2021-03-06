<?php
class AECexport_csv extends AECexport
{
	public function AECexport_csv(){}

	public function putDescription( $array )
	{
		echo $this->fputcsv( $array );
	}

	public function putln( $array )
	{
		echo $this->fputcsv( $array );
	}

	public function fputcsv( $fields = array(), $delimiter = ',', $enclosure = '"' )
	{
		$str = '';
		$escape_char = '\\';
		foreach ($fields as $value) {
			if ( strpos($value, $delimiter) !== false ||
					strpos($value, $enclosure) !== false ||
					strpos($value, "\n") !== false ||
					strpos($value, "\r") !== false ||
					strpos($value, "\t") !== false ||
					strpos($value, ' ') !== false )
			{
				$str2 = $enclosure;
				$escaped = 0;
				$len = strlen($value);
				for ($i=0;$i<$len;$i++) {
					if ($value[$i] == $escape_char) {
						$escaped = 1;
					} else if (!$escaped && $value[$i] == $enclosure) {
						$str2 .= $enclosure;
					} else {
						$escaped = 0;
					}
					$str2 .= $value[$i];
				}
				$str2 .= $enclosure;
				$str .= $str2.$delimiter;
			} else {
				$str .= $value.$delimiter;
			}
		}
		$str = substr($str,0,-1);
		$str .= "\n";
		return $str;
	}

}
