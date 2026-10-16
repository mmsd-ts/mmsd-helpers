<?php
namespace MmsdHelpers\View\Helper;

use Cake\View\Helper;

class FilestoreHelper extends Helper
{
    protected array $_defaultConfig = [
        'mimetypes' => [
            'unknown' => [
                'name' => 'File',
                'icon' => '<span class="fa-regular fa-file" title="File"></span>',
                'allowed' => false,
            ],
        ],
    ];
    public function fileTypeInfo(?string $filepath = null, ?string $mimetype = 'unknown'): array
    {
        if (!empty($filepath)) {
            if (file_exists($filepath)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimetype = $finfo->file($filepath);
            }
        }
        return (!empty($this->getConfig('mimetypes')[$mimetype]))
            ? $this->getConfig('mimetypes')[$mimetype]
            : $this->getConfig('mimetypes')['unknown']
        ;
    }
}

