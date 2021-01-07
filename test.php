<?php

//for($x = -20; $x < 20; $x++) {
//    for($z = -20; $z < 20; $z++) {
//        if($x ** 2 + $z ** 2 < 400)
//            echo " x ";
//        else
//            echo "   ";
//    }
//    echo "\n";
//}

//
//for($i = 0; $i < 10; $i++) {
//    for($j = 0; $j < 10; $j++) {
//        echo $i . ":" . $j . "\n";
//        if($i == 4 && $j == 2) {
//            break 1;
//        }
//    }
//}

//$args = "10%dirt,90%grass";
//$exp = explode(",", $args);
//foreach ($exp as $arg) {
//    var_dump(substr($arg, 0, strpos($arg, "%")));
//}

//$t = 0;
//$f = 0;
//
//$p = 25;
//for($i = 0; $i < 100; $i++) {
//    if(mt_rand(1, 100) <= $p) {
//        $t++;
//    } else {
//        $f++;
//    }
//}
//
//var_dump($t, $f);

//$a = ["k" => "v", "n" => "m"];
//var_dump(array_shift($a));

$a = [
    "x" => ["y" => ["z" => "block"]],
    "x1" => ["y1" => ["z1" => "block1"]]
];

$block = null;
if(($x = array_key_first($a)) !== null) {
    if(($y = array_key_first($a[$x])) !== null) {
        if(($z = array_key_first($a[$x][$y])) !== null) {
            var_dump(array_shift($a[$x][$y]), $x, $y, $z);
        }
        if(empty($a[$x][$y])) {
            unset($a[$x][$y]);
        }
    }
    if(empty($a[$x])) {
        unset($a[$x]);
    }
}

var_dump($a);