<?php
	$words = array("Ook", "Bee");
	$maxMemory = 4096;
	
	$program = str_replace(array("\r","\n"," ","\t"), "",file_get_contents($argv[1]));
	
	$oplen = 1; //brainf***
	foreach($words as $w){
		if(strpos($program, $w) !== false){
			$oplen = 2;
			break;
		}
	}
	$program = str_split(str_replace($words, "", $program), $oplen);
	
	$memory = array();	
	for($i = 0; $i < $maxMemory; ++$i){
		$memory[$i] = 0;
	}
	
	$Mp = 0;
	$Pp = 0;
	$ops = count($program);
	
	while(true){
		$inc = true;
		$op = $program[$Pp];
		switch($op){
			case ".?":
			case ">":
				++$Mp;
				break;
			case "?.":
			case "<":
				--$Mp;
				break;
			case "..":
			case "+":
				++$memory[$Mp];
				break;
			case "!!":
			case "-":
				--$memory[$Mp];
				break;
			case ".!":
			case ".":
				$memory[$Mp] = ord(fread(STDIN, 1));
				break;
			case "!.":
			case ",":
				echo chr($memory[$Mp]);
				break;
			case "!?":
			case "[":
				if($memory[$Mp] == 0){
					$par = 0;
					for($x = $Pp + 1; $x < $ops; ++$x){
						if($program[$x] == "!?"){
							++$par;
						}elseif($program[$x] == "?!"){
							if($par == 0){
								$inc = false;
								$Pp = $x + 1;
							}else{
								--$par;
							}
						}
					}
				}
				break;
			case "?!":
			case "]":
				if($memory[$Mp] != 0){
					$par = 0;
					for($x = $Pp - 1; $x >= 0; --$x){
						if($program[$x] == "?!"){
							++$par;
						}elseif($program[$x] == "!?"){
							if($par == 0){
								$inc = false;
								$Pp = $x + 1;
							}else{
								--$par;
							}
						}
					}
				}
				break;
		
		}
		
		if($inc == true){
			++$Pp;
			if($Pp >= $ops){
				die();
			}
		}
	}
	
?>