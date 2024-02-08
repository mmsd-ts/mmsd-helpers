<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;

class KeyStringComponent extends Component
{
    public function makeKey(int $length = 40): string
    {
        $chars = [];
        for ($i = 0; $i < 3; ++$i) {
            foreach (range(0, 9) as $char) {
                $chars[] = $char;
            }
            foreach (range('a', 'z') as $char) {
                $chars[] = $char;
            }
            foreach (range('A', 'Z') as $char) {
                $chars[] = $char;
            }
        }
        $charStr = implode('',$chars);
        return substr(str_shuffle($charStr),0,$length);
    }
}