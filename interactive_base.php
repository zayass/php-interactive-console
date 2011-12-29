<?php

if (!extension_loaded('readline')) dl('readline.so');
if (!extension_loaded('readline')) {
        function readline($prompt) {
                echo $prompt;
                $F=fopen("php://stdin", "r");
                $str=fgets($F,4094);
                fclose($F);
                return $str;
        }
        function readline_completion_function() { }
        function readline_add_history() { }
}

class PHPInteractiveConsole {
        public $historyFile = '.phpi_history';
        
        protected function getcolor_index($color) {
                switch ($color) {
                        case 'black': return 0;
                        case 'red':return 1;
                        case 'green': return 2;
                        case 'yellow': return 3;
                        case 'blue': return 4;
                        case 'magenta':return 5;
                        case 'cyan': return 6;
                        case 'white': return 7;
                }
        }
        protected function setcolor($color = null, $flag = 0) {
                if ($color === null) {
                        //reset
                        echo chr(27).'[0;;m';
                } else {
                        echo chr(27).'['.$flag.';'.(30+$this->getcolor_index($color)).';40m';
                }
        }
        
        public function addToAutoComplete($arr) {
                foreach ($arr as $value ) {
                        $this->autocomplete[mb_substr($value,0,2)][] = $value;
                }
        }
        
        protected $autocomplete = array();
        public function __construct($historyFile = '.phpi_history',$complete = array('internal','user','class','constant')) {
                $this->historyFile = $historyFile;
                $this->initHistory();

                readline_completion_function(array($this,'readlineCompleteCallback'));

                $df = get_defined_functions();
                if (in_array('internal',$complete)) $this->addToAutoComplete($df['internal']);
                if (in_array('user',$complete)) $this->addToAutoComplete($df['user']);
                if (in_array('class',$complete)) $this->addToAutoComplete(get_declared_classes());
                if (in_array('constant',$complete)) $this->addToAutoComplete(array_keys(get_defined_constants()));
        }
        
        public function readlineCompleteCallback($string) {
                $len = mb_strlen($string);
                if ($len<2) return null;
                if (!isset($this->autocomplete[mb_substr($string,0,2)])) return null;
                return $this->autocomplete[mb_substr($string,0,2)];
        }
        
        private function initHistory() {
                if (file_exists($this->historyFile)) {
                        $f = fopen($this->historyFile,'r');
                        while (!feof($f)) {
                                if ($s = trim(fgets($f,16384))) {
                                        readline_add_history($s);
                                }
                        }
                        fclose($f);
                }
        }
        private function saveToHistory($cmd) {
                if (readline_add_history($cmd)) {
                        $f = fopen($this->historyFile,'a+');
                        fwrite($f, $cmd."\n");
                        fclose($f);
                }
        }
        private static function correctSyntax($cmd) {
                if (!self::checkSyntax($cmd)) {
                        if (self::checkSyntax('return ('.$cmd.');')) {
                                return 'return ('.$cmd.');';
                        } elseif (self::checkSyntax($cmd.';')) {
                                return $cmd.';';
                        } else {
                                return false;
                        }
                } else {
                        return $cmd;
                }
        }

        public function run() {
                $prev = null;
                $mode = 0;
                $cmdMulti = '';
                while (true) {
                        $cmd = trim(readline($mode==0?'> ':'- '));
                        if ($cmd && $mode==0 || $mode>0) {
                                if ($cmd && $cmd!=$prev) {
                                        $this->saveToHistory($cmd);
                                        $prev = $cmd;
                                }
                                try {
                                        unset($ret);
                                        if ($mode == 2 && $cmd) $mode = 1;
                                        if ($mode == 0) {
                                                if ($cmdNew = self::correctSyntax($cmd)) {
                                                        $this->setcolor('black',1);
                                                        $ret = eval($cmdNew);
                                                } else {
                                                        $mode = 1;
                                                        $cmdMulti = $cmd."\n";
                                                }
                                        } elseif ($mode == 1) {
                                                if ($cmd != '') {
                                                        $cmdMulti.=$cmd."\n";
                                                } elseif ($cmdNew = self::correctSyntax($cmdMulti)) {
                                                        $this->setcolor('black',1);
                                                        $ret = eval($cmdNew);
                                                        $mode = 0;
                                                } else $mode = 2;
                                        } elseif ($mode == 2) {
                                                //show parse error
                                                $this->setcolor('red',0);
                                                $ret = eval($cmdMulti);
                                                $mode = 0;
                                        }
                                        if (isset($ret) && $ret!==null) {
                                                $this->setcolor('green',0);
                                                print_r($ret);
                                                echo "\n";
                                        }
                                } catch (Exception $e) {
                                        $this->setcolor('black',1);
                                        echo $e."\n\n";
                                }
                                $this->setcolor();
                        }
                }
        }
        
        protected static function checkSyntax($cmd) {
                return @eval('namespace check; return true;'.$cmd);
        }
}

?>
