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
        return substr(str_shuffle(implode('',$chars)),0,$length);
    }
    public function makeGuidV4(): ?string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        return null;
    }
}