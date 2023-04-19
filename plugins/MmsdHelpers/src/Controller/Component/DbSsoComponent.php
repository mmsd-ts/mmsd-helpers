<?php

namespace MmsdHelpers\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;

class DbSsoComponent extends Component
{
    public function initialize(array $config): void
    {
    }
    public function username(string $tag): ?string
    {
        $dbSsoRowsTable = $this->getController()->loadModel('MmsdHelpers.DbSsoRows');
        try {
            $row = $dbSsoRowsTable->get($tag);
        } catch (RecordNotFoundException $e) {
            return null;
        }
        $dbSsoRowsTable->delete($row);
        if (
            (empty($row))
            or ($row->appKey != $this->getConfig('appKey'))
        ) {
            return null;
        }
        $cipher = 'aes-128-gcm';
        return openssl_decrypt(
            $row->username,
            $cipher,
            $this->getConfig('dbSsoKey'),
            $options=0,
            base64_decode($row->initVector),
            base64_decode($row->usernameTag)
        );
    }
}