<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Event\EventInterface;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

class FilestoreComponent extends Component
{
    protected array $components = ['MmsdHelpers.KeyString'];
    private string $baseFilePath = '//purefs01/Shared/Place/';
    private string $virtualFilePath = '/_filestore/';
    private string $appFolder;
    public function beforeRender(EventInterface $event): void
    {
        $this->getController()
            ->viewBuilder()
            ->addHelper('MmsdHelpers.Filestore',[
                'mimetypes' => $this->mimetypes,
            ])
        ;
    }
    public function initialize(array $config): void
    {
        parent::initialize($config);
        if (!empty($config['appFolder'])) {
            $this->appFolder = $config['appFolder'];
        } elseif (!empty(Configure::read('App.appFolder'))) {
            $this->appFolder = Configure::read('App.appFolder');
        } elseif (!empty(Configure::read('App.auditAppName'))) {
            $this->appFolder = Configure::read('App.auditAppName');
        } else {
            $this->appFolder = 'Unspecified';
        }
        if (!empty(Configure::read('debug'))) {
            $this->baseFilePath .= 'Dev/';
        } else {
            $this->baseFilePath .= 'Prod/';
        }
        $this->baseFilePath .= "{$this->appFolder}";
        if (!file_exists($this->baseFilePath)) {
            mkdir($this->baseFilePath);
        }
        $this->baseFilePath .= '/';
        $this->virtualFilePath .= "{$this->appFolder}/";
    }
    
