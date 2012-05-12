<?php

/*
Brainfuck compressor and decompressor

usage: compress.php <source> <target> <mode> <type>
mode: 1 => compress, 2 => decompress
type: 1 => fixedV1, 2 => variableV1 (optional with mode 2)

@author shoghicp@gmail.com
*/


function bigendian_int($str){
	$bits = array_map("intval",str_split($str,1));
	$v = 0;
	$x = 0;
	for($i = strlen($str) - 1; $i >= 0; --$i){
		$v += pow(2, $i) * $bits[$x];
		++$x;
	}
	return $v;
}
function bigint2bit($result, $bigendian = false){
	//Big number division
	$bit = "";
	while($result != "0" and $result != "1"){
		$int = str_split($result, 1);
		$len = count($int);
		$tmp = "";
		$tmp2 = "";
		$result = "";
		for($i = 0; $i < $len; ++$i){
			$int[$i] = intval($tmp2 . $int[$i]);
			$result .= (string) floor($int[$i]/2);
			$tmp2 = (string) $int[$i] % 2;
		}
		$bit .= $tmp2;
		$result = (string) intval($result);
	}
	$bit .= $result;
	return $bigendian == true ? strrev($bit):$bit;
}




$mode = isset($argv[3]) ? ($argv[3] == 2 ? "decompress":"compress"):"compress";
$type =  isset($argv[4]) ? ($argv[4] == 2 ? "variableV1":"fixedV1"):"fixedV1";

$source = file_get_contents($argv[1]);



$result = "";

$replace = array(
	"+" => "000",
	"-" => "001",
	">" => "010",
	"<" => "011",
	"." => "100",
	"," => "101",
	"[" => "110",
	"]" => "111",
);


if($mode == "compress"){
	$source = preg_replace("/[^\+\-\<\>\.\,\[\]]/", "", $source);
	$len = strlen($source);
	$slen = $len;
	
	if($type === "variableV1"){
		$last = array("", 0);
		$ops = array();
		for($i = 0; $i < $len; ++$i){
			$op = $source{$i};
			if($last[0] == $op){
				++$ops[$last[1]][1];
			}else{
				$last = array($op, $last[1] + 1);
				$ops[$last[1]] = array($op, 1);
			}
		}
		foreach($ops as $c){
			if($c[1] >= 3){
				$bin = decbin($c[1] - 3);
				
				$l = strlen($bin);
				$pad = 0;
				if($l == 0){
					$pad = 3;
				}elseif(($l % 3) > 0){
					$pad = 3 - ($l % 3) + $l;
				}
				$bin = str_pad($bin, $pad, "0", STR_PAD_LEFT);
				$bin = "1".implode("1", str_split($bin,3));
				$result .= $bin . "0". $replace[$c[0]];
			}else{
				$result .= str_repeat("0". $replace[$c[0]], $c[1]);
			}	
		}
		
		$len = strlen($result);
		$padding = ($len % 8) > 0 ? (8 - ($len % 8)):0;
		$result = "1110001".str_repeat("0", $padding)."1" . $result;

	}elseif($type == "fixedV1"){
		for($i = 0; $i < $len; ++$i){
			$result .= $replace[$source{$i}];
		}
		$padding = (($len * 3) % 8) > 0 ? (8 - ($len * 3) % 8):0;
		$result = "1110000".str_repeat("0", $padding)."1" . $result;
	}
	$len = strlen($result);
	$ret = "";
	for($i = 0; $i < $len; $i += 8){
		$ret .= chr(bindec(substr($result, $i, 8)));
	}
	$result =& $ret;
	file_put_contents($argv[2], $result);
	echo "Compression: ",round((($len / 8) / $slen) * 100, 2), "%",PHP_EOL;

}elseif($mode == "decompress"){
	
	$len = strlen($source);
	$ret = "";
	for($i = 0; $i < $len; ++$i){
		$ret .= str_pad(decbin(ord($source{$i})), 8, "0", STR_PAD_LEFT);
	}
	$source =& $ret;
	if(!isset($argv[4]) or $argv[4] == ""){
		$t = substr($source, 3, 4);
		if($t == "0000"){
			$type = "fixedV1";
		}elseif($t = "0001"){
			$type = "variableV1";
		}
	}
	$source = substr($source, 7);
	for($i = 0; $i < 8; ++$i){
		if($source{$i} == "1"){
			break;
		}
	}
	$source = substr($source, $i + 1);
	if($type === "variableV1"){
		$source = str_split($source, 4);
		$result = "";
		$num = "";
		foreach($source as $bin){
			$t = $bin{0};
			$bin = substr($bin, 1);
			if($t == "1"){
				$num .= $bin;
			}else{
				if($num != ""){
					$num = bindec($num) + 3;
				}else{
					$num = 1;
				}
				$result .= str_repeat(array_search($bin, $replace), $num);
				$num = "";
			}
		}
	}elseif($type == "fixedV1"){
		$source = str_split($source, 3);
		$result = "";
		$num = "";
		foreach($source as $bin){
			$result .= array_search($bin, $replace);
		}
	}
	
	file_put_contents($argv[2], $result);

}
