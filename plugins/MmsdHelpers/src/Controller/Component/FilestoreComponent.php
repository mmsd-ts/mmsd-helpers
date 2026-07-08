<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

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
    public function save(UploadedFileInterface $fileObject, ?string $directoryPath = null, ?string $filename = null): array
    {
        if ((empty($fileObject->getError()))
            and ($fileObject->getSize() > 0)
        ) {
            if (empty($filename)) {
                $filename = $fileObject->getClientFilename();
            }
            $directories = '';
            if (!empty($directoryPath)) {
                $directories = $this->verifyDirectories($directoryPath);
            }
            $filenameInfo = [];
            $filepath = '';
            // on the VERY off chance that a file has the same name as an existing file
            while ((empty($filepath))
                or (file_exists($this->baseFilePath . $filepath))
            ) {
                $filenameInfo = $this->sanitizeFilename($filename);
                $filepath = "{$directories}{$filenameInfo['filesystemName']}{$filenameInfo['ext']}";
            }
            $fileObject->moveTo($this->baseFilePath . $filepath);
            return [
                'filepath' => $this->baseFilePath . $filepath,
                'url' => $this->virtualFilePath . $filepath,
                'directories' => "{$this->appFolder}/{$directories}",
                'displayFile' => $filenameInfo['displayName'],
                'filesystemFile' => $filenameInfo['filesystemName'],
                'ext' => $filenameInfo['ext'],
                'displayFilename' => "{$filenameInfo['displayName']}{$filenameInfo['ext']}",
                'filesystemFilename' => "{$filenameInfo['filesystemName']}{$filenameInfo['ext']}",
                'filesize' => $fileObject->getSize(),
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
    public function delete(string $filepath): bool
    {
        return unlink($filepath);
    }
    public function download(string $filepath, string $displayFilename): bool|Response
    {
        if (file_exists($filepath)) {
            $response = $this->getController()->getResponse()->withFile(
                $filepath,
                [
                    'name' => $displayFilename,
                    'download' => true,
                ],
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
    public function sanitizeFilename(string $filename): array
    {
        $filename = basename($filename);
        $filenameInfo = pathinfo($filename);
        $ext = (!empty($filenameInfo['extension'])) ? '.' . strtolower(trim($filenameInfo['extension'])) : '';
        $displayName = preg_replace('/\W+/','-',trim($filenameInfo['filename']));
        $arbitraryName = $this->KeyString->makeKey();
        return [
            'displayName' => $displayName,
            'filesystemName' => $arbitraryName,
            'ext' => $ext,
        ];
    }
}