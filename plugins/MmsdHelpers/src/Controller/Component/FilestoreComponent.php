<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\UploadedFileInterface;
use Exception;
use Cake\Log\Log;

class FilestoreComponent extends Component
{
    protected array $components = ['MmsdHelpers.KeyString'];
    private string $baseFilePath = '//purefs01/Shared/Place/';
    private string $virtualFilePath = '/_filestore/';
    private string $appFolder;
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
    public function saveFile(UploadedFileInterface $fileObject, ?string $directoryPath = null, ?string $filename = null): array
    {
        if ((empty($fileObject->getError()))
            and ($fileObject->getSize() > 0)
        ) {
            $clientFilenameInfo = pathinfo($fileObject->getClientFilename());
            Log::debug(print_r($clientFilenameInfo,true));
            $fileExtension = $clientFilenameInfo['extension'] ?? '';
            Log::debug(print_r($fileExtension,true));
            if (empty($filename)) {
                $filename = $this->cleanFilename($clientFilenameInfo['filename']);
            } else {
                $filename = pathinfo($this->cleanFilename($filename))['filename'];
            }
            Log::debug(print_r($filename,true));
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
            ];
        } else {
            if ($fileObject->getSize() === 0) {
                throw new Exception("File size is zero");
            } else {
                throw new Exception($fileObject->getError());
            }
        }
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
        $directoryPath = trim($directoryPath,'/\\');
        $directoryPath = str_replace('\\','/',$directoryPath);
        // create directories if they do not exist
        if (!empty($directoryPath)) {
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
    public function cleanFilename(string $filename): string
    {
        return preg_replace('/\W+/','-',trim($filename));
    }
    public function formatSize(int $bytes, int $decimals = 2): string
    {
        $factor = floor((strlen($bytes) - 1) / 3);
        $sz = ' KMGT';
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . str_split($sz)[$factor] . 'B';
    }
}