    /**
     * @throws Exception
     */
    public function saveFile(
        UploadedFileInterface $fileObject,
        ?string $directoryPath = null,
        ?string $filename = null,
        ?bool $onlyAllowedMimeTypes = true
    ): array
    {
        if (!empty($fileObject->getError())) {
            throw new Exception($fileObject->getError());
        }
        if ($fileObject->getSize() < 50) {
            throw new Exception("File size is too small");
        }
        $supportedMimeType = true;
        $stream = $fileObject->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        // read first 4k of filestream to read magic numbers to get mimetype
        $headerChunk = $stream->read(4096);
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = $finfo->buffer($headerChunk);
        if ($onlyAllowedMimeTypes) {
            if (!in_array($mimetype,$this->allowedMimeTypes())) {
                throw new Exception("Unsupported file type");
            }
        }
        $clientFilenameInfo = pathinfo($fileObject->getClientFilename());
        $fileExtension = $clientFilenameInfo['extension'] ?? '';
        $filename = $this->cleanFilename($filename);
        if (!empty($filename)) {
            $filename = pathinfo($filename)['filename'];
        } else {
            $filename = $clientFilenameInfo['filename'];
        }
        $directories = '';
        if (!empty($directoryPath)) {
            $directories = $this->verifyDirectories($directoryPath);
        }
        $filenameInfo = [];
        $filepath = '';
        while ((empty($filepath))
            // on the VERY off chance that a file has the same name as an existing file
            or (file_exists($this->baseFilePath . $filepath))
        ) {
            $filenameInfo['displayFile'] = $filename;
            $filenameInfo['filesystemFile'] = $this->KeyString->makeKey();
            $filenameInfo['displayFilename'] = "{$filenameInfo['displayFile']}";
            $filenameInfo['filesystemFilename'] = "{$filenameInfo['filesystemFile']}";
            if (!empty($fileExtension)) {
                $filenameInfo['displayFilename'] .= ".{$fileExtension}";
                $filenameInfo['filesystemFilename'] .= ".{$fileExtension}";
            }
            $filepath = "{$directories}{$filenameInfo['filesystemFilename']}";
        }
        $fileObject->moveTo($this->baseFilePath . $filepath);
        return [
            'filepath' => $this->baseFilePath . $filepath,
            'url' => $this->virtualFilePath . $filepath,
            'directories' => "{$this->appFolder}/{$directories}",
            'displayFile' => $filenameInfo['displayFile'],
            'filesystemFile' => $filenameInfo['filesystemFile'],
            'ext' => $fileExtension,
            'displayFilename' => "{$filenameInfo['displayFilename']}",
            'filesystemFilename' => "{$filenameInfo['filesystemFilename']}",
            'filesize' => $this->formatSize($fileObject->getSize()),
            'uploadedFilename' => $fileObject->getClientFilename(),
            'mimetype' => $mimetype,
        ];
    }
    public function deleteFile(string $filepath): bool
    {
        return unlink($filepath);
    }
    public function downloadFile(string $filepath, ?string $displayFilename = null): bool|Response
    {
        if (file_exists($filepath)) {
            $response = $this->getController()->getResponse()->withFile(
                $filepath,
                [
                    'download' => true,
                    'name' => $displayFilename,
                ]
            );
            return $response;
        }
        return false;
    }
    /**
     * @throws Exception
     */
    public function verifyDirectories(string $directoryPath): string
    {
        // create directories if they do not exist
        if (!empty($directoryPath)) {
            $directoryPath = trim($directoryPath,'/\\');
            $directoryPath = str_replace('\\','/',$directoryPath);
            $workingDirectory = $this->baseFilePath . $directoryPath;
            if (!file_exists($workingDirectory)) {
                if (!mkdir($workingDirectory, 0777, true)) {
                    throw new Exception("Unable to create directory {$workingDirectory}");
                }
            }
            // return directoryPath with any \ changed to /
            return $directoryPath . '/';
        } else {
            throw new Exception("Unable to parse {$directoryPath}");
        }
    }
    public function cleanFilename(?string $filename): ?string
    {
        return preg_replace('#[/\\\\]#','_',trim($filename));
    }
    public function formatSize(int $bytes, int $decimals = 2): string
    {
        $factor = floor((strlen($bytes) - 1) / 3);
        $sz = ' KMGT'; // Hopefully we never see G or T lol :|
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . str_split($sz)[$factor] . 'B';
    }
    public function fileTypeInfo(?string $filepath = null, ?string $mimetype = 'unknown'): array
    {
        if (!empty($filepath)) {
            if (file_exists($filepath)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimetype = $finfo->file($filepath);
            }
        }
        return (!empty($this->mimetypes[$mimetype])) ? $this->mimetypes[$mimetype] : $this->mimetypes['unknown'];
    }
    private function allowedMimeTypes(): array
    {
        return array_keys(array_filter($this->mimetypes, fn($type) => ($type['allowed'] ?? null) === true));
    }
    public array $mimetypes = [
        'application/msword' => [
            'name' => 'Word document',
            'icon' => '<span class="fa-thin fa-file-word" title="Word document"></span>',
            'allowed' => true,
        ],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'name' => 'Word document',
            'icon' => '<span class="fa-thin fa-file-word" title="Word document"></span>',
            'allowed' => true,
        ],
        'application/vnd.oasis.opendocument.text' => [
            'name' => 'OpenDocument text document',
            'icon' => '<span class="fa-thin fa-file-word" title="OpenDocument text document"></span>',
            'allowed' => true,
        ],
        'application/pdf' => [
            'name' => 'PDF document',
            'icon' => '<span class="fa-thin fa-file-pdf" title="PDF document"></span>',
            'allowed' => true,
        ],
        'text/plain' => [
            'name' => 'Text document',
            'icon' => '<span class="fa-thin fa-file-txt" title="Text document"></span>',
            'allowed' => true,
        ],
        'application/rtf' => [
            'name' => 'Rich Text document',
            'icon' => '<span class="fa-thin fa-file" title="Rich Text document"></span>',
            'allowed' => true,
        ],
        'application/xml' => [
            'name' => 'XML document',
            'icon' => '<span class="fa-thin fa-file-xml" title="XML document"></span>',
            'allowed' => true,
        ],
        'text/xml' => [
            'name' => 'XML document',
            'icon' => '<span class="fa-thin fa-file-xml" title="XML document"></span>',
            'allowed' => true,
        ],
        'application/vnd.ms-excel' => [
            'name' => 'Excel document',
            'icon' => '<span class="fa-thin fa-file-excel" title="Excel document"></span>',
            'allowed' => true,
        ],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
            'name' => 'Excel document',
            'icon' => '<span class="fa-thin fa-file-excel" title="Excel document"></span>',
            'allowed' => true,
        ],
        'application/vnd.oasis.opendocument.spreadsheet' => [
            'name' => 'OpenDocument spreadsheet',
            'icon' => '<span class="fa-thin fa-file-excel" title="OpenDocument spreadsheet"></span>',
            'allowed' => true,
        ],
        'text/csv' => [
            'name' => 'Comma separated values document',
            'icon' => '<span class="fa-thin fa-file-csv" title="Comma separated values document"></span>',
            'allowed' => true,
        ],
        'application/zip' => [
            'name' => 'Zip file',
            'icon' => '<span class="fa-thin fa-file-zip" title="Zip file"></span>',
            'allowed' => true,
        ],
        // Windows does a non-standard thing for zip files if you can believe it
        'application/x-zip-compressed' => [
            'name' => 'Zip file',
            'icon' => '<span class="fa-thin fa-file-zip" title="Zip file"></span>',
            'allowed' => true,
        ],
        'text/html' => [
            'name' => 'Saved web page',
            'icon' => '<span class="fa-thin fa-file-html" title="Zip file"></span>',
            'allowed' => true,
        ],
        'image/heic' => [
            'name' => 'Apple photo format',
            'icon' => '<span class="fa-thin fa-image" title="Apple photo format"></span>',
            'allowed' => true,
        ],
        'image/png' => [
            'name' => 'Image file',
            'icon' => '<span class="fa-thin fa-image" title="Image file - png"></span>',
            'allowed' => true,
        ],
        'image/jpeg' => [
            'name' => 'Image file',
            'icon' => '<span class="fa-thin fa-image" title="Image file - jpeg"></span>',
            'allowed' => true,
        ],
        'image/gif' => [
            'name' => 'Image file',
            'icon' => '<span class="fa-thin fa-image" title="Image file - gif"></span>',
            'allowed' => true,
        ],
        'image/webp' => [
            'name' => 'Image file',
            'icon' => '<span class="fa-thin fa-image" title="Image file - webp"></span>',
            'allowed' => true,
        ],
        'unknown' => [
            'name' => 'File',
            'icon' => '<span class="fa-thin fa-file" title="File"></span>',
            'allowed' => false,
        ],
    ];
}