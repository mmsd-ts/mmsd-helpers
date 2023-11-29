<?php
namespace MmsdHelpers\Model\Behavior;
use Cake\Database\StatementInterface;
use Cake\ORM\Behavior;

class StoredProcedureBehavior extends Behavior
{
    public function executeProcedure(string $procedureName, array $parameters = []): StatementInterface
    {
        $dbConnection = $this->table()->getConnection();
        $parameterString = '';
        $assignedValues = [];
        $parameterTypes = [];
        if (!empty($parameters)) {
            $parameterList = [];
            foreach ($parameters as $name => $data) {
                $parameterList[] = "@{$name}=:{$name}";
                if (is_array($data)) {
                    // if you send an array, one key must be 'value'
                    $assignedValues[$name] = $data['value'];
                    // type is still optional but then why are you sending an array lol
                    if (!empty($data['type'])) {
                        $parameterTypes[$name] = $data['type'];
                    }
                } else {
                    $assignedValues[$name] = $data;
                }
            }
            $parameterString = implode(', ',$parameterList);
        }
        return $dbConnection->execute(
            "{$procedureName} {$parameterString}"
            ,$assignedValues
            ,$parameterTypes
        );
    }
}